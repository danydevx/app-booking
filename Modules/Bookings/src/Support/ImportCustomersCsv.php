<?php

namespace Modules\Bookings\Support;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Actions\CreateCustomer;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;

final class ImportCustomersCsv
{
    public function __construct(
        private readonly CreateCustomer $createCustomer,
        private readonly RecordCustomerActivity $activity,
    ) {}

    /**
     * @return array{created: int, skipped: int, errors: array<int, array{line: int, message: string}>}
     */
    public function handle(Team $team, User $actor, UploadedFile $file, ?Listing $listing = null): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'No se pudo leer el archivo CSV.']);
        }

        $header = fgetcsv($handle);

        if ($header === false || $header === [null]) {
            fclose($handle);

            throw ValidationException::withMessages(['file' => 'El archivo CSV está vacío o no tiene encabezados.']);
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $columns = $this->columns($header);

        foreach (['name', 'email'] as $required) {
            if (! isset($columns[$required])) {
                fclose($handle);

                throw ValidationException::withMessages([
                    'file' => 'El CSV debe incluir las columnas Nombre y Correo.',
                ]);
            }
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $seenEmails = [];
        $line = 1;
        $maxRows = (int) config('bookings.import_max_rows', 1000);

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($line > $maxRows + 1) {
                $errors[] = [
                    'line' => $line,
                    'message' => "Máximo {$maxRows} filas por importación.",
                ];
                break;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = [
                'name' => trim((string) $this->value($row, $columns, 'name')),
                'email' => strtolower(trim((string) $this->value($row, $columns, 'email'))),
                'phone' => $this->nullableValue($row, $columns, 'phone'),
                'whatsapp_phone' => $this->nullableValue($row, $columns, 'whatsapp_phone'),
                'notes' => $this->nullableValue($row, $columns, 'notes'),
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'whatsapp_phone' => ['nullable', 'string', 'max:40'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'line' => $line,
                    'message' => (string) $validator->errors()->first(),
                ];

                continue;
            }

            if (isset($seenEmails[$data['email']]) || Customer::query()
                ->where('team_id', $team->id)
                ->where('email', $data['email'])
                ->exists()) {
                $skipped++;

                continue;
            }

            $seenEmails[$data['email']] = true;

            $customer = $this->createCustomer->handle($team, $data, $listing);

            $this->activity->handle(
                $customer,
                CustomerActivityType::System,
                'Cliente importado desde CSV.',
                $actor,
            );

            $created++;
        }

        fclose($handle);

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array<string, int>
     */
    private function columns(array $header): array
    {
        $aliases = [
            'name' => ['name', 'nombre', 'cliente'],
            'email' => ['email', 'correo', 'correo_electronico', 'e_mail'],
            'phone' => ['phone', 'telefono', 'tel'],
            'whatsapp_phone' => ['whatsapp', 'whatsapp_phone', 'whatsapp telefono'],
            'notes' => ['notes', 'notas', 'nota'],
        ];

        $normalized = [];

        foreach ($header as $index => $column) {
            $normalized[$this->normalizeHeader((string) $column)] = $index;
        }

        $columns = [];

        foreach ($aliases as $key => $names) {
            foreach ($names as $name) {
                $normalizedKey = $this->normalizeHeader($name);

                if (isset($normalized[$normalizedKey])) {
                    $columns[$key] = $normalized[$normalizedKey];

                    break;
                }
            }
        }

        return $columns;
    }

    private function normalizeHeader(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', Str::lower(Str::ascii($value))), '_');
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $columns
     */
    private function value(array $row, array $columns, string $key): ?string
    {
        $index = $columns[$key] ?? null;

        return $index === null ? null : ($row[$index] ?? null);
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $columns
     */
    private function nullableValue(array $row, array $columns, string $key): ?string
    {
        $value = $this->value($row, $columns, $key);

        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}

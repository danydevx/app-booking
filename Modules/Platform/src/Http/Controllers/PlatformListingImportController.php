<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Listings\Actions\ImportListingData;
use Modules\Listings\Actions\PreviewListingImport;
use Modules\Listings\Support\ListingImportFileReader;
use Modules\Listings\Support\ListingImportTemplate;
use Modules\Platform\Http\Requests\PreviewListingImportRequest;
use RuntimeException;
use Throwable;

class PlatformListingImportController extends Controller
{
    private const PENDING_SESSION = 'platform.listing_import.pending';

    private const PENDING_REPORT = 'platform.listing_import.report';

    private const PENDING_DIRECTORY = 'listing-imports';

    public function create(Request $request, PreviewListingImport $preview): InertiaResponse
    {
        $directory = $this->directory();
        $pending = null;
        $pendingId = $request->session()->get(self::PENDING_SESSION);

        if (is_string($pendingId)) {
            $stored = $this->readPending($pendingId);

            if ($stored === null) {
                $request->session()->forget(self::PENDING_SESSION);
            } else {
                $result = $preview->handle($stored['rows'], $stored['first_line'], $directory);

                $pending = [
                    'id' => $pendingId,
                    'filename' => $stored['filename'],
                    'rows' => $result['rows'],
                    'summary' => $result['summary'],
                ];
            }
        }

        $this->prunePending();

        return Inertia::render('Platform/Listings/Import', [
            'directory' => $directory === null
                ? null
                : [
                    'name' => $directory->name,
                    'listings_url' => route('platform.listings.index'),
                ],
            'pending' => $pending,
            'report' => $request->session()->get(self::PENDING_REPORT),
            'template_urls' => [
                'csv' => route('platform.listings.import.template', ['format' => 'csv']),
                'json' => route('platform.listings.import.template', ['format' => 'json']),
            ],
            'urls' => [
                'index' => route('platform.listings.index'),
                'preview' => route('platform.listings.import.preview'),
                'store' => route('platform.listings.import.store'),
                'cancel' => route('platform.listings.import.cancel'),
            ],
        ]);
    }

    public function preview(
        PreviewListingImportRequest $request,
        ListingImportFileReader $reader,
    ): RedirectResponse {
        if ($this->directory() === null) {
            return $this->directoryMissing();
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $result = $reader->read((string) $file->getRealPath(), $extension);
        } catch (RuntimeException $exception) {
            return $this->backToImport(['file' => $exception->getMessage()]);
        }

        if ($result['rows'] === []) {
            return $this->backToImport(['file' => 'El archivo no contiene terrazas.']);
        }

        $id = (string) Str::uuid();

        Storage::disk('local')->put(
            $this->pendingPath($id),
            (string) json_encode([
                'filename' => $file->getClientOriginalName(),
                'first_line' => $result['first_line'],
                'rows' => $result['rows'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        $request->session()->put(self::PENDING_SESSION, $id);
        $request->session()->forget(self::PENDING_REPORT);

        return to_route('platform.listings.import');
    }

    public function store(
        Request $request,
        PreviewListingImport $preview,
        ImportListingData $import,
    ): RedirectResponse {
        $directory = $this->directory();

        if ($directory === null) {
            return $this->directoryMissing();
        }

        $pendingId = $request->session()->get(self::PENDING_SESSION);

        if (! is_string($pendingId)) {
            return $this->backToImport(['file' => 'Sube un archivo antes de importar.']);
        }

        $stored = $this->readPending($pendingId);

        if ($stored === null) {
            $request->session()->forget(self::PENDING_SESSION);

            return $this->backToImport(['file' => 'La vista previa caducó; vuelve a subir el archivo.']);
        }

        $result = $preview->handle($stored['rows'], $stored['first_line'], $directory);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $rows = [];

        foreach ($result['rows'] as $row) {
            if ($row['status'] === 'error') {
                $failed++;
                $rows[] = [
                    'line' => $row['line'],
                    'name' => $row['name'],
                    'status' => 'error',
                    'message' => implode(' ', $row['errors']),
                    'url' => null,
                ];

                continue;
            }

            try {
                $outcome = $import->handle($directory, $row['data'], null, false);
                $listing = $outcome['listing'];

                $outcome['created'] ? $created++ : $updated++;

                $rows[] = [
                    'line' => $row['line'],
                    'name' => $listing->name,
                    'status' => $outcome['created'] ? 'created' : 'updated',
                    'message' => implode(' ', $outcome['warnings']),
                    'url' => route('platform.listings.show', ['listing' => $listing->slug]),
                ];
            } catch (Throwable $exception) {
                $failed++;
                $rows[] = [
                    'line' => $row['line'],
                    'name' => $row['name'],
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'url' => null,
                ];
            }
        }

        Storage::disk('local')->delete($this->pendingPath($pendingId));
        $request->session()->forget(self::PENDING_SESSION);
        $request->session()->flash(self::PENDING_REPORT, [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'rows' => $rows,
        ]);

        Inertia::flash('toast', [
            'type' => $failed > 0 ? 'warning' : 'success',
            'message' => "Importación terminada: {$created} creadas, {$updated} actualizadas, {$failed} con error.",
        ]);

        return to_route('platform.listings.import');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $pendingId = $request->session()->get(self::PENDING_SESSION);

        if (is_string($pendingId)) {
            Storage::disk('local')->delete($this->pendingPath($pendingId));
        }

        $request->session()->forget([self::PENDING_SESSION, self::PENDING_REPORT]);

        return to_route('platform.listings.import');
    }

    public function template(Request $request): HttpResponse
    {
        if ($request->query('format') === 'json') {
            return response(ListingImportTemplate::json(), 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="plantilla-terrazas.json"',
            ]);
        }

        return response("\xEF\xBB\xBF".ListingImportTemplate::csv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-terrazas.csv"',
        ]);
    }

    private function directory(): ?Team
    {
        return Team::query()->where('is_directory', true)->first();
    }

    /**
     * @return array{filename: string, first_line: int, rows: array<int, array<string, mixed>>}|null
     */
    private function readPending(string $id): ?array
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = $this->pendingPath($id);

        if (! $disk->exists($path)) {
            return null;
        }

        $decoded = json_decode((string) $disk->get($path), true);

        if (! is_array($decoded) || ! is_array($decoded['rows'] ?? null)) {
            return null;
        }

        return [
            'filename' => (string) ($decoded['filename'] ?? 'importación'),
            'first_line' => (int) ($decoded['first_line'] ?? 1),
            'rows' => array_values(array_filter($decoded['rows'], 'is_array')),
        ];
    }

    private function pendingPath(string $id): string
    {
        return self::PENDING_DIRECTORY.'/'.$id.'.json';
    }

    private function prunePending(): void
    {
        $disk = Storage::disk('local');
        $threshold = now()->subDay()->getTimestamp();

        foreach ($disk->files(self::PENDING_DIRECTORY) as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $disk->delete($file);
            }
        }
    }

    /**
     * @param  array<string, string>  $errors
     */
    private function backToImport(array $errors): RedirectResponse
    {
        return to_route('platform.listings.import')->withErrors($errors);
    }

    private function directoryMissing(): RedirectResponse
    {
        return $this->backToImport([
            'file' => 'Primero crea la cuenta Directorio con `php artisan app:directory-team`.',
        ]);
    }
}

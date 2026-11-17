<?php

namespace Modules\Listings\Actions;

use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingEventType;
use Modules\Platform\Support\GeographicCoverageService;
use RuntimeException;

final class ImportListingData
{
    private const NUMERIC_FIELDS = [
        'capacity',
        'price_from_cents',
        'price_weekday_cents',
        'price_weekend_cents',
        'included_hours',
        'included_people',
        'extra_hour_cents',
        'extra_person_cents',
        'deposit_required_cents',
    ];

    private const STRING_FIELDS = [
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'rules',
        'price_notes',
        'contact_name',
        'contact_email',
        'contact_phone',
        'whatsapp_phone',
        'currency',
        'timezone',
    ];

    public function __construct(
        private readonly GenerateListingSlug $generateSlug,
        private readonly AddListingMedia $addMedia,
        private readonly PublishListing $publishListing,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{listing: Listing, created: bool, published: bool, warnings: array<int, string>}
     */
    public function handle(
        Team $directory,
        array $data,
        ?string $imagesRoot = null,
        bool $publish = false,
    ): array {
        $warnings = [];
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Cada terraza necesita un nombre.');
        }

        $externalRef = $this->nullableString($data['external_ref'] ?? null);

        return DB::transaction(function () use ($directory, $data, $name, $externalRef, $imagesRoot, $publish, &$warnings): array {
            $listing = $externalRef !== null
                ? Listing::query()->where('external_ref', $externalRef)->first()
                : null;

            $created = $listing === null;

            if ($listing === null) {
                $listing = new Listing;
                $listing->team_id = $directory->id;
                $listing->type = (string) config('listings.default_type', 'terrace');
                $listing->slug = $this->generateSlug->handle($name);
                $listing->external_ref = $externalRef;
                $listing->status = ListingStatus::Draft;
                $listing->published_at = null;
                $listing->claimed_at = null;
                $listing->timezone = $directory->timezone
                    ?: (string) config('listings.default_timezone', 'America/Mexico_City');
            } elseif (! $listing->isClaimable()) {
                throw new RuntimeException("La terraza {$listing->name} ya fue reclamada o no pertenece al Directorio; no se actualizó.");
            }

            $listing->name = $name;

            foreach (self::STRING_FIELDS as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }

                $value = $this->nullableString($data[$field]);

                if ($field === 'currency' && $value === null) {
                    $value = (string) config('listings.currency', 'MXN');
                }

                if ($field === 'timezone' && $value === null) {
                    $value = (string) config('listings.default_timezone', 'America/Mexico_City');
                }

                $listing->{$field} = $value;
            }

            if (! array_key_exists('country', $data)) {
                $listing->country = $listing->country ?: 'México';
            }

            foreach (self::NUMERIC_FIELDS as $field) {
                if (array_key_exists($field, $data)) {
                    $listing->{$field} = $this->nullableInt($data[$field]);
                }
            }

            if (array_key_exists('latitude', $data)) {
                $latitude = $this->nullableFloat($data['latitude']);
                $listing->latitude = $latitude === null ? null : (string) $latitude;
            }

            if (array_key_exists('longitude', $data)) {
                $longitude = $this->nullableFloat($data['longitude']);
                $listing->longitude = $longitude === null ? null : (string) $longitude;
            }

            if (array_key_exists('services', $data)) {
                $listing->services = $this->stringList($data['services']);
            }

            if (array_key_exists('details', $data)) {
                $listing->details = is_array($data['details']) ? $data['details'] : null;
            }

            if (array_key_exists('daily_pricing', $data)) {
                $listing->daily_pricing = is_array($data['daily_pricing']) ? $data['daily_pricing'] : null;
            }

            $listing->save();

            if (array_key_exists('event_types', $data)) {
                $this->syncEventTypes($listing, $this->stringList($data['event_types']));
            }

            $this->importImages($listing, $this->stringList($data['images'] ?? []), $imagesRoot, $warnings);

            $published = false;

            if ($listing->status === ListingStatus::Published) {
                $published = true;
            } elseif ($publish) {
                try {
                    $this->publishListing->handle($listing);
                    $published = true;
                } catch (ValidationException $exception) {
                    foreach ($exception->errors() as $messages) {
                        foreach ($messages as $message) {
                            $warnings[] = $message;
                        }
                    }
                }
            }

            if ($listing->isClaimable()) {
                $coverage = app(GeographicCoverageService::class);

                if (! $coverage->isEligible($listing->state, $listing->city)) {
                    $warnings[] = 'La ubicación no está dentro de la cobertura habilitada; la terraza quedará oculta al público.';
                }
            }

            return [
                'listing' => $listing->refresh(),
                'created' => $created,
                'published' => $published,
                'warnings' => array_values(array_unique($warnings)),
            ];
        });
    }

    /**
     * @param  array<int, string>  $eventTypes
     */
    private function syncEventTypes(Listing $listing, array $eventTypes): void
    {
        if ($eventTypes === []) {
            return;
        }

        $ids = ListingEventType::query()
            ->where('is_active', true)
            ->where(function ($query) use ($eventTypes): void {
                $query->whereIn('slug', $eventTypes);

                $numeric = array_values(array_filter($eventTypes, fn (string $value): bool => ctype_digit($value)));

                if ($numeric !== []) {
                    $query->orWhereIn('id', array_map('intval', $numeric));
                }
            })
            ->pluck('id')
            ->all();

        $listing->eventTypes()->sync($ids);
    }

    /**
     * @param  array<int, string>  $images
     * @param  array<int, string>  $warnings
     */
    private function importImages(Listing $listing, array $images, ?string $imagesRoot, array &$warnings): void
    {
        if ($images === []) {
            return;
        }

        $existing = $listing->media()
            ->pluck('path')
            ->map(fn (string $path): string => basename($path))
            ->all();

        foreach ($images as $image) {
            $image = trim($image);

            if ($image === '') {
                continue;
            }

            $temporaryPath = null;

            try {
                $path = $this->resolveImagePath($image, $imagesRoot, $temporaryPath);

                if ($path === null) {
                    $warnings[] = "No se encontró la imagen {$image}.";

                    continue;
                }

                $filename = basename($path);

                if (in_array($filename, $existing, true)) {
                    continue;
                }

                $mime = @mime_content_type($path) ?: 'image/jpeg';

                if (! str_starts_with((string) $mime, 'image/')) {
                    $warnings[] = "El archivo {$image} no es una imagen válida.";

                    continue;
                }

                $upload = new UploadedFile($path, $filename, $mime, null, true);

                $this->addMedia->handle($listing, $upload, ListingMediaType::Image, $listing->name);
                $existing[] = $filename;
            } catch (RuntimeException $exception) {
                $warnings[] = $exception->getMessage();
            } finally {
                if ($temporaryPath !== null && is_file($temporaryPath)) {
                    @unlink($temporaryPath);
                }
            }
        }
    }

    private function resolveImagePath(string $image, ?string $imagesRoot, ?string &$temporaryPath): ?string
    {
        if (Str::startsWith($image, ['http://', 'https://'])) {
            $response = Http::timeout(20)->get($image);

            if (! $response->successful()) {
                return null;
            }

            $extension = pathinfo(parse_url($image, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
            $tempPath = tempnam(sys_get_temp_dir(), 'listing-image-');

            if ($tempPath === false) {
                return null;
            }

            $downloadPath = $tempPath.'.'.$extension;
            file_put_contents($downloadPath, $response->body());
            @unlink($tempPath);
            $temporaryPath = $downloadPath;

            return $temporaryPath;
        }

        $candidates = array_filter([
            $image,
            $imagesRoot !== null ? rtrim($imagesRoot, '/').'/'.$image : null,
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\s*[|,]\s*/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_scalar($item))
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}

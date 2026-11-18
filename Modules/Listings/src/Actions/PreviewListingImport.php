<?php

namespace Modules\Listings\Actions;

use App\Models\Team;
use Illuminate\Support\Collection;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingEventType;
use Modules\Listings\Support\CommercialServiceCatalog;
use Modules\Listings\Support\ListingImportNormalizer;
use Modules\Listings\Support\TerraceServiceCatalog;
use Modules\Platform\Support\GeographicCoverageService;

/**
 * Validates import rows against the database and prepares the preview shown in
 * the platform importer before anything is written.
 */
final class PreviewListingImport
{
    public function __construct(
        private readonly ListingImportNormalizer $normalizer,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{total: int, create: int, update: int, error: int}
     * }
     */
    public function handle(array $rows, int $firstLine = 1, ?Team $directory = null): array
    {
        $directory ??= Team::query()->where('is_directory', true)->first();

        $normalized = $this->normalizer->normalize($rows, $firstLine);
        $existing = $this->existingListings($directory, $normalized);
        $eventTypeLabels = $this->eventTypeLabels($normalized);
        $serviceLabels = TerraceServiceCatalog::all() + CommercialServiceCatalog::all();
        $coverage = app(GeographicCoverageService::class);

        $seenRefs = [];
        $preview = [];
        $summary = ['total' => 0, 'create' => 0, 'update' => 0, 'error' => 0];

        foreach ($normalized as $row) {
            $data = $row['data'];
            $errors = $row['errors'];
            $warnings = $row['warnings'];

            $ref = $data['external_ref'] ?? null;
            $existingListing = is_string($ref) ? $existing->get($ref) : null;

            if (is_string($ref) && isset($seenRefs[$ref])) {
                $errors[] = "La referencia externa {$ref} está repetida en el archivo.";
            }

            if (is_string($ref)) {
                $seenRefs[$ref] = true;
            }

            if ($existingListing instanceof Listing && ! $existingListing->isClaimable()) {
                $errors[] = 'La terraza ya fue reclamada por su propietario; no se actualizará.';
            }

            $name = $data['name'] ?? null;
            $city = $data['city'] ?? null;
            $state = $data['state'] ?? null;

            if (! $existingListing && is_string($name) && $name !== '') {
                $duplicate = $this->findByNameAndCity($directory, $name, is_string($city) ? $city : null);

                if ($duplicate !== null) {
                    $warnings[] = 'Ya existe una terraza con ese nombre y municipio; se creará otra.';
                }
            }

            if (is_string($state) && is_string($city) && ! $coverage->isEligible($state, $city)) {
                $warnings[] = 'La ubicación no está dentro de la cobertura habilitada; la terraza quedará oculta al público.';
            }

            $status = $errors !== []
                ? 'error'
                : ($existingListing ? 'update' : 'create');

            $summary['total']++;
            $summary[$status]++;

            $preview[] = [
                'line' => $row['line'],
                'name' => is_string($name) ? $name : null,
                'capacity' => $data['capacity'] ?? null,
                'event_types' => $this->labels(
                    is_array($data['event_types'] ?? null) ? $data['event_types'] : [],
                    $eventTypeLabels,
                ),
                'services' => $this->labels(
                    is_array($data['services'] ?? null) ? $data['services'] : [],
                    $serviceLabels,
                ),
                'status' => $status,
                'errors' => array_values(array_unique($errors)),
                'warnings' => array_values(array_unique($warnings)),
                'data' => $data,
            ];
        }

        return [
            'rows' => $preview,
            'summary' => $summary,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalized
     * @return Collection<string, Listing>
     */
    private function existingListings(?Team $directory, array $normalized): Collection
    {
        $refs = collect($normalized)
            ->map(fn (array $row): mixed => $row['data']['external_ref'] ?? null)
            ->filter(fn (mixed $ref): bool => is_string($ref) && $ref !== '')
            ->unique()
            ->values()
            ->all();

        if ($refs === []) {
            return collect();
        }

        return Listing::query()
            ->with('team')
            ->whereIn('external_ref', $refs)
            ->get()
            ->keyBy('external_ref');
    }

    private function findByNameAndCity(?Team $directory, string $name, ?string $city): ?Listing
    {
        if ($directory === null) {
            return null;
        }

        return Listing::query()
            ->where('team_id', $directory->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when(
                $city !== null && $city !== '',
                fn ($query) => $query->whereRaw('LOWER(city) = ?', [mb_strtolower($city)]),
                fn ($query) => $query->whereNull('city'),
            )
            ->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalized
     * @return array<string, string>
     */
    private function eventTypeLabels(array $normalized): array
    {
        $slugs = collect($normalized)
            ->flatMap(fn (array $row): array => is_array($row['data']['event_types'] ?? null)
                ? $row['data']['event_types']
                : [])
            ->unique()
            ->values()
            ->all();

        if ($slugs === []) {
            return [];
        }

        return ListingEventType::query()
            ->whereIn('slug', $slugs)
            ->pluck('name', 'slug')
            ->all();
    }

    /**
     * @param  array<int, string>  $values
     * @param  array<string, string>  $labels
     * @return array<int, array{value: string, label: string}>
     */
    private function labels(array $values, array $labels): array
    {
        return array_map(
            fn (string $value): array => [
                'value' => $value,
                'label' => $labels[$value] ?? $value,
            ],
            $values,
        );
    }
}

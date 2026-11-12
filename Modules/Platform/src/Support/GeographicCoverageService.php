<?php

namespace Modules\Platform\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Platform\Models\GeoCountry;
use Modules\Platform\Models\GeoMunicipalityCoverage;
use Modules\Platform\Models\GeoState;
use Modules\Platform\Models\GeoStateCoverage;

final class GeographicCoverageService
{
    private const CACHE_KEY = 'platform:geographic-coverage:v1';

    private const CACHE_VERSION_KEY = 'platform:geographic-coverage:version';

    /**
     * Effective, commercially enabled coverage.
     *
     * @return array<string, array{
     *     label: string,
     *     state_names: array<int, string>,
     *     all_municipalities: bool,
     *     municipalities: array<int, string>
     * }>
     */
    public function effectiveLocations(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $locations = [];

        $states = GeoState::query()
            ->with(['coverage', 'municipalities.coverage'])
            ->orderBy('name')
            ->get();

        foreach ($states as $state) {
            $coverage = $state->coverage;

            if (! $coverage?->enabled) {
                continue;
            }

            $municipalities = $coverage->all_municipalities
                ? $state->municipalities
                : $state->municipalities->filter(
                    fn ($municipality): bool => (bool) $municipality->coverage?->enabled,
                );

            if ($municipalities->isEmpty()) {
                continue;
            }

            $locations[$state->name] = [
                'label' => $state->name,
                'state_names' => $this->stateNames($state),
                'all_municipalities' => (bool) $coverage->all_municipalities,
                'municipalities' => $municipalities
                    ->pluck('name')
                    ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all(),
            ];
        }

        Cache::put(self::CACHE_KEY, $locations, now()->addMinutes(10));

        return $locations;
    }

    /**
     * Locations payload for public selectors and owner forms.
     *
     * @return array<string, array{label: string, municipalities: array<int, string>}>
     */
    public function locations(): array
    {
        return collect($this->effectiveLocations())
            ->map(fn (array $state): array => [
                'label' => $state['label'],
                'municipalities' => $state['municipalities'],
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function countries(): array
    {
        return GeoCountry::query()
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (GeoCountry $country): array => [
                'value' => $country->name,
                'label' => $country->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function enabledStateNames(bool $includeAliases = true): array
    {
        $locations = $this->effectiveLocations();

        if (! $includeAliases) {
            return array_keys($locations);
        }

        return collect($locations)
            ->flatMap(fn (array $state): array => $state['state_names'])
            ->unique()
            ->values()
            ->all();
    }

    public function singleEnabledStateName(): ?string
    {
        $locations = $this->locations();

        return count($locations) === 1 ? array_key_first($locations) : null;
    }

    /**
     * Database values that represent the given enabled state. Legacy listings
     * may store a short alias (for example "CDMX") instead of the official
     * catalog name, so filters must accept every representation.
     *
     * @return array<int, string>
     */
    public function stateValues(string $state): array
    {
        $normalized = $this->normalize($state);

        if ($normalized === '') {
            return [];
        }

        foreach ($this->effectiveLocations() as $location) {
            foreach ($location['state_names'] as $name) {
                if ($this->normalize($name) === $normalized) {
                    return $location['state_names'];
                }
            }
        }

        return [];
    }

    public function isStateEnabled(string $state): bool
    {
        $normalized = $this->normalize($state);

        if ($normalized === '') {
            return false;
        }

        foreach ($this->effectiveLocations() as $location) {
            foreach ($location['state_names'] as $name) {
                if ($this->normalize($name) === $normalized) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isMunicipalityEnabled(string $state, ?string $municipality): bool
    {
        $normalizedState = $this->normalize($state);
        $normalizedMunicipality = $this->normalize($municipality);

        if ($normalizedState === '' || $normalizedMunicipality === '') {
            return false;
        }

        foreach ($this->effectiveLocations() as $location) {
            $stateMatches = collect($location['state_names'])
                ->contains(fn (string $name): bool => $this->normalize($name) === $normalizedState);

            if (! $stateMatches) {
                continue;
            }

            return collect($location['municipalities'])
                ->contains(fn (string $name): bool => $this->normalize($name) === $normalizedMunicipality);
        }

        return false;
    }

    public function isEligible(?string $state, ?string $municipality): bool
    {
        if ($state === null || $municipality === null) {
            return false;
        }

        return $this->isMunicipalityEnabled($state, $municipality);
    }

    /**
     * Apply the effective coverage to a listings query.
     *
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public function applyToQuery(
        Builder $query,
        string $stateColumn = 'state',
        string $cityColumn = 'city',
    ): Builder {
        $locations = $this->effectiveLocations();

        return $query->where(function (Builder $query) use ($locations, $stateColumn, $cityColumn): void {
            if ($locations === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $first = true;

            foreach ($locations as $location) {
                $method = $first ? 'where' : 'orWhere';
                $first = false;

                $query->{$method}(function (Builder $inner) use ($location, $stateColumn, $cityColumn): void {
                    $inner->whereIn($stateColumn, $location['state_names']);

                    if (! $location['all_municipalities']) {
                        $inner->whereIn($cityColumn, $location['municipalities']);
                    }
                });
            }
        });
    }

    /**
     * Data for the Superadmin coverage screen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function adminStates(): array
    {
        return GeoState::query()
            ->with(['coverage', 'municipalities.coverage'])
            ->orderBy('name')
            ->get()
            ->map(function (GeoState $state): array {
                $coverage = $state->coverage;
                $enabled = (bool) $coverage?->enabled;
                $all = (bool) $coverage?->all_municipalities;

                $municipalities = $state->municipalities
                    ->map(fn ($municipality): array => [
                        'id' => $municipality->id,
                        'code' => $municipality->code,
                        'name' => $municipality->name,
                        'enabled' => $all || (bool) $municipality->coverage?->enabled,
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $state->id,
                    'code' => $state->code,
                    'name' => $state->name,
                    'enabled' => $enabled,
                    'all_municipalities' => $all,
                    'municipalities_count' => count($municipalities),
                    'enabled_municipalities_count' => ! $enabled
                        ? 0
                        : ($all
                            ? count($municipalities)
                            : collect($municipalities)->where('enabled', true)->count()),
                    'municipalities' => $municipalities,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     enabled?: bool,
     *     all_municipalities?: bool,
     *     municipality_ids?: array<int, int>
     * }>  $states
     */
    public function updateCoverage(array $states, User $actor): void
    {
        DB::transaction(function () use ($states, $actor): void {
            $country = GeoCountry::query()->first();
            $changes = [];

            foreach ($states as $payload) {
                $state = GeoState::query()->findOrFail((int) $payload['id']);
                $enabled = (bool) ($payload['enabled'] ?? false);
                $all = (bool) ($payload['all_municipalities'] ?? false);
                $selectedIds = array_map('intval', $payload['municipality_ids'] ?? []);

                $coverage = GeoStateCoverage::query()->firstOrCreate(
                    ['geo_state_id' => $state->id],
                    ['enabled' => false, 'all_municipalities' => false],
                );

                $coverage->update([
                    'enabled' => $enabled,
                    'all_municipalities' => $enabled && $all,
                ]);

                if ($enabled && ! $all) {
                    $municipalityIds = $state->municipalities()->pluck('id')->all();
                    $selected = array_values(array_intersect(
                        array_map('intval', $municipalityIds),
                        $selectedIds,
                    ));

                    GeoMunicipalityCoverage::query()
                        ->whereIn('geo_municipality_id', $municipalityIds)
                        ->update(['enabled' => false]);

                    foreach (array_chunk($selected, 100) as $chunk) {
                        $now = now();

                        GeoMunicipalityCoverage::query()->upsert(
                            array_map(fn (int $id): array => [
                                'geo_municipality_id' => $id,
                                'enabled' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ], $chunk),
                            ['geo_municipality_id'],
                            ['enabled', 'updated_at'],
                        );
                    }
                }

                $changes[$state->code] = [
                    'name' => $state->name,
                    'enabled' => $enabled,
                    'all_municipalities' => $enabled && $all,
                    'enabled_municipalities' => $enabled
                        ? ($all
                            ? $state->municipalities()->count()
                            : count($selectedIds))
                        : 0,
                ];
            }

            $activity = activity('moderation')
                ->causedBy($actor)
                ->withProperties(['states' => $changes]);

            if ($country !== null) {
                $activity->performedOn($country);
            }

            $activity->log('geographic-coverage.updated');
        });

        $this->flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forever(self::CACHE_VERSION_KEY, (string) Str::uuid());
    }

    public static function cacheVersion(): string
    {
        return (string) Cache::get(self::CACHE_VERSION_KEY, '1');
    }

    /**
     * @return array<int, string>
     */
    private function stateNames(GeoState $state): array
    {
        $names = [$state->name, $state->code];

        // Legacy public URLs/listings used the short CDMX key.
        if ($state->code === '09') {
            $names[] = 'CDMX';
        }

        return array_values(array_unique($names));
    }

    private function normalize(?string $value): string
    {
        return Str::lower(Str::ascii(trim((string) $value)));
    }
}

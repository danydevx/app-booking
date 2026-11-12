<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Models\GeoCountry;
use Modules\Platform\Models\GeoMunicipality;
use Modules\Platform\Models\GeoState;
use Modules\Platform\Models\GeoStateCoverage;
use Modules\Platform\Support\GeographicCoverageService;
use RuntimeException;

class GeographicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = dirname(__DIR__, 3).'/database/data/mexico_geography.json';
        $contents = @file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException("The geographic catalog data file was not found at [{$path}].");
        }

        /** @var array<int, array{code: string, name: string, municipalities: array<int, array{code: string, name: string}>}> $data */
        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            $country = GeoCountry::query()->updateOrCreate(
                ['code' => 'MX'],
                ['name' => 'México'],
            );

            $now = now();

            $states = collect($data)
                ->values()
                ->map(fn (array $state, int $index): array => [
                    'geo_country_id' => $country->id,
                    'code' => $state['code'],
                    'name' => $state['name'],
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            foreach (array_chunk($states, 50) as $chunk) {
                GeoState::query()->upsert(
                    $chunk,
                    ['geo_country_id', 'code'],
                    ['name', 'sort_order', 'updated_at'],
                );
            }

            $stateIds = GeoState::query()
                ->where('geo_country_id', $country->id)
                ->pluck('id', 'code');

            $municipalities = [];

            foreach ($data as $state) {
                $stateId = $stateIds[$state['code']] ?? null;

                if ($stateId === null) {
                    continue;
                }

                foreach ($state['municipalities'] as $municipality) {
                    $municipalities[] = [
                        'geo_state_id' => $stateId,
                        'code' => $municipality['code'],
                        'full_code' => $state['code'].$municipality['code'],
                        'name' => $municipality['name'],
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($municipalities, 100) as $chunk) {
                GeoMunicipality::query()->upsert(
                    $chunk,
                    ['geo_state_id', 'code'],
                    ['full_code', 'name', 'sort_order', 'updated_at'],
                );
            }

            $statesByCode = GeoState::query()->pluck('id', 'code');

            foreach ($statesByCode as $code => $stateId) {
                // First run defaults: only Jalisco is commercially enabled.
                // firstOrCreate preserves any later Superadmin configuration.
                GeoStateCoverage::query()->firstOrCreate(
                    ['geo_state_id' => $stateId],
                    [
                        'enabled' => (string) $code === '14',
                        'all_municipalities' => (string) $code === '14',
                    ],
                );
            }
        });

        GeographicCoverageService::flushCache();
    }
}

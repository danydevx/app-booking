<?php

namespace Modules\Listings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Listings\Support\CommercialServiceCatalog;
use Tests\TestCase;

class CommercialServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_is_independent_from_characteristics(): void
    {
        $groups = CommercialServiceCatalog::groups();
        $all = CommercialServiceCatalog::all();

        $this->assertCount(5, $groups);
        $this->assertCount(33, $all);
        $this->assertSame(count($all), count(array_unique(array_keys($all))));

        $this->assertSame('Alimentos y bebidas', $groups[0]['label']);
        $this->assertSame('Equipamiento adicional', $groups[4]['label']);

        foreach ([
            'catering',
            'taquizas',
            'full_catering',
            'drinks_bar',
            'bartender',
            'ice_and_drinks',
            'candy_bar',
            'dessert_table',
            'decoration',
            'themed_decoration',
            'floral_arrangements',
            'table_setup',
            'centerpieces',
            'balloon_decoration',
            'dj',
            'live_music',
            'entertainers',
            'kids_shows',
            'karaoke',
            'bounce_house_rental',
            'waiters',
            'cleaning_staff',
            'security_staff',
            'event_coordinator',
            'kitchen_staff',
            'furniture_rental',
            'linen_rental',
            'sound_rental',
            'lighting_rental',
            'av_rental',
            'tent_rental',
        ] as $key) {
            $this->assertArrayHasKey($key, $all);
        }
    }

    public function test_legacy_service_keys_keep_their_labels(): void
    {
        $this->assertSame('Hospedaje', CommercialServiceCatalog::label('lodging'));
        $this->assertSame('Habitaciones', CommercialServiceCatalog::label('rooms'));
        $this->assertSame('DJ', CommercialServiceCatalog::label('dj'));
    }

    public function test_groups_do_not_contain_duplicate_services(): void
    {
        $grouped = [];

        foreach (CommercialServiceCatalog::groups() as $group) {
            $this->assertNotSame([], $group['services']);

            foreach ($group['services'] as $service) {
                $grouped[] = $service['value'];
            }
        }

        $this->assertSame($grouped, array_values(array_unique($grouped)));
    }
}

<?php

namespace Modules\Listings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Listings\Models\ListingEventType;
use Modules\Listings\Support\ListingImportNormalizer;
use Tests\TestCase;

class ListingImportNormalizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_spanish_columns_catalogs_and_legacy_aliases(): void
    {
        $rows = [[
            'Nombre' => 'Terraza Uno',
            'Aforo' => '1,200',
            'Descripción' => 'Terraza para eventos.',
            'Tipos de evento' => 'boda|Corporativo|inventado',
            'Características' => 'Estacionamiento|Wi-Fi',
            'Referencia' => 'terraza-uno',
        ]];

        $result = app(ListingImportNormalizer::class)->normalize($rows, 2);

        $this->assertCount(1, $result);

        $row = $result[0];

        $this->assertSame([], $row['errors']);
        $this->assertSame(2, $row['line']);
        $this->assertSame('Terraza Uno', $row['data']['name']);
        $this->assertSame(1200, $row['data']['capacity']);
        $this->assertSame('Terraza para eventos.', $row['data']['description']);
        $this->assertSame('terraza-uno', $row['data']['external_ref']);
        $this->assertSame(['bodas', 'eventos-corporativos'], $row['data']['event_types']);
        $this->assertSame(['parking', 'wifi'], $row['data']['services']);
        $this->assertContains('Tipo de evento desconocido: inventado.', $row['warnings']);
    }

    public function test_it_reports_a_missing_title_and_invalid_capacity(): void
    {
        $rows = [[
            'titulo' => '',
            'capacidad' => 'muchas',
        ]];

        $row = app(ListingImportNormalizer::class)->normalize($rows)[0];

        $this->assertContains('Cada terraza necesita un título.', $row['errors']);
        $this->assertContains('La capacidad debe ser un número entero.', $row['errors']);
    }

    public function test_it_maps_social_links_and_warns_about_invalid_networks(): void
    {
        $rows = [
            [
                'titulo' => 'Terraza Redes',
                'facebook' => 'facebook.com/terraza',
                'instagram_url' => 'https://instagram.com/terraza',
                'tiktok' => 'https://tiktok.com/@terraza',
                'google_maps' => 'maps.app.goo.gl/abc',
            ],
            [
                'titulo' => 'Terraza Inválida',
                'facebook' => 'https://instagram.com/terraza',
            ],
        ];

        $result = app(ListingImportNormalizer::class)->normalize($rows);

        $this->assertSame('https://facebook.com/terraza', $result[0]['data']['facebook_url']);
        $this->assertSame('https://instagram.com/terraza', $result[0]['data']['instagram_url']);
        $this->assertSame('https://tiktok.com/@terraza', $result[0]['data']['tiktok_url']);
        $this->assertSame('https://maps.app.goo.gl/abc', $result[0]['data']['google_maps_url']);

        $this->assertArrayNotHasKey('facebook_url', $result[1]['data']);
        $this->assertContains('El enlace de Facebook no es válido; se omitió.', $result[1]['warnings']);
    }

    public function test_it_accepts_json_arrays_and_numeric_event_type_ids(): void
    {
        $weddings = ListingEventType::query()
            ->where('slug', 'bodas')
            ->firstOrFail();

        $rows = [[
            'titulo' => 'Terraza JSON',
            'tipos_evento' => json_encode([(string) $weddings->id, 'Cumpleaños']),
            'caracteristicas' => json_encode(['wifi', 'Jardín']),
        ]];

        $row = app(ListingImportNormalizer::class)->normalize($rows)[0];

        $this->assertSame([], $row['errors']);
        $this->assertSame(['bodas', 'cumpleanos'], $row['data']['event_types']);
        $this->assertSame(['wifi', 'garden'], $row['data']['services']);
    }
}

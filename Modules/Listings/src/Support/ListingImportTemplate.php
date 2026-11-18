<?php

namespace Modules\Listings\Support;

/**
 * Downloadable examples for the platform listing importer.
 */
final class ListingImportTemplate
{
    /**
     * @return array<int, string>
     */
    public static function columns(): array
    {
        return [
            'titulo',
            'capacidad',
            'descripcion',
            'tipos_evento',
            'caracteristicas',
            'external_ref',
            'direccion',
            'municipio',
            'estado',
            'reglas',
            'contacto_email',
            'facebook',
            'instagram',
            'tiktok',
            'google_maps',
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function exampleRows(): array
    {
        return [
            [
                'Jardín Los Encinos',
                '150',
                'Terraza para eventos sociales y corporativos.',
                'bodas|eventos-corporativos',
                'parking|wifi|green_areas',
                'jardin-encinos',
                'Av. Vallarta 1234',
                'Guadalajara',
                'Jalisco',
                'No se permiten mascotas.',
                'contacto@encinos.test',
                'facebook.com/jardinlosencinos',
                'instagram.com/jardinlosencinos',
                'tiktok.com/@jardinlosencinos',
                'google.com/maps/place/Jardin+Los+Encinos',
            ],
            [
                'Terraza Mirador',
                '80',
                'Espacio techado con vista a la ciudad.',
                'cumpleanos|reuniones-familiares',
                'covered_terrace|bathroom|tables',
                'terraza-mirador',
                'Av. Chapultepec 10',
                'Guadalajara',
                'Jalisco',
                '',
                'hola@mirador.test',
                '',
                'instagram.com/terrazamirador',
                '',
                'maps.app.goo.gl/ejemplo',
            ],
        ];
    }

    public static function csv(): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return '';
        }

        fputcsv($stream, self::columns(), ',', '"', '');

        foreach (self::exampleRows() as $row) {
            fputcsv($stream, $row, ',', '"', '');
        }

        rewind($stream);
        $contents = (string) stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }

    public static function json(): string
    {
        return (string) json_encode([
            'listings' => [
                [
                    'name' => 'Jardín Los Encinos',
                    'capacity' => 150,
                    'description' => 'Terraza para eventos sociales y corporativos.',
                    'event_types' => ['bodas', 'eventos-corporativos'],
                    'services' => ['parking', 'wifi', 'green_areas'],
                    'external_ref' => 'jardin-encinos',
                    'address' => 'Av. Vallarta 1234',
                    'city' => 'Guadalajara',
                    'state' => 'Jalisco',
                    'contact_email' => 'contacto@encinos.test',
                    'facebook_url' => 'https://facebook.com/jardinlosencinos',
                    'instagram_url' => 'https://instagram.com/jardinlosencinos',
                    'tiktok_url' => 'https://tiktok.com/@jardinlosencinos',
                    'google_maps_url' => 'https://maps.app.goo.gl/ejemplo',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

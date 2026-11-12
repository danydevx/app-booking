<?php

namespace Modules\Listings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Listings\Models\ListingCharacteristic;
use Modules\Listings\Models\ListingEventType;

class ListingCatalogSeeder extends Seeder
{
    /**
     * Idempotent: existing labels, ordering and active flags are preserved so
     * Superadmin changes are never overwritten by repeated deployments.
     */
    public function run(): void
    {
        $eventTypes = [
            ['slug' => 'bodas', 'name' => 'Bodas', 'description' => 'Ceremonias y recepciones de boda.', 'icon' => 'heart'],
            ['slug' => 'xv-anos', 'name' => 'XV años', 'description' => 'Celebraciones de quinceañera.', 'icon' => 'star'],
            ['slug' => 'bautizos', 'name' => 'Bautizos', 'description' => 'Reuniones familiares para bautizo.', 'icon' => 'droplets'],
            ['slug' => 'cumpleanos', 'name' => 'Cumpleaños', 'description' => 'Festejos de cumpleaños para todas las edades.', 'icon' => 'cake'],
            ['slug' => 'graduaciones', 'name' => 'Graduaciones', 'description' => 'Festejos de fin de estudios.', 'icon' => 'graduation-cap'],
            ['slug' => 'eventos-corporativos', 'name' => 'Eventos corporativos', 'description' => 'Convivios, presentaciones y eventos de empresa.', 'icon' => 'briefcase'],
            ['slug' => 'primera-comunion', 'name' => 'Primera comunión', 'description' => 'Celebraciones de primera comunión.', 'icon' => 'church'],
            ['slug' => 'baby-shower', 'name' => 'Baby shower', 'description' => 'Reuniones para celebrar la llegada de un bebé.', 'icon' => 'baby'],
            ['slug' => 'despedidas-de-soltera', 'name' => 'Despedidas de soltera', 'description' => 'Reuniones previas a una boda.', 'icon' => 'sparkles'],
            ['slug' => 'aniversarios', 'name' => 'Aniversarios', 'description' => 'Celebraciones de aniversarios y fechas especiales.', 'icon' => 'gem'],
            ['slug' => 'reuniones-familiares', 'name' => 'Reuniones familiares', 'description' => 'Encuentros y convivios familiares.', 'icon' => 'users'],
            ['slug' => 'fiestas-infantiles', 'name' => 'Fiestas infantiles', 'description' => 'Fiestas y actividades para niñas y niños.', 'icon' => 'party-popper'],
            ['slug' => 'otros-eventos', 'name' => 'Otros eventos', 'description' => 'Otros tipos de celebraciones.', 'icon' => 'calendar'],
        ];

        foreach ($eventTypes as $index => $eventType) {
            ListingEventType::query()->firstOrCreate(
                ['slug' => $eventType['slug']],
                [
                    'name' => $eventType['name'],
                    'description' => $eventType['description'],
                    'icon' => $eventType['icon'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        $characteristics = [
            ['service_key' => 'garden', 'description' => 'Espacio acondicionado al aire libre.', 'icon' => 'trees'],
            ['service_key' => 'pool', 'description' => 'Para disfrutar días de calor.', 'icon' => 'waves'],
            ['service_key' => 'covered_terrace', 'description' => 'Espacio protegido del sol y la lluvia.', 'icon' => 'home'],
            ['service_key' => 'green_areas', 'description' => 'Naturaleza, vegetación y sombra.', 'icon' => 'sprout'],
        ];

        foreach ($characteristics as $index => $characteristic) {
            ListingCharacteristic::query()->firstOrCreate(
                ['service_key' => $characteristic['service_key']],
                [
                    'description' => $characteristic['description'],
                    'icon' => $characteristic['icon'],
                    'is_featured' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}

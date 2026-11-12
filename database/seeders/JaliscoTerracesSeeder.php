<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Enums\ListingServicePriceUnit;
use Modules\Listings\Enums\ListingServicePricingMode;
use Modules\Listings\Enums\ListingServiceProvider;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingEventType;
use RuntimeException;

/**
 * @phpstan-type SeederProfile array{
 *     owner: array{name: string, email: string},
 *     team: string,
 *     color: string,
 *     event_types: array<int, string>,
 *     listing: array<string, mixed>
 * }
 */
class JaliscoTerracesSeeder extends Seeder
{
    /**
     * @var array<int, SeederProfile>
     */
    private array $profiles = [
        [
            'owner' => ['name' => 'Lucía Mendoza', 'email' => 'lucia.mendoza@terrazas.test'],
            'team' => 'Jardín Los Colomos',
            'color' => '0f766e',
            'event_types' => ['bodas', 'xv-anos', 'cumpleanos', 'eventos-corporativos', 'reuniones-familiares', 'aniversarios'],
            'listing' => [
                'slug' => 'jardin-los-colomos',
                'name' => 'Jardín Los Colomos',
                'description' => 'Un jardín de 1,800 m² rodeado de árboles centenarios en la zona de Los Colomos. Ideal para bodas al aire libre, recepciones familiares y eventos corporativos con montaje a medida.',
                'address' => 'Av. Patria 1200, Lomas del Bosque',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'postal_code' => '44670',
                'latitude' => 20.6973,
                'longitude' => -103.4098,
                'capacity' => 180,
                'weekday_cents' => 2800000,
                'weekend_cents' => 3400000,
                'included_people' => 120,
                'services' => ['garden', 'green_areas', 'covered_terrace', 'wifi', 'bathroom', 'parking', 'tables', 'lighting', 'sound', 'kitchen', 'accessible'],
                'details' => ['space_type' => 'garden', 'access' => 'Entrada independiente por Av. Patria', 'parking' => '40 cajones privados', 'view' => 'Jardín arbolado con fuente'],
                'rules' => 'Música hasta las 23:30. No se permite pirotecnia. Montaje desde las 09:00.',
            ],
        ],
        [
            'owner' => ['name' => 'Diego Álvarez', 'email' => 'diego.alvarez@terrazas.test'],
            'team' => 'Terraza Andares',
            'color' => '1d4ed8',
            'event_types' => ['bodas', 'eventos-corporativos', 'aniversarios', 'graduaciones', 'despedidas-de-soltera'],
            'listing' => [
                'slug' => 'terraza-andares',
                'name' => 'Terraza Andares',
                'description' => 'Terraza contemporánea con alberca, barra y vista abierta sobre la zona de Andares. Un espacio versátil para lanzamientos, cenas privadas y celebraciones de empresa.',
                'address' => 'Blvd. Puerta de Hierro 4965, Puerta de Hierro',
                'city' => 'Zapopan',
                'state' => 'Jalisco',
                'postal_code' => '45116',
                'latitude' => 20.7214,
                'longitude' => -103.3918,
                'capacity' => 120,
                'weekday_cents' => 3500000,
                'weekend_cents' => 4200000,
                'included_people' => 80,
                'services' => ['covered_terrace', 'pool', 'wifi', 'parking', 'bathroom', 'tables', 'lighting', 'sound', 'air_conditioning', 'bar', 'lounge_chairs'],
                'details' => ['space_type' => 'rooftop', 'access' => 'Elevador privado y acceso vial', 'parking' => '25 cajones con valet', 'view' => 'Panorámica de Puerta de Hierro'],
                'rules' => 'Uso de alberca hasta las 22:00. No se permite vidrio en la zona de la piscina.',
            ],
        ],
        [
            'owner' => ['name' => 'Mariana Cárdenas', 'email' => 'mariana.cardenas@terrazas.test'],
            'team' => 'Casa Tlaquepaque',
            'color' => 'b45309',
            'event_types' => ['bodas', 'bautizos', 'primera-comunion', 'cumpleanos', 'reuniones-familiares'],
            'listing' => [
                'slug' => 'casa-tlaquepaque',
                'name' => 'Casa Tlaquepaque',
                'description' => 'Casona de estilo colonial con patio central, buganvilias y salones interiores. Perfecta para celebraciones íntimas, bautizos y reuniones familiares con encanto artesanal.',
                'address' => 'Calle Independencia 88, Centro',
                'city' => 'San Pedro Tlaquepaque',
                'state' => 'Jalisco',
                'postal_code' => '45500',
                'latitude' => 20.6400,
                'longitude' => -103.3100,
                'capacity' => 90,
                'weekday_cents' => 1850000,
                'weekend_cents' => 2250000,
                'included_people' => 60,
                'services' => ['garden', 'green_areas', 'covered_terrace', 'wifi', 'bathroom', 'tables', 'lighting', 'parking', 'kitchen', 'accessible'],
                'details' => ['space_type' => 'patio', 'access' => 'Acceso por zaguán principal', 'parking' => '12 cajones', 'view' => 'Patio colonial con fuente'],
                'rules' => 'Eventos hasta la 01:00. Se permite catering propio con registro previo.',
            ],
        ],
        [
            'owner' => ['name' => 'Ricardo Ochoa', 'email' => 'ricardo.ochoa@terrazas.test'],
            'team' => 'Mirador Tonalá',
            'color' => '7c3aed',
            'event_types' => ['eventos-corporativos', 'graduaciones', 'cumpleanos', 'aniversarios', 'otros-eventos'],
            'listing' => [
                'slug' => 'mirador-tonala',
                'name' => 'Mirador Tonalá',
                'description' => 'Terraza techada en altura con vista al valle y al taller artesanal de Tonalá. Un escenario flexible para presentaciones, graduaciones y celebraciones privadas.',
                'address' => 'Calle Morelos 315, Col. Centro',
                'city' => 'Tonalá',
                'state' => 'Jalisco',
                'postal_code' => '45400',
                'latitude' => 20.6244,
                'longitude' => -103.2439,
                'capacity' => 150,
                'weekday_cents' => 2200000,
                'weekend_cents' => 2700000,
                'included_people' => 100,
                'services' => ['covered_terrace', 'green_areas', 'wifi', 'parking', 'bathroom', 'sound', 'lighting', 'tables', 'accessible', 'rooftop'],
                'details' => ['space_type' => 'rooftop', 'access' => 'Elevador y escaleras', 'parking' => '30 cajones', 'view' => 'Valle y cerros de Tonalá'],
                'rules' => 'Música hasta las 00:00. Montaje permitido desde las 08:00.',
            ],
        ],
        [
            'owner' => ['name' => 'Fernanda Rojas', 'email' => 'fernanda.rojas@terrazas.test'],
            'team' => 'Hacienda Tlajomulco',
            'color' => '047857',
            'event_types' => ['bodas', 'xv-anos', 'eventos-corporativos', 'aniversarios', 'reuniones-familiares'],
            'listing' => [
                'slug' => 'hacienda-tlajomulco',
                'name' => 'Hacienda Tlajomulco',
                'description' => 'Hacienda campestre con amplios jardines, alberca y áreas de hospedaje. Diseñada para bodas de gran formato, retiros de empresa y celebraciones de varios días.',
                'address' => 'Camino Real a Tlajomulco 2450, La Teja',
                'city' => 'Tlajomulco de Zúñiga',
                'state' => 'Jalisco',
                'postal_code' => '45640',
                'latitude' => 20.4736,
                'longitude' => -103.4469,
                'capacity' => 250,
                'weekday_cents' => 4200000,
                'weekend_cents' => 5200000,
                'included_people' => 200,
                'services' => ['garden', 'pool', 'green_areas', 'covered_terrace', 'parking', 'bathroom', 'kitchen', 'tables', 'lighting', 'sound', 'lodging', 'rooms', 'accessible'],
                'details' => ['space_type' => 'garden', 'access' => 'Acceso controlado por caseta', 'parking' => '80 cajones', 'view' => 'Jardines y alberca central'],
                'rules' => 'Hospedaje máximo para 30 personas. Eventos hasta las 02:00 con permiso municipal.',
            ],
        ],
        [
            'owner' => ['name' => 'Sofía Ballesteros', 'email' => 'sofia.ballesteros@terrazas.test'],
            'team' => 'Terraza Marina Vallarta',
            'color' => '0e7490',
            'event_types' => ['bodas', 'aniversarios', 'eventos-corporativos', 'despedidas-de-soltera', 'baby-shower'],
            'listing' => [
                'slug' => 'terraza-marina-vallarta',
                'name' => 'Terraza Marina Vallarta',
                'description' => 'Terraza frente a la marina con alberca, camastros y brisa del Pacífico. Ideal para cenas de gala, despedidas y eventos corporativos con ambiente de playa.',
                'address' => 'Paseo de la Marina 245, Marina Vallarta',
                'city' => 'Puerto Vallarta',
                'state' => 'Jalisco',
                'postal_code' => '48354',
                'latitude' => 20.6534,
                'longitude' => -105.2253,
                'capacity' => 100,
                'weekday_cents' => 3800000,
                'weekend_cents' => 4600000,
                'included_people' => 70,
                'services' => ['pool', 'covered_terrace', 'wifi', 'parking', 'bathroom', 'tables', 'lighting', 'sound', 'air_conditioning', 'lounge_chairs', 'showers'],
                'details' => ['space_type' => 'terrace', 'access' => 'Acceso peatonal y valet', 'parking' => '20 cajones', 'view' => 'Marina y atardecer'],
                'rules' => 'Música hasta las 23:00. Prohibido el acceso a la alberca después de las 22:00.',
            ],
        ],
        [
            'owner' => ['name' => 'Jorge Villalobos', 'email' => 'jorge.villalobos@terrazas.test'],
            'team' => 'Jardín Chapala',
            'color' => '15803d',
            'event_types' => ['bodas', 'cumpleanos', 'reuniones-familiares', 'baby-shower', 'bautizos'],
            'listing' => [
                'slug' => 'jardin-chapala',
                'name' => 'Jardín Chapala',
                'description' => 'Jardín a la orilla del lago de Chapala con pérgola, alberca y áreas verdes. Un refugio tranquilo para bodas, cumpleaños y reuniones familiares de fin de semana.',
                'address' => 'Paseo del Lago 410, La Floresta',
                'city' => 'Chapala',
                'state' => 'Jalisco',
                'postal_code' => '45900',
                'latitude' => 20.2962,
                'longitude' => -103.1912,
                'capacity' => 130,
                'weekday_cents' => 2400000,
                'weekend_cents' => 2900000,
                'included_people' => 90,
                'services' => ['garden', 'green_areas', 'pool', 'covered_terrace', 'wifi', 'parking', 'kitchen', 'tables', 'bathroom', 'grill', 'pergola'],
                'details' => ['space_type' => 'garden', 'access' => 'Entrada directa desde Paseo del Lago', 'parking' => '35 cajones', 'view' => 'Lago de Chapala y jardines'],
                'rules' => 'No se permite nadar después del atardecer. Música hasta las 23:00.',
            ],
        ],
        [
            'owner' => ['name' => 'Paola Gutiérrez', 'email' => 'paola.gutierrez@terrazas.test'],
            'team' => 'Cava Tequila',
            'color' => '92400e',
            'event_types' => ['eventos-corporativos', 'aniversarios', 'graduaciones', 'despedidas-de-soltera', 'otros-eventos'],
            'listing' => [
                'slug' => 'cava-tequila',
                'name' => 'Cava Tequila',
                'description' => 'Patio techado entre caves y paisaje agavero, con barra de degustación y luz cálida. Perfecto para catas privadas, cenas de empresa y celebraciones con identidad jalisciense.',
                'address' => 'Calle José Cuervo 72, Centro',
                'city' => 'Tequila',
                'state' => 'Jalisco',
                'postal_code' => '46400',
                'latitude' => 20.8818,
                'longitude' => -103.8356,
                'capacity' => 80,
                'weekday_cents' => 1950000,
                'weekend_cents' => 2400000,
                'included_people' => 50,
                'services' => ['covered_terrace', 'green_areas', 'wifi', 'bathroom', 'tables', 'lighting', 'parking', 'bar', 'sound'],
                'details' => ['space_type' => 'patio', 'access' => 'Acceso por calle principal', 'parking' => '15 cajones', 'view' => 'Paisaje agavero y cava'],
                'rules' => 'Consumo de tequila solo con servicio autorizado. Eventos hasta las 01:00.',
            ],
        ],
        [
            'owner' => ['name' => 'Andrés Navarro', 'email' => 'andres.navarro@terrazas.test'],
            'team' => 'Terraza Tepatitlán',
            'color' => '4338ca',
            'event_types' => ['bodas', 'xv-anos', 'cumpleanos', 'fiestas-infantiles', 'reuniones-familiares'],
            'listing' => [
                'slug' => 'terraza-tepatitlan',
                'name' => 'Terraza Tepatitlán',
                'description' => 'Terraza amplia con jardín, juegos infantiles y zona techada para todas las edades. Pensada para cumpleaños, XV años y reuniones familiares de fin de semana.',
                'address' => 'Av. Dr. Luis F. Nájera 620, Centro',
                'city' => 'Tepatitlán de Morelos',
                'state' => 'Jalisco',
                'postal_code' => '47600',
                'latitude' => 20.8172,
                'longitude' => -102.7635,
                'capacity' => 160,
                'weekday_cents' => 2100000,
                'weekend_cents' => 2600000,
                'included_people' => 110,
                'services' => ['covered_terrace', 'garden', 'green_areas', 'wifi', 'parking', 'bathroom', 'sound', 'lighting', 'tables', 'accessible', 'playground'],
                'details' => ['space_type' => 'terrace', 'access' => 'Acceso por Av. Nájera', 'parking' => '45 cajones', 'view' => 'Jardín y área infantil'],
                'rules' => 'Brincolín y juegos infantiles incluidos hasta las 20:00. Música hasta las 00:00.',
            ],
        ],
        [
            'owner' => ['name' => 'Valeria Ibarra', 'email' => 'valeria.ibarra@terrazas.test'],
            'team' => 'Casona Lagos',
            'color' => '9f1239',
            'event_types' => ['bodas', 'xv-anos', 'bautizos', 'primera-comunion', 'aniversarios', 'eventos-corporativos'],
            'listing' => [
                'slug' => 'casona-lagos',
                'name' => 'Casona Lagos',
                'description' => 'Casona de campo con patio empedrado, jardín, alberca y habitaciones para invitados. Una sede completa para bodas, XV años y celebraciones de varios días en Los Altos.',
                'address' => 'Camino a la Presa 1500, El Refugio',
                'city' => 'Lagos de Moreno',
                'state' => 'Jalisco',
                'postal_code' => '47400',
                'latitude' => 21.3567,
                'longitude' => -101.9379,
                'capacity' => 200,
                'weekday_cents' => 3000000,
                'weekend_cents' => 3600000,
                'included_people' => 150,
                'services' => ['garden', 'pool', 'green_areas', 'covered_terrace', 'wifi', 'parking', 'kitchen', 'tables', 'lighting', 'lodging', 'rooms', 'accessible', 'grill'],
                'details' => ['space_type' => 'garden', 'access' => 'Acceso por camino privado', 'parking' => '60 cajones', 'view' => 'Patio de casona y jardín'],
                'rules' => 'Hospedaje para 24 personas. Eventos hasta las 02:00 con permiso municipal.',
            ],
        ],
    ];

    public function run(): void
    {
        $eventTypes = ListingEventType::query()->pluck('id', 'slug');

        foreach ($this->profiles as $index => $profile) {
            $user = $this->userFor($profile);
            $team = $this->teamFor($user, (string) $profile['team']);
            $listing = Listing::query()
                ->where('team_id', $team->id)
                ->where('slug', $profile['listing']['slug'])
                ->first();

            if (! $listing) {
                $listing = Listing::factory()
                    ->forTeam($team)
                    ->published()
                    ->create($this->listingAttributes($profile, $user, $index));
            }

            $this->syncEventTypes($listing, $profile, $eventTypes);
            $this->seedMedia($listing, $profile);
            $this->seedPackages($listing, $profile);
            $this->seedCommercialServices($listing, $profile);
            $this->seedPromotions($listing, $profile);
            $this->seedPriceRanges($listing, $profile);
            $this->seedAvailability($listing, $user);
        }
    }

    /**
     * @param  SeederProfile  $profile
     */
    private function userFor(array $profile): User
    {
        $user = User::query()
            ->where('email', $profile['owner']['email'])
            ->first();

        if ($user) {
            return $user;
        }

        return User::factory()->create([
            'name' => $profile['owner']['name'],
            'email' => $profile['owner']['email'],
        ]);
    }

    private function teamFor(User $user, string $name): Team
    {
        $team = $user->currentTeam;

        if (! $team) {
            $team = Team::factory()->personal()->create(['name' => $name]);
            $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
            $user->switchTeam($team);

            return $team;
        }

        if ($user->wasRecentlyCreated) {
            $team->update(['name' => $name]);
        }

        return $team;
    }

    /**
     * @param  SeederProfile  $profile
     * @return array<string, mixed>
     */
    private function listingAttributes(array $profile, User $user, int $index): array
    {
        $data = $profile['listing'];
        $weekday = (int) $data['weekday_cents'];
        $weekend = (int) $data['weekend_cents'];
        unset($data['weekday_cents'], $data['weekend_cents']);

        $daily = $this->dailyPricing($weekday, $weekend);
        $phone = '+52 33 1234 56'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);

        return [
            ...$data,
            'type' => 'terrace',
            'country' => 'México',
            'timezone' => 'America/Mexico_City',
            'currency' => 'MXN',
            'price_from_cents' => min($weekday, $weekend),
            'price_weekday_cents' => $weekday,
            'price_weekend_cents' => $weekend,
            'price_monday_cents' => $weekday,
            'price_tuesday_cents' => $weekday,
            'price_wednesday_cents' => $weekday,
            'price_thursday_cents' => $weekday,
            'price_friday_cents' => $weekend,
            'price_saturday_cents' => $weekend,
            'price_sunday_cents' => $weekend,
            'daily_pricing' => $daily,
            'included_hours' => 8,
            'included_people' => (int) $data['included_people'],
            'extra_hour_cents' => 250000,
            'extra_person_cents' => 15000,
            'deposit_required_cents' => (int) round($weekday * 0.2),
            'price_notes' => 'Precio base por evento de 8 horas. El anticipo confirma la fecha y se descuenta del total.',
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'contact_phone' => $phone,
            'whatsapp_phone' => '52133123456'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * @param  SeederProfile  $profile
     * @param  Collection<string, int>  $eventTypes
     */
    private function syncEventTypes(Listing $listing, array $profile, Collection $eventTypes): void
    {
        $ids = collect($profile['event_types'])
            ->map(fn (string $slug): mixed => $eventTypes[$slug] ?? null)
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($ids !== []) {
            $listing->eventTypes()->syncWithoutDetaching($ids);
        }
    }

    /**
     * @param  SeederProfile  $profile
     */
    private function seedMedia(Listing $listing, array $profile): void
    {
        if ($listing->images()->exists()) {
            return;
        }

        $disk = (string) config('listings.media.disk', 'public');
        $colors = $this->colorVariants((string) $profile['color']);

        foreach ($colors as $index => $color) {
            $path = "listings/{$listing->id}/seed-".($index + 1).'.png';
            $binary = $this->placeholderPng(
                (string) $profile['listing']['name'],
                'Jalisco · Foto '.($index + 1).' · Placeholder',
                $color,
            );

            if (! Storage::disk($disk)->put($path, $binary)) {
                throw new RuntimeException("No se pudo guardar la imagen placeholder en {$path}.");
            }

            $listing->media()->create([
                'type' => ListingMediaType::Image,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => 'image/png',
                'size_bytes' => strlen($binary),
                'width' => 1200,
                'height' => 800,
                'duration_seconds' => null,
                'alt_text' => $profile['listing']['name'].' - Foto '.($index + 1),
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  SeederProfile  $profile
     */
    private function seedPackages(Listing $listing, array $profile): void
    {
        $base = (int) $profile['listing']['weekday_cents'];
        $capacity = (int) $profile['listing']['capacity'];

        $templates = [
            [
                'name' => 'Esencial',
                'description' => 'Montaje base con mobiliario, iluminación y personal de apoyo para una celebración de seis horas.',
                'factor' => 0.65,
                'hours' => 6,
                'people' => 0.5,
                'services' => ['waiters', 'furniture_rental', 'lighting_rental'],
                'conditions' => 'Incluye montaje y desmontaje dentro del horario contratado.',
            ],
            [
                'name' => 'Celebración Premium',
                'description' => 'Experiencia completa con DJ, decoración floral, meseros y coordinación del evento.',
                'factor' => 0.9,
                'hours' => 8,
                'people' => 0.75,
                'services' => ['waiters', 'dj', 'decoration', 'event_coordinator', 'lighting_rental'],
                'conditions' => 'Anticipo del 30%. Cambios con 15 días de anticipación.',
            ],
            [
                'name' => 'Corporativo',
                'description' => 'Paquete para presentaciones y convivios de empresa con audio, proyector y coffee break.',
                'factor' => 1.1,
                'hours' => 10,
                'people' => 1,
                'services' => ['event_coordinator', 'av_rental', 'waiters', 'furniture_rental'],
                'conditions' => 'Requiere lista de invitados y montaje previo de proveedores.',
            ],
        ];

        foreach ($templates as $sort => $template) {
            $package = $listing->packages()->firstOrNew(['name' => $template['name']]);

            if ($package->exists) {
                continue;
            }

            $package->team_id = $listing->team_id;
            $package->fill([
                'description' => $template['description'],
                'price_cents' => (int) round($base * $template['factor']),
                'duration_hours' => $template['hours'],
                'included_people' => max(10, (int) round($capacity * $template['people'])),
                'included_services' => $template['services'],
                'conditions' => $template['conditions'],
                'active' => true,
                'sort_order' => $sort + 1,
            ]);
            $package->save();
        }
    }

    /**
     * @param  SeederProfile  $profile
     */
    private function seedCommercialServices(Listing $listing, array $profile): void
    {
        $services = [
            [
                'service_key' => 'catering',
                'description' => 'Menú de tres tiempos con estación de bebidas y servicio durante el evento.',
                'pricing_mode' => ListingServicePricingMode::Additional,
                'provider' => ListingServiceProvider::External,
                'price_cents' => 38000,
                'price_unit' => ListingServicePriceUnit::Person,
            ],
            [
                'service_key' => 'dj',
                'description' => 'DJ en vivo con equipo de audio profesional y lista musical personalizada.',
                'pricing_mode' => ListingServicePricingMode::Additional,
                'provider' => ListingServiceProvider::Own,
                'price_cents' => 450000,
                'price_unit' => ListingServicePriceUnit::Event,
            ],
            [
                'service_key' => 'decoration',
                'description' => 'Decoración temática con flores naturales, telas y centros de mesa.',
                'pricing_mode' => ListingServicePricingMode::Quote,
                'provider' => ListingServiceProvider::External,
                'price_cents' => null,
                'price_unit' => ListingServicePriceUnit::Event,
            ],
            [
                'service_key' => 'waiters',
                'description' => 'Equipo de meseros y personal de servicio durante todo el evento.',
                'pricing_mode' => ListingServicePricingMode::Additional,
                'provider' => ListingServiceProvider::External,
                'price_cents' => 180000,
                'price_unit' => ListingServicePriceUnit::Event,
            ],
            [
                'service_key' => 'event_coordinator',
                'description' => 'Coordinación del montaje, proveedores y tiempos del evento.',
                'pricing_mode' => ListingServicePricingMode::Included,
                'provider' => ListingServiceProvider::Own,
                'price_cents' => null,
                'price_unit' => ListingServicePriceUnit::Event,
            ],
            [
                'service_key' => 'lighting_rental',
                'description' => 'Iluminación ambiental y luces de pista para el montaje.',
                'pricing_mode' => ListingServicePricingMode::Additional,
                'provider' => ListingServiceProvider::External,
                'price_cents' => 120000,
                'price_unit' => ListingServicePriceUnit::Event,
            ],
        ];

        foreach ($services as $serviceData) {
            $service = $listing->commercialServices()->firstOrNew([
                'service_key' => $serviceData['service_key'],
            ]);

            if ($service->exists) {
                continue;
            }

            $service->team_id = $listing->team_id;
            $service->fill([
                ...$serviceData,
                'is_included' => $serviceData['pricing_mode'] === ListingServicePricingMode::Included,
                'active' => true,
            ]);
            $service->save();
        }
    }

    /**
     * @param  SeederProfile  $profile
     */
    private function seedPromotions(Listing $listing, array $profile): void
    {
        $weekday = (int) $profile['listing']['weekday_cents'];
        $weekend = (int) $profile['listing']['weekend_cents'];

        $promotions = [
            [
                'title' => 'Reserva entre semana',
                'description' => 'Un 12% de descuento al reservar de lunes a jueves.',
                'previous_price_cents' => $weekday,
                'promotional_price_cents' => (int) round($weekday * 0.88),
                'applies_on' => ['mon', 'tue', 'wed', 'thu'],
                'conditions' => 'Válido para nuevas reservas y fechas sujetas a disponibilidad.',
            ],
            [
                'title' => 'Fin de semana de celebración',
                'description' => 'Precio preferencial para eventos de viernes a domingo.',
                'previous_price_cents' => $weekend,
                'promotional_price_cents' => (int) round($weekend * 0.93),
                'applies_on' => ['fri', 'sat', 'sun'],
                'conditions' => 'No aplica en días festivos. Anticipo del 30%.',
            ],
        ];

        foreach ($promotions as $promotionData) {
            $promotion = $listing->promotions()->firstOrNew([
                'title' => $promotionData['title'],
            ]);

            if ($promotion->exists) {
                continue;
            }

            $promotion->team_id = $listing->team_id;
            $promotion->fill([
                ...$promotionData,
                'starts_at' => now()->subDays(2)->toDateString(),
                'ends_at' => now()->addDays(90)->toDateString(),
                'active' => true,
            ]);
            $promotion->save();
        }
    }

    /**
     * @param  SeederProfile  $profile
     */
    private function seedPriceRanges(Listing $listing, array $profile): void
    {
        $weekday = (int) $profile['listing']['weekday_cents'];
        $weekend = (int) $profile['listing']['weekend_cents'];
        $capacity = (int) $profile['listing']['capacity'];

        $ranges = [
            [
                'name' => 'Temporada alta',
                'starts_at' => now()->addDays(30)->toDateString(),
                'ends_at' => now()->addDays(120)->toDateString(),
                'price_cents' => $weekend,
                'daily' => $this->dailyPricing($weekend, $weekend),
                'conditions' => 'Incluye montaje y desmontaje. Sujeto a disponibilidad.',
            ],
            [
                'name' => 'Temporada baja',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'price_cents' => $weekday,
                'daily' => $this->dailyPricing($weekday, $weekday),
                'conditions' => 'Precio válido para eventos de lunes a jueves.',
            ],
        ];

        foreach ($ranges as $rangeData) {
            $range = $listing->priceRanges()->firstOrNew([
                'name' => $rangeData['name'],
            ]);

            if ($range->exists) {
                continue;
            }

            $range->team_id = $listing->team_id;
            $range->fill([
                'starts_at' => $rangeData['starts_at'],
                'ends_at' => $rangeData['ends_at'],
                'price_cents' => $rangeData['price_cents'],
                'daily_pricing' => $rangeData['daily'],
                'included_hours' => 8,
                'included_people' => min($capacity, 120),
                'extra_hour_cents' => 250000,
                'extra_person_cents' => 15000,
                'deposit_required_cents' => (int) round($rangeData['price_cents'] * 0.2),
                'conditions' => $rangeData['conditions'],
                'active' => true,
            ]);
            $range->save();
        }
    }

    private function seedAvailability(Listing $listing, User $user): void
    {
        foreach ([14, 28, 45] as $offset) {
            ListingUnavailability::query()->firstOrCreate(
                [
                    'listing_id' => $listing->id,
                    'date' => now()->addDays($offset)->toDateString(),
                ],
                [
                    'status' => AvailabilityStatus::Blocked,
                    'reason' => 'Mantenimiento programado del espacio.',
                    'created_by' => $user->id,
                ],
            );
        }
    }

    /**
     * @return array<string, array{hours: int, entry_time: string, exit_time: string, price_cents: int}>
     */
    private function dailyPricing(int $weekday, int $weekend): array
    {
        $daily = [];

        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $daily[$day] = [
                'hours' => 8,
                'entry_time' => '10:00',
                'exit_time' => '22:00',
                'price_cents' => in_array($day, ['friday', 'saturday', 'sunday'], true)
                    ? $weekend
                    : $weekday,
            ];
        }

        return $daily;
    }

    /**
     * @return array<int, string>
     */
    private function colorVariants(string $hex): array
    {
        return [
            $this->adjustColor($hex, 0),
            $this->adjustColor($hex, 26),
            $this->adjustColor($hex, -24),
            $this->adjustColor($hex, 44),
        ];
    }

    private function adjustColor(string $hex, int $delta): string
    {
        $hex = ltrim($hex, '#');
        $red = $this->clamp(hexdec(substr($hex, 0, 2)) + $delta);
        $green = $this->clamp(hexdec(substr($hex, 2, 2)) + $delta);
        $blue = $this->clamp(hexdec(substr($hex, 4, 2)) + $delta);

        return sprintf('%02x%02x%02x', $red, $green, $blue);
    }

    private function clamp(int|float $value): int
    {
        return max(0, min(255, (int) $value));
    }

    private function placeholderPng(string $title, string $subtitle, string $hex): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('La extensión GD es necesaria para generar las imágenes placeholder.');
        }

        $width = 1200;
        $height = 800;
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new RuntimeException('No se pudo crear la imagen placeholder.');
        }

        $background = $this->allocateColor($image, $hex);
        $dark = $this->allocateColor($image, $this->adjustColor($hex, -40));
        $light = $this->allocateColor($image, $this->adjustColor($hex, 55));
        $white = $this->allocateColor($image, 'ffffff');

        imagefill($image, 0, 0, $background);
        imagefilledellipse($image, 1030, 95, 420, 420, $light);
        imagefilledrectangle($image, 0, 560, $width, $height, $dark);

        $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

        if (is_file($font)) {
            $lines = explode("\n", wordwrap($title, 28, "\n", true));
            $y = 300;

            foreach ($lines as $line) {
                imagettftext($image, 40, 0, 70, $y, $white, $font, $line);
                $y += 58;
            }

            imagettftext($image, 22, 0, 70, 660, $white, $font, $subtitle);
        } else {
            imagestring($image, 5, 70, 330, $title, $white);
            imagestring($image, 4, 70, 660, $subtitle, $white);
        }

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (! is_string($binary)) {
            throw new RuntimeException('No se pudo generar el PNG placeholder.');
        }

        return $binary;
    }

    private function allocateColor(\GdImage $image, string $hex): int
    {
        $hex = ltrim($hex, '#');
        $red = max(0, min(255, (int) hexdec(substr($hex, 0, 2))));
        $green = max(0, min(255, (int) hexdec(substr($hex, 2, 2))));
        $blue = max(0, min(255, (int) hexdec(substr($hex, 4, 2))));

        $color = imagecolorallocate($image, $red, $green, $blue);

        if ($color === false) {
            throw new RuntimeException('No se pudo asignar un color a la imagen placeholder.');
        }

        return $color;
    }
}

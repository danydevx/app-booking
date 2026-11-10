<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\Listing;
use RuntimeException;

class DemoUsersWithListingsSeeder extends Seeder
{
    /**
     * @var array<int, array{
     *     name: string,
     *     email: string,
     *     team: string,
     *     listing: array<string, mixed>,
     *     image_text: string
     * }>
     */
    private array $profiles = [
        [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@terrazas.test',
            'team' => 'Terraza Ana Torres',
            'listing' => [
                'name' => 'Terraza Jardín Roma',
                'slug' => 'terraza-jardin-roma',
                'description' => 'Un jardín privado en el corazón de Roma Norte, ideal para cenas y celebraciones al aire libre.',
                'address' => 'Calle Córdoba 45',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'postal_code' => '06700',
                'latitude' => 19.4194,
                'longitude' => -99.1618,
                'capacity' => 80,
                'services' => ['wifi', 'bathroom', 'kitchen', 'tables', 'lighting', 'grill'],
                'details' => ['space_type' => 'garden', 'access' => 'Entrada independiente', 'parking' => '3 cajones', 'view' => 'Jardín interior'],
                'rules' => 'No se permiten mascotas. Música hasta las 23:00.',
            ],
            'image_text' => 'Terraza Jardin Roma',
        ],
        [
            'name' => 'Bruno Salinas',
            'email' => 'bruno.salinas@terrazas.test',
            'team' => 'Terraza Bruno Salinas',
            'listing' => [
                'name' => 'Azotea Condesa',
                'slug' => 'azotea-condesa',
                'description' => 'Rooftop con vista a los árboles de la Condesa, perfecto para eventos sociales y after office.',
                'address' => 'Avenida Ámsterdam 120',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'postal_code' => '06100',
                'latitude' => 19.4120,
                'longitude' => -99.1730,
                'capacity' => 60,
                'services' => ['wifi', 'bathroom', 'sound', 'lighting', 'tables', 'heating'],
                'details' => ['space_type' => 'rooftop', 'access' => 'Elevador', 'parking' => 'No incluido', 'view' => 'Panorámica'],
                'rules' => 'Máximo 60 personas. Eventos hasta las 02:00 con permiso.',
            ],
            'image_text' => 'Azotea Condesa',
        ],
        [
            'name' => 'Carla Méndez',
            'email' => 'carla.mendez@terrazas.test',
            'team' => 'Terraza Carla Méndez',
            'listing' => [
                'name' => 'Patio Coyoacán',
                'slug' => 'patio-coyoacan',
                'description' => 'Patio colonial rodeado de buganvilias, a unos pasos del centro de Coyoacán.',
                'address' => 'Calle Francisco Sosa 180',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'postal_code' => '04000',
                'latitude' => 19.3467,
                'longitude' => -99.1617,
                'capacity' => 100,
                'services' => ['wifi', 'bathroom', 'kitchen', 'parking', 'tables', 'accessible'],
                'details' => ['space_type' => 'patio', 'access' => 'Acceso por zaguán', 'parking' => '5 cajones', 'view' => 'Patio colonial'],
                'rules' => 'Montaje y desmontaje dentro del horario rentado.',
            ],
            'image_text' => 'Patio Coyoacan',
        ],
        [
            'name' => 'Diego Ramírez',
            'email' => 'diego.ramirez@terrazas.test',
            'team' => 'Terraza Diego Ramírez',
            'listing' => [
                'name' => 'Terraza Polanco',
                'slug' => 'terraza-polanco',
                'description' => 'Terraza contemporánea con alberca y asador en una de las zonas más exclusivas de la ciudad.',
                'address' => 'Calle Julio Verne 22',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'postal_code' => '11560',
                'latitude' => 19.4330,
                'longitude' => -99.2000,
                'capacity' => 50,
                'services' => ['wifi', 'bathroom', 'pool', 'grill', 'sound', 'lighting', 'tables'],
                'details' => ['space_type' => 'terrace', 'access' => 'Elevador privado', 'parking' => '2 cajones', 'view' => 'Ciudad'],
                'rules' => 'Uso de alberca hasta las 22:00. No vidrio en la zona de alberca.',
            ],
            'image_text' => 'Terraza Polanco',
        ],
        [
            'name' => 'Elena Vargas',
            'email' => 'elena.vargas@terrazas.test',
            'team' => 'Terraza Elena Vargas',
            'listing' => [
                'name' => 'Mirador Santa Fe',
                'slug' => 'mirador-santa-fe',
                'description' => 'Espacio con vista abierta al poniente, pensado para lanzamientos y reuniones corporativas.',
                'address' => 'Avenida Santa Fe 495',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'postal_code' => '05349',
                'latitude' => 19.3650,
                'longitude' => -99.2600,
                'capacity' => 120,
                'services' => ['wifi', 'bathroom', 'parking', 'sound', 'lighting', 'air_conditioning', 'tables'],
                'details' => ['space_type' => 'terrace', 'access' => 'Elevador y escaleras', 'parking' => '10 cajones', 'view' => 'Panorámica poniente'],
                'rules' => 'Eventos corporativos hasta las 00:00. Requiere lista de invitados.',
            ],
            'image_text' => 'Mirador Santa Fe',
        ],
    ];

    public function run(): void
    {
        foreach ($this->profiles as $index => $profile) {
            $user = User::query()
                ->where('email', $profile['email'])
                ->first();

            if (! $user) {
                $user = User::factory()->create([
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                ]);
            }

            $team = $this->teamFor($user, $profile['team']);

            $listing = Listing::query()
                ->where('team_id', $team->id)
                ->where('name', $profile['listing']['name'])
                ->first();

            if (! $listing) {
                $listing = Listing::factory()
                    ->forTeam($team)
                    ->published()
                    ->create([
                        ...$profile['listing'],
                        'type' => 'terrace',
                        'country' => 'México',
                        'timezone' => 'America/Mexico_City',
                        'contact_name' => $profile['name'],
                        'contact_email' => $profile['email'],
                        'contact_phone' => '55 5555 55'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'whatsapp_phone' => '52 55 5555 55'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    ]);
            }

            if ($listing->images()->count() === 0) {
                $this->importImages($listing, $profile['image_text']);
            }
        }
    }

    private function teamFor(User $user, string $name): Team
    {
        $team = $user->currentTeam;

        if (! $team) {
            $team = Team::factory()->personal()->create(['name' => $name]);
            $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
            $user->switchTeam($team);
        } elseif ($user->wasRecentlyCreated) {
            $team->update(['name' => $name]);
        }

        return $team;
    }

    private function importImages(Listing $listing, string $text): void
    {
        $disk = (string) config('listings.media.disk', 'public');
        $variants = [
            ['size' => '1200x800', 'color' => '0f766e'],
            ['size' => '1200x800', 'color' => '1d4ed8'],
            ['size' => '1200x800', 'color' => 'b45309'],
        ];

        foreach ($variants as $index => $variant) {
            $url = sprintf(
                'https://placehold.co/%s/%s/ffffff/png?text=%s',
                $variant['size'],
                $variant['color'],
                rawurlencode("{$text} ".($index + 1)),
            );

            $response = Http::timeout(20)
                ->retry(2, 300)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("No se pudo descargar la imagen de prueba: {$url}");
            }

            $body = $response->body();
            $dimensions = @getimagesizefromstring($body);

            if ($dimensions === false) {
                throw new RuntimeException("La respuesta de {$url} no es una imagen válida.");
            }

            $path = "listings/{$listing->id}/seed-".($index + 1).'.png';

            if (! Storage::disk($disk)->put($path, $body)) {
                throw new RuntimeException("No se pudo guardar la imagen en {$path}.");
            }

            $listing->media()->create([
                'type' => ListingMediaType::Image,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $dimensions['mime'],
                'size_bytes' => strlen($body),
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'duration_seconds' => null,
                'alt_text' => "{$text} - Foto ".($index + 1),
                'sort_order' => $index + 1,
            ]);
        }
    }
}

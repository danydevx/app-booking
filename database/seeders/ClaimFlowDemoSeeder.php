<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingEventType;
use Modules\Platform\Enums\ListingClaimRole;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Models\ListingClaim;

/**
 * Creates two claimable directory terraces and two verified demo owners so the
 * claim -> approve -> transfer flow can be tested on a local environment.
 *
 * Usage: php artisan db:seed --class=ClaimFlowDemoSeeder
 */
class ClaimFlowDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('ClaimFlowDemoSeeder no se ejecuta en producción.');

            return;
        }

        $directory = $this->directoryTeam();

        $ownerOne = $this->owner(
            'Dueño Demo Uno',
            'dueno.uno@terrazas.test',
            'Terraza Demo Uno',
        );
        $ownerTwo = $this->owner(
            'Dueño Demo Dos',
            'dueno.dos@terrazas.test',
            'Terraza Demo Dos',
        );

        $first = $this->listing($directory, [
            'name' => 'Terraza Demo Reclamable Uno',
            'slug' => 'terraza-demo-reclamable-uno',
            'external_ref' => 'demo-claim-uno',
            'description' => 'Terraza jardín para eventos sociales en Guadalajara. Datos de prueba para reclamaciones.',
            'address' => 'Av. Chapultepec 250',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'postal_code' => '44100',
            'latitude' => 20.6736,
            'longitude' => -103.4055,
            'capacity' => 120,
            'services' => ['wifi', 'bathroom', 'parking', 'green_areas'],
            'event_types' => ['bodas', 'eventos-corporativos', 'cumpleanos'],
            'details' => [
                'space_type' => 'garden',
                'access' => 'Entrada independiente',
                'parking' => '10 cajones',
                'view' => 'Jardín interior',
            ],
            'rules' => 'No se permiten mascotas. Música hasta las 23:00.',
            'contact_name' => 'Equipo Directorio',
            'contact_email' => 'reclamaciones@terrazas.test',
            'contact_phone' => '3312345678',
        ]);

        $second = $this->listing($directory, [
            'name' => 'Terraza Demo Reclamable Dos',
            'slug' => 'terraza-demo-reclamable-dos',
            'external_ref' => 'demo-claim-dos',
            'description' => 'Terraza techada en Zapopan. Datos de prueba para reclamaciones.',
            'address' => 'Av. Guadalupe 500',
            'city' => 'Zapopan',
            'state' => 'Jalisco',
            'postal_code' => '45000',
            'latitude' => 20.7214,
            'longitude' => -103.3918,
            'capacity' => 80,
            'services' => ['wifi', 'rooftop', 'bathroom'],
            'event_types' => ['cumpleanos', 'reuniones-familiares'],
            'details' => [
                'space_type' => 'rooftop',
                'access' => 'Elevador',
                'parking' => 'No incluido',
                'view' => 'Panorámica',
            ],
            'rules' => 'Máximo 80 personas. Eventos hasta las 01:00.',
            'contact_name' => 'Equipo Directorio',
            'contact_email' => 'reclamaciones@terrazas.test',
            'contact_phone' => '3312345679',
        ]);

        ListingClaim::query()->firstOrCreate(
            [
                'listing_id' => $first->id,
                'user_id' => $ownerOne->id,
            ],
            [
                'target_team_id' => $ownerOne->currentTeam?->id,
                'business_name' => 'Terraza Demo Uno',
                'role' => ListingClaimRole::Owner->value,
                'claimant_name' => $ownerOne->name,
                'claimant_email' => $ownerOne->email,
                'claimant_phone' => '3311111111',
                'whatsapp_phone' => '5213311111111',
                'message' => 'Soy propietario de la terraza y quiero administrar el anuncio.',
                'status' => ListingClaimStatus::Pending->value,
            ],
        );

        $this->command?->newLine();
        $this->command?->info('Datos de prueba del flujo de reclamaciones listos.');
        $this->command?->line('Terrazas reclamables:');
        $this->command?->line("  - {$first->name}: /terrazas/{$first->slug}");
        $this->command?->line("  - {$second->name}: /terrazas/{$second->slug}");
        $this->command?->line('Cuentas (contraseña: '.self::DEMO_PASSWORD.'):');
        $this->command?->line("  - {$ownerOne->email} (reclamación pendiente en la terraza Uno)");
        $this->command?->line("  - {$ownerTwo->email} (terraza Dos libre para reclamar)");
        $this->command?->line('Bandeja: /app/platform/claims');
    }

    private function directoryTeam(): Team
    {
        $directory = Team::query()->where('is_directory', true)->first();

        if ($directory !== null) {
            return $directory;
        }

        $directory = new Team([
            'name' => (string) config('listings.directory.team_name', 'Directorio'),
            'is_personal' => false,
            'timezone' => (string) config('listings.default_timezone', 'America/Mexico_City'),
        ]);
        $directory->forceFill(['is_directory' => true])->save();

        return $directory;
    }

    private function owner(string $name, string $email, string $teamName): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $team = $user->currentTeam;

        if ($team === null) {
            $team = Team::factory()->personal()->create(['name' => $teamName]);
            $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
            $user->switchTeam($team);
        }

        return $user->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function listing(Team $directory, array $data): Listing
    {
        $eventTypes = (array) ($data['event_types'] ?? []);
        unset($data['event_types']);

        $listing = Listing::query()->firstOrCreate(
            ['slug' => $data['slug']],
            [
                ...$data,
                'team_id' => $directory->id,
                'type' => (string) config('listings.default_type', 'terrace'),
                'country' => 'México',
                'timezone' => (string) config('listings.default_timezone', 'America/Mexico_City'),
                'status' => 'published',
                'published_at' => now(),
                'claimed_at' => null,
            ],
        );

        if ($eventTypes !== []) {
            $ids = ListingEventType::query()
                ->whereIn('slug', $eventTypes)
                ->pluck('id')
                ->all();

            $listing->eventTypes()->syncWithoutDetaching($ids);
        }

        if ($listing->media()->count() === 0) {
            $this->seedImage($listing);
        }

        return $listing->refresh();
    }

    private function seedImage(Listing $listing): void
    {
        $disk = (string) config('listings.media.disk', 'public');
        $text = rawurlencode($listing->name.' 1');

        $contents = null;

        try {
            $response = Http::timeout(8)
                ->retry(1, 200)
                ->get("https://placehold.co/1200x800/0f766e/ffffff/png?text={$text}");

            if ($response->successful()) {
                $contents = $response->body();
            }
        } catch (\Throwable) {
            $contents = null;
        }

        if ($contents === null || $contents === '') {
            $contents = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=',
            );
        }

        $dimensions = @getimagesizefromstring((string) $contents);
        $path = "listings/{$listing->id}/seed-claim.png";

        Storage::disk($disk)->put($path, (string) $contents);

        $listing->media()->create([
            'type' => ListingMediaType::Image,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $dimensions['mime'] ?? 'image/png',
            'size_bytes' => strlen((string) $contents),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'duration_seconds' => null,
            'alt_text' => "{$listing->name} - Foto 1",
            'sort_order' => 1,
        ]);
    }
}

<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class PlatformListingImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_superadmin_can_download_both_templates(): void
    {
        $user = $this->superadmin();

        $csv = $this->actingAs($user)->get(route('platform.listings.import.template', ['format' => 'csv']));

        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));
        $this->assertStringContainsString('titulo,capacidad,descripcion', (string) $csv->getContent());
        $this->assertStringContainsString('bodas|eventos-corporativos', (string) $csv->getContent());

        $json = $this->actingAs($user)->get(route('platform.listings.import.template', ['format' => 'json']));

        $json->assertOk();
        $this->assertStringContainsString('application/json', (string) $json->headers->get('Content-Type'));
        $this->assertStringContainsString('"listings"', (string) $json->getContent());
    }

    public function test_user_without_moderate_permission_cannot_use_the_importer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.listings.import'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('platform.listings.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('terrazas.csv', $this->csv([
                    ['titulo', 'capacidad'],
                    ['Terraza Uno', '100'],
                ])),
            ])
            ->assertForbidden();
    }

    public function test_csv_upload_shows_a_preview_with_valid_rows_and_errors(): void
    {
        $this->directory();
        $user = $this->superadmin();

        $this->actingAs($user)
            ->post(route('platform.listings.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('terrazas.csv', $this->csv([
                    ['titulo', 'capacidad', 'descripcion', 'tipos_evento', 'caracteristicas', 'external_ref'],
                    ['Jardín Los Encinos', '150', 'Terraza para eventos.', 'bodas|cumpleanos', 'Estacionamiento|wifi', 'encinos'],
                    ['', '90', 'Sin título.', '', '', 'sin-titulo'],
                    ['Terraza Dos', 'cero', '', '', '', ''],
                ])),
            ])
            ->assertRedirect(route('platform.listings.import'));

        $this->actingAs($user)
            ->get(route('platform.listings.import'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Listings/Import')
                ->where('pending.filename', 'terrazas.csv')
                ->where('pending.summary.total', 3)
                ->where('pending.summary.create', 1)
                ->where('pending.summary.error', 2)
                ->where('pending.rows.0.name', 'Jardín Los Encinos')
                ->where('pending.rows.0.capacity', 150)
                ->where('pending.rows.0.event_types.0.value', 'bodas')
                ->where('pending.rows.0.event_types.0.label', 'Bodas')
                ->where('pending.rows.0.services.0.value', 'parking')
                ->where('pending.rows.0.services.0.label', 'Estacionamiento')
                ->where('pending.rows.0.services.1.value', 'wifi'));
    }

    public function test_confirm_import_creates_claimable_directory_listings(): void
    {
        $directory = $this->directory();
        $user = $this->superadmin();

        $this->uploadCsv($user, [
            ['titulo', 'capacidad', 'descripcion', 'tipos_evento', 'caracteristicas', 'external_ref', 'municipio', 'estado'],
            ['Jardín Los Encinos', '150', 'Terraza para eventos.', 'bodas|cumpleanos', 'Estacionamiento|wifi', 'encinos', 'Guadalajara', 'Jalisco'],
            ['Terraza Mirador', '80', 'Vista a la ciudad.', 'Boda', 'Wi-Fi', 'mirador', 'Guadalajara', 'Jalisco'],
        ]);

        $this->actingAs($user)
            ->post(route('platform.listings.import.store'))
            ->assertRedirect(route('platform.listings.import'));

        $this->assertSame(2, Listing::query()->count());

        $listing = Listing::query()->where('external_ref', 'encinos')->firstOrFail();

        $this->assertSame($directory->id, $listing->team_id);
        $this->assertSame(ListingStatus::Draft, $listing->status);
        $this->assertSame(150, $listing->capacity);
        $this->assertSame('Terraza para eventos.', $listing->description);
        $this->assertSame(['parking', 'wifi'], $listing->services);
        $this->assertNull($listing->claimed_at);
        $this->assertSame(
            ['bodas', 'cumpleanos'],
            $listing->eventTypes()->orderBy('slug')->pluck('slug')->all(),
        );

        $this->assertSame(
            ['bodas'],
            Listing::query()->where('external_ref', 'mirador')->firstOrFail()
                ->eventTypes()->pluck('slug')->all(),
        );

        $this->assertSame([], Storage::disk('local')->files('listing-imports'));

        $this->actingAs($user)
            ->get(route('platform.listings.import'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.created', 2)
                ->where('report.updated', 0)
                ->where('report.failed', 0)
                ->where('pending', null));
    }

    public function test_json_upload_maps_event_type_names_and_characteristic_labels(): void
    {
        $this->directory();
        $user = $this->superadmin();

        $payload = [
            'listings' => [
                [
                    'name' => 'Terraza JSON',
                    'capacity' => 60,
                    'description' => 'Cargada por JSON.',
                    'event_types' => ['Boda', 'Evento corporativo'],
                    'services' => ['Wi-Fi', 'Estacionamiento'],
                    'external_ref' => 'json-uno',
                ],
            ],
        ];

        $this->actingAs($user)
            ->post(route('platform.listings.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent(
                    'terrazas.json',
                    (string) json_encode($payload, JSON_UNESCAPED_UNICODE),
                ),
            ])
            ->assertRedirect(route('platform.listings.import'));

        $this->actingAs($user)
            ->post(route('platform.listings.import.store'))
            ->assertRedirect(route('platform.listings.import'));

        $listing = Listing::query()->where('external_ref', 'json-uno')->firstOrFail();

        $this->assertSame(['wifi', 'parking'], $listing->services);
        $this->assertSame(
            ['bodas', 'eventos-corporativos'],
            $listing->eventTypes()->orderBy('slug')->pluck('slug')->all(),
        );
    }

    public function test_semicolon_csv_is_parsed_and_unknown_catalogs_are_reported_as_warnings(): void
    {
        $this->directory();
        $user = $this->superadmin();

        $csv = "titulo;capacidad;tipos_evento;caracteristicas\n";
        $csv .= "Terraza Punto y Coma;40;fiesta-inventada;caracteristica-inventada\n";

        $this->actingAs($user)
            ->post(route('platform.listings.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('terrazas.csv', $csv),
            ])
            ->assertRedirect(route('platform.listings.import'));

        $this->actingAs($user)
            ->get(route('platform.listings.import'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('pending.rows.0.name', 'Terraza Punto y Coma')
                ->where('pending.rows.0.status', 'create')
                ->where('pending.rows.0.warnings.0', 'Tipo de evento desconocido: fiesta-inventada.')
                ->where('pending.rows.0.warnings.1', 'Característica desconocida: caracteristica-inventada.'));
    }

    public function test_existing_claimable_listing_is_updated_by_external_ref(): void
    {
        $directory = $this->directory();
        $user = $this->superadmin();

        $listing = Listing::factory()->forTeam($directory)->create([
            'external_ref' => 'encinos',
            'name' => 'Nombre anterior',
            'claimed_at' => null,
        ]);

        $this->uploadCsv($user, [
            ['titulo', 'external_ref'],
            ['Jardín Los Encinos', 'encinos'],
        ]);

        $this->actingAs($user)
            ->get(route('platform.listings.import'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('pending.rows.0.status', 'update')
                ->where('pending.summary.update', 1));

        $this->actingAs($user)
            ->post(route('platform.listings.import.store'))
            ->assertRedirect(route('platform.listings.import'));

        $this->assertSame(1, Listing::query()->count());
        $this->assertSame('Jardín Los Encinos', $listing->fresh()->name);
    }

    public function test_listing_already_claimed_is_reported_as_an_error(): void
    {
        $directory = $this->directory();
        $user = $this->superadmin();

        $listing = Listing::factory()->forTeam($directory)->create([
            'external_ref' => 'reclamada',
            'name' => 'Nombre original',
            'claimed_at' => now(),
        ]);

        $this->uploadCsv($user, [
            ['titulo', 'external_ref'],
            ['Nombre modificado', 'reclamada'],
        ]);

        $this->actingAs($user)
            ->get(route('platform.listings.import'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('pending.rows.0.status', 'error')
                ->where('pending.summary.error', 1)
                ->where('pending.summary.create', 0));

        $this->actingAs($user)
            ->post(route('platform.listings.import.store'))
            ->assertRedirect(route('platform.listings.import'));

        $this->assertSame('Nombre original', $listing->fresh()->name);

        $this->actingAs($user)
            ->get(route('platform.listings.import'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.created', 0)
                ->where('report.failed', 1));
    }

    public function test_import_rejects_files_over_the_row_limit(): void
    {
        $this->directory();
        $user = $this->superadmin();

        $rows = [['titulo', 'capacidad']];

        for ($index = 1; $index <= 501; $index++) {
            $rows[] = ["Terraza {$index}", '50'];
        }

        $this->actingAs($user)
            ->post(route('platform.listings.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('terrazas.csv', $this->csv($rows)),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Listing::query()->count());
    }

    public function test_confirm_requires_a_pending_upload(): void
    {
        $this->directory();
        $user = $this->superadmin();

        $this->actingAs($user)
            ->post(route('platform.listings.import.store'))
            ->assertRedirect(route('platform.listings.import'))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Listing::query()->count());
    }

    public function test_import_requires_the_directory_account(): void
    {
        $user = $this->superadmin();

        $this->actingAs($user)
            ->post(route('platform.listings.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('terrazas.csv', $this->csv([
                    ['titulo'],
                    ['Terraza sin directorio'],
                ])),
            ])
            ->assertRedirect(route('platform.listings.import'))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Listing::query()->count());
    }

    private function directory(): Team
    {
        return Team::factory()->directory()->create(['name' => 'Directorio']);
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ensure());

        return $user;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function csv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return '';
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '');
        }

        rewind($stream);
        $contents = (string) stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function uploadCsv(User $user, array $rows): void
    {
        $this->actingAs($user)
            ->post(route('platform.listings.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('terrazas.csv', $this->csv($rows)),
            ])
            ->assertRedirect(route('platform.listings.import'))
            ->assertSessionHasNoErrors();
    }
}

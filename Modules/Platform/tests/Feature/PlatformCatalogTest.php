<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Database\Seeders\ListingCatalogSeeder;
use Modules\Listings\Models\ListingCharacteristic;
use Modules\Listings\Models\ListingEventType;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class PlatformCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_event_types_and_featured_characteristics(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->get(route('platform.catalogs.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Catalogs/Index')
                ->has('eventTypes', 13)
                ->has('characteristics', 4));

        $eventTypes = ListingEventType::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ListingEventType $eventType): array => [
                'id' => $eventType->id,
                'name' => $eventType->slug === 'bodas'
                    ? 'Bodas y recepciones'
                    : $eventType->name,
                'description' => $eventType->description,
                'icon' => $eventType->icon,
                'is_active' => $eventType->slug === 'bodas'
                    ? false
                    : $eventType->is_active,
                'sort_order' => $eventType->slug === 'bodas'
                    ? 20
                    : $eventType->sort_order,
            ])
            ->all();

        $characteristics = ListingCharacteristic::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ListingCharacteristic $characteristic): array => [
                'id' => $characteristic->id,
                'description' => $characteristic->service_key === 'garden'
                    ? 'Jardín con sombra natural.'
                    : $characteristic->description,
                'icon' => $characteristic->icon,
                'is_featured' => $characteristic->service_key === 'garden'
                    ? false
                    : $characteristic->is_featured,
                'sort_order' => $characteristic->sort_order,
            ])
            ->all();

        $this->actingAs($superadmin)
            ->patch(route('platform.catalogs.update'), [
                'event_types' => $eventTypes,
                'characteristics' => $characteristics,
            ])
            ->assertRedirect();

        $weddings = ListingEventType::query()->where('slug', 'bodas')->firstOrFail();
        $garden = ListingCharacteristic::query()
            ->where('service_key', 'garden')
            ->firstOrFail();

        $this->assertSame('Bodas y recepciones', $weddings->name);
        $this->assertFalse($weddings->is_active);
        $this->assertSame(20, $weddings->sort_order);
        $this->assertSame('Jardín con sombra natural.', $garden->description);
        $this->assertFalse($garden->is_featured);

        $this->get(route('public.listings.index', ['event' => 'bodas']))
            ->assertSessionHasErrors('event');
        $this->get(route('public.listings.index', ['features' => ['garden']]))
            ->assertOk();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('featuredCharacteristics', fn ($items) => collect($items)
                    ->pluck('key')
                    ->doesntContain('garden')));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'platform.catalogs.updated',
        ]);

        // Running the idempotent seeder must not overwrite the Superadmin edits.
        $this->seed(ListingCatalogSeeder::class);

        $this->assertSame(
            'Bodas y recepciones',
            $weddings->fresh()->name,
        );
        $this->assertFalse($weddings->fresh()->is_active);
        $this->assertFalse($garden->fresh()->is_featured);
    }

    public function test_owners_cannot_access_or_modify_platform_catalogs(): void
    {
        $owner = User::factory()->create();
        $eventTypes = ListingEventType::query()->get()->map(fn (ListingEventType $eventType): array => [
            'id' => $eventType->id,
            'name' => $eventType->name,
            'description' => $eventType->description,
            'icon' => $eventType->icon,
            'is_active' => $eventType->is_active,
            'sort_order' => $eventType->sort_order,
        ])->all();

        $this->actingAs($owner)
            ->get(route('platform.catalogs.edit'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('platform.catalogs.update'), [
                'event_types' => $eventTypes,
                'characteristics' => [],
            ])
            ->assertForbidden();
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ensure());

        return $user;
    }
}

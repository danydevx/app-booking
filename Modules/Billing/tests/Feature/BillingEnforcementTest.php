<?php

namespace Modules\Billing\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPackage;
use Modules\Listings\Models\ListingPromotion;
use Modules\Listings\Models\ListingService;
use Modules\Listings\Support\CommercialServiceCatalog;
use Tests\TestCase;

class BillingEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_cannot_create_a_second_listing(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        Listing::factory()->forTeam($team)->create();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Segunda terraza',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Listing::query()->where('team_id', $team->id)->count());

        $this->actingAs($owner)
            ->get(route('listings.create', ['current_team' => $team->slug]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));
    }

    public function test_active_pro_subscription_allows_more_listings(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribe($team);
        Listing::factory()->forTeam($team)->create();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Segunda terraza',
            ])
            ->assertRedirect();

        $this->assertSame(2, Listing::query()->where('team_id', $team->id)->count());
    }

    public function test_free_photo_limit_is_enforced_on_upload_route(): void
    {
        Storage::fake('public');

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();
        $url = route('listings.media.store', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);

        for ($index = 0; $index < 5; $index++) {
            $this->actingAs($owner)
                ->post($url, [
                    'type' => 'image',
                    'file' => UploadedFile::fake()->image("photo-{$index}.jpg"),
                ])
                ->assertRedirect();
        }

        $this->actingAs($owner)
            ->post($url, [
                'type' => 'image',
                'file' => UploadedFile::fake()->image('photo-6.jpg'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_free_plan_cannot_upload_videos(): void
    {
        Storage::fake('public');

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();

        $this->actingAs($owner)
            ->post(route('listings.media.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'type' => 'video',
                'file' => UploadedFile::fake()->create('clip.mp4', 1000, 'video/mp4'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_customer_crm_is_gated_by_plan(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', ['current_team' => $team->slug]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->subscribe($team);

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Bookings/Customers/Index'));
    }

    public function test_free_plan_limits_packages_per_listing(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();
        $url = route('listings.packages.store', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);

        for ($index = 1; $index <= 5; $index++) {
            $this->actingAs($owner)
                ->post($url, $this->packagePayload($index))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(5, $this->packageCount($listing));

        $this->actingAs($owner)
            ->post($url, $this->packagePayload(6))
            ->assertSessionHasErrors('name');

        $this->assertSame(5, $this->packageCount($listing));

        $this->actingAs($owner)
            ->get(route('listings.packages.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Packages/Index')
                ->where('limits.max', 5)
                ->where('limits.used', 5)
                ->where('limits.can_create', false)
                ->where('billing_url', route('billing.index', ['current_team' => $team->slug])));
    }

    public function test_free_plan_limits_services_per_listing(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();
        $url = route('listings.services.store', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);
        $keys = array_slice(CommercialServiceCatalog::keys(), 0, 6);

        foreach (array_slice($keys, 0, 5) as $key) {
            $this->actingAs($owner)
                ->post($url, $this->servicePayload($key))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(5, $this->serviceCount($listing));

        $this->actingAs($owner)
            ->post($url, $this->servicePayload($keys[5]))
            ->assertSessionHasErrors('service_key');

        $this->assertSame(5, $this->serviceCount($listing));

        $this->actingAs($owner)
            ->get(route('listings.services.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Services/Index')
                ->where('limits.max', 5)
                ->where('limits.used', 5)
                ->where('limits.can_create', false));
    }

    public function test_free_plan_limits_promotions_per_listing(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();
        $url = route('listings.promotions.store', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);

        for ($index = 1; $index <= 5; $index++) {
            $this->actingAs($owner)
                ->post($url, $this->promotionPayload($index))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(5, $this->promotionCount($listing));

        $this->actingAs($owner)
            ->post($url, $this->promotionPayload(6))
            ->assertSessionHasErrors('title');

        $this->assertSame(5, $this->promotionCount($listing));

        $this->actingAs($owner)
            ->get(route('listings.promotions.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Promotions/Index')
                ->where('limits.max', 5)
                ->where('limits.used', 5)
                ->where('limits.can_create', false));
    }

    public function test_commercial_catalog_limits_are_per_listing_on_pro_plan(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribe($team);

        $first = Listing::factory()->forTeam($team)->create();
        $second = Listing::factory()->forTeam($team)->create();

        ListingPackage::factory()->count(5)->forListing($first)->create();

        $this->actingAs($owner)
            ->post(route('listings.packages.store', [
                'current_team' => $team->slug,
                'listing' => $second->slug,
            ]), $this->packagePayload(6))
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $this->packageCount($first));
        $this->assertSame(1, $this->packageCount($second));

        $this->actingAs($owner)
            ->get(route('listings.packages.index', [
                'current_team' => $team->slug,
                'listing' => $first->slug,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('limits.max', 20)
                ->where('limits.used', 5)
                ->where('limits.can_create', true));
    }

    public function test_downgrade_blocks_new_catalog_items_but_keeps_editing_and_deleting(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $subscription = $this->subscribe($team);
        $listing = Listing::factory()->forTeam($team)->create();

        ListingPackage::factory()
            ->count(6)
            ->forListing($listing)
            ->sequence(fn ($sequence): array => ['sort_order' => $sequence->index + 1])
            ->create();

        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);

        $storeUrl = route('listings.packages.store', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);

        $this->actingAs($owner)
            ->post($storeUrl, $this->packagePayload(7))
            ->assertSessionHasErrors('name');

        $this->assertSame(6, $this->packageCount($listing));

        $package = ListingPackage::query()->where('listing_id', $listing->id)->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('listings.packages.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'package' => $package->id,
            ]), $this->packagePayload(8))
            ->assertSessionHasNoErrors();

        $remaining = ListingPackage::query()
            ->where('listing_id', $listing->id)
            ->orderBy('id')
            ->take(2)
            ->get();

        foreach ($remaining as $item) {
            $this->actingAs($owner)
                ->delete(route('listings.packages.destroy', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                    'package' => $item->id,
                ]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(4, $this->packageCount($listing));

        $this->actingAs($owner)
            ->post($storeUrl, $this->packagePayload(9))
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $this->packageCount($listing));
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }

    /**
     * @return array<string, mixed>
     */
    private function packagePayload(int $index): array
    {
        return [
            'name' => "Paquete {$index}",
            'price' => 1200,
            'duration_hours' => 8,
            'included_people' => 40,
            'included_services' => [],
            'active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function servicePayload(string $key): array
    {
        return [
            'service_key' => $key,
            'pricing_mode' => 'additional',
            'provider' => 'own',
            'price' => 500,
            'price_unit' => 'event',
            'active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function promotionPayload(int $index): array
    {
        return [
            'title' => "Promoción {$index}",
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(10)->toDateString(),
            'active' => true,
        ];
    }

    private function packageCount(Listing $listing): int
    {
        return ListingPackage::query()->where('listing_id', $listing->id)->count();
    }

    private function serviceCount(Listing $listing): int
    {
        return ListingService::query()->where('listing_id', $listing->id)->count();
    }

    private function promotionCount(Listing $listing): int
    {
        return ListingPromotion::query()->where('listing_id', $listing->id)->count();
    }

    private function subscribe(Team $team): Subscription
    {
        return Subscription::query()->create([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->bothify('??####'),
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);
    }
}

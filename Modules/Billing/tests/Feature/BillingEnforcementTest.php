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

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
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

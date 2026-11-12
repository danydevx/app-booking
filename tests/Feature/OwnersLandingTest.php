<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OwnersLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_landing_is_public_and_exposes_real_plans(): void
    {
        config(['cashier.secret' => null]);

        $this->get(route('public.owners'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owners')
                ->has('plans', 2)
                ->where('plans.0.slug', 'free')
                ->where('plans.0.price_cents', 0)
                ->where('plans.0.price_formatted', '$0 MXN')
                ->where('plans.0.limits.listings', 1)
                ->where('plans.0.limits.photos_per_listing', 5)
                ->where('plans.0.features.customers', false)
                ->where('plans.1.slug', 'pro')
                ->where('plans.1.price_cents', 49900)
                ->where('plans.1.price_formatted', '$499 MXN')
                ->where('plans.1.limits.listings', 5)
                ->where('plans.1.limits.photos_per_listing', 20)
                ->where('plans.1.limits.videos_per_listing', 3)
                ->where('plans.1.features.customers', true)
                ->missing('plans.1.stripe_price_id')
                ->where('urls.register', route('register'))
                ->where('urls.login', route('login'))
                ->where('urls.dashboard', null)
                ->where('urls.billing', null));
    }

    public function test_authenticated_owner_gets_dashboard_and_billing_links(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;

        $this->actingAs($owner)
            ->get(route('public.owners'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('urls.dashboard', route('dashboard', ['current_team' => $team->slug]))
                ->where('urls.billing', route('billing.index', ['current_team' => $team->slug])));
    }
}

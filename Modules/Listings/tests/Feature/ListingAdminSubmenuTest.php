<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class ListingAdminSubmenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_admin_pages_share_the_same_submenu(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'listing_id' => $listing->id,
        ]);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();

        $pages = [
            route('listings.manage', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('bookings.listing', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('bookings.listing.create', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('listings.pricing.edit', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('listings.packages.index', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('listings.services.index', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('listings.promotions.index', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('bookings.availability.edit', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('listings.edit', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('listings.photos.index', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            route('bookings.show', ['current_team' => $team->slug, 'inquiry' => $inquiry->id]),
        ];

        $titles = [
            'Resumen',
            'Reservaciones',
            'Clientes',
            'Precios',
            'Paquetes',
            'Servicios',
            'Promociones',
            'Disponibilidad',
            'Datos',
            'Fotos',
        ];

        foreach ($pages as $url) {
            $this->actingAs($owner)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->has('listingAdmin.submenu', 10)
                    ->where('listingAdmin.submenu.0.title', 'Resumen')
                    ->where('listingAdmin.submenu.1.title', 'Reservaciones')
                    ->where('listingAdmin.submenu.2.title', 'Clientes')
                    ->where('listingAdmin.submenu.3.title', 'Precios')
                    ->where('listingAdmin.submenu.4.title', 'Paquetes')
                    ->where('listingAdmin.submenu.5.title', 'Servicios')
                    ->where('listingAdmin.submenu.6.title', 'Promociones')
                    ->where('listingAdmin.submenu.7.title', 'Disponibilidad')
                    ->where('listingAdmin.submenu.8.title', 'Datos')
                    ->where('listingAdmin.submenu.9.title', 'Fotos'));
        }

        // Customer workspace keeps its own customer submenu instead of the terrace submenu.
        $this->actingAs($owner)
            ->get(route('bookings.listing.customers.show', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'customer' => $customer->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listingAdmin', null)
                ->where('section', 'summary'));
    }

    /**
     * @return array{0: User, 1: Team, 2: Listing}
     */
    private function ownerListing(): array
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();

        return [$owner, $team, $listing];
    }

    private function subscribePro(Team $team): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        Subscription::query()->create([
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

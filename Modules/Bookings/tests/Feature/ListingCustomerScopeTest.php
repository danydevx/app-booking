<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class ListingCustomerScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_customers_index_only_shows_customers_linked_to_the_listing(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);

        $linked = Customer::factory()->create(['team_id' => $team->id]);
        BookingInquiry::factory()->forListing($listing)->forCustomer($linked)->create();

        $unrelated = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('bookings.listing.customers', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Customers/Index')
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $linked->id)
                ->where('activeListing.id', $listing->id)
                ->where('filters.listing', $listing->slug)
                ->where('management.customers', route('bookings.listing.customers', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]))
                ->where('urls.store', route('bookings.listing.customers.store', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ])));

        $this->assertNotSame($linked->id, $unrelated->id);
    }

    public function test_manual_customer_created_from_a_listing_is_linked_and_visible_in_its_admin(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);

        $response = $this->actingAs($owner)
            ->post(route('bookings.listing.customers.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => 'Cliente De Terraza',
                'email' => 'terraza-cliente@example.test',
                'phone' => '5555550000',
            ]);

        $response->assertSessionHasNoErrors();

        $customer = Customer::query()->where('email', 'terraza-cliente@example.test')->firstOrFail();

        $response->assertRedirect(route('bookings.listing.customers.show', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
            'customer' => $customer->id,
        ]));

        $this->assertSame($listing->id, $customer->listing_id);

        $this->actingAs($owner)
            ->get(route('bookings.listing.customers', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $customer->id));
    }

    public function test_listing_scoped_customer_page_rejects_customers_from_another_listing(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $otherListing = Listing::factory()->forTeam($team)->published()->create();

        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'listing_id' => $otherListing->id,
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.listing.customers.show', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'customer' => $customer->id,
            ]))
            ->assertNotFound();
    }

    public function test_listing_customer_admin_is_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $foreignTeam = Team::factory()->create();
        $foreignListing = Listing::factory()->forTeam($foreignTeam)->published()->create();
        $foreignCustomer = Customer::factory()->create([
            'team_id' => $foreignTeam->id,
            'listing_id' => $foreignListing->id,
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.listing.customers', [
                'current_team' => $team->slug,
                'listing' => $foreignListing->slug,
            ]))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('bookings.listing.customers.show', [
                'current_team' => $team->slug,
                'listing' => $foreignListing->slug,
                'customer' => $foreignCustomer->id,
            ]))
            ->assertNotFound();
    }

    public function test_csv_export_can_be_filtered_by_listing(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $otherListing = Listing::factory()->forTeam($team)->published()->create();

        Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Cliente De Terraza',
            'email' => 'terraza@example.test',
            'listing_id' => $listing->id,
        ]);
        Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Cliente Otra Terraza',
            'email' => 'otra@example.test',
            'listing_id' => $otherListing->id,
        ]);

        $response = $this->actingAs($owner)->get(route('bookings.customers.export', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]));

        $response->assertOk()->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Cliente De Terraza', $content);
        $this->assertStringNotContainsString('Cliente Otra Terraza', $content);
    }

    public function test_scoped_csv_import_links_customers_to_the_listing(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);

        $file = UploadedFile::fake()->createWithContent(
            'clientes.csv',
            "Nombre,Correo,Telefono\nCliente Importado,importado-terraza@example.test,5555551111\n",
        );

        $this->actingAs($owner)
            ->post(route('bookings.customers.import.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'file' => $file,
            ])
            ->assertRedirect(route('bookings.customers.import', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]));

        $this->assertDatabaseHas('customers', [
            'team_id' => $team->id,
            'listing_id' => $listing->id,
            'email' => 'importado-terraza@example.test',
        ]);
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

<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class CrmReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_see_crm_reports(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $this->seedReportData($team, $listing);

        $this->actingAs($owner)
            ->get(route('bookings.reports', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Reports/Index')
                ->where('overview.inquiries', 3)
                ->where('overview.accepted', 1)
                ->where('overview.conversion_percent', 33.3)
                ->where('overview.quotes', 2)
                ->where('overview.quotes_accepted', 1)
                ->has('pipeline', 6)
                ->has('sources', 2)
                ->where('sources.0.source', 'instagram')
                ->where('sources.0.count', 2)
                ->where('sources.0.accepted', 1)
                ->has('listings', 1)
                ->where('listings.0.listing', $listing->name)
                ->has('monthly'));
    }

    public function test_report_only_counts_the_current_team_and_range(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $this->seedReportData($team, $listing);

        $foreignTeam = Team::factory()->create();
        $foreignListing = Listing::factory()->forTeam($foreignTeam)->published()->create();
        BookingInquiry::factory()->forListing($foreignListing)->create([
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.reports', [
                'current_team' => $team->slug,
                'from' => now()->subDays(30)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.inquiries', 3)
                ->has('sources', 2));
    }

    public function test_free_plan_cannot_open_reports(): void
    {
        [$owner, $team] = $this->ownerListing();

        $this->actingAs($owner)
            ->get(route('bookings.reports', ['current_team' => $team->slug]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));
    }

    private function seedReportData(Team $team, Listing $listing): void
    {
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'pipeline_stage' => CustomerPipelineStage::Contacted,
        ]);

        BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(10),
            'contacted_at' => now()->subDays(9),
            'accepted_at' => now()->subDays(8),
            'utm_source' => 'instagram',
        ]);

        BookingInquiry::factory()->forListing($listing)->declined()->create([
            'created_at' => now()->subDays(5),
            'contacted_at' => now()->subDays(4),
            'utm_source' => 'facebook',
        ]);

        BookingInquiry::factory()->forListing($listing)->create([
            'created_at' => now()->subDay(),
            'utm_source' => 'instagram',
        ]);

        CustomerQuote::factory()->forCustomer($customer)->create([
            'listing_id' => $listing->id,
            'status' => QuoteStatus::Accepted,
            'accepted_at' => now()->subDays(2),
        ]);
        CustomerQuote::factory()->forCustomer($customer)->create([
            'listing_id' => $listing->id,
            'status' => QuoteStatus::Sent,
            'sent_at' => now()->subDay(),
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

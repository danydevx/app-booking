<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;
use Modules\Bookings\Models\CustomerTask;
use Tests\TestCase;

class CustomerMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_show_exposes_commercial_metrics(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $accepted = BookingInquiry::factory()
            ->forCustomer($customer)
            ->accepted()
            ->create([
                'start_date' => now($team->timezone)->addDays(3)->toDateString(),
                'end_date' => now($team->timezone)->addDays(4)->toDateString(),
            ]);

        BookingInquiry::factory()
            ->forCustomer($customer)
            ->declined()
            ->create();

        BookingDeposit::factory()->create([
            'booking_inquiry_id' => $accepted->id,
            'amount_cents' => 50000,
            'status' => DepositStatus::Received,
        ]);

        CustomerActivity::factory()->forCustomer($customer)->create([
            'occurred_at' => now()->subDay(),
        ]);

        CustomerTask::factory()->forCustomer($customer)->create([
            'title' => 'Confirmar menú',
            'status' => CustomerTaskStatus::Open,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('metrics.total_inquiries', 2)
                ->where('metrics.accepted_inquiries', 1)
                ->where('metrics.upcoming_bookings', 1)
                ->where('metrics.deposits_received_cents', 50000)
                ->where('metrics.next_task.title', 'Confirmar menú')
                ->whereNotNull('metrics.last_activity_at'));

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('customers.data.0.accepted_inquiries_count', 1));
    }

    public function test_dashboard_counts_new_customers_this_month(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('dashboard', ['current_team' => $team->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.new_customers', 1));
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
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

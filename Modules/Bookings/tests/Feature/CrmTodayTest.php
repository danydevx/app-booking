<?php

namespace Modules\Bookings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTask;
use Modules\Bookings\Notifications\CustomerTaskDigest;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class CrmTodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_render_the_today_board_with_task_groups(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'assigned_to' => $owner->id,
        ]);

        $overdue = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->subDay(),
            'title' => 'Tarea vencida',
        ]);
        $today = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->setTime(15, 0),
            'title' => 'Tarea de hoy',
        ]);
        $upcoming = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->addDays(3),
            'title' => 'Tarea próxima',
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.today', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Crm/Today')
                ->has('overdue', 1)
                ->where('overdue.0.id', $overdue->id)
                ->has('todayTasks', 1)
                ->where('todayTasks.0.id', $today->id)
                ->has('upcomingTasks', 1)
                ->where('upcomingTasks.0.id', $upcoming->id)
                ->where('unassignedCustomers', [])
                ->where('unassignedCount', 0)
                ->where('mine', false));
    }

    public function test_today_board_can_be_filtered_to_the_current_user(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->setTime(12, 0),
            'title' => 'Mía',
        ]);
        CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $member->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->setTime(12, 0),
            'title' => 'Del miembro',
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.today', [
                'current_team' => $team->slug,
                'mine' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('mine', true)
                ->has('todayTasks', 1)
                ->where('todayTasks.0.title', 'Mía'));
    }

    public function test_free_plan_cannot_open_the_today_board(): void
    {
        [$owner, $team] = $this->ownerListing();

        $this->actingAs($owner)
            ->get(route('bookings.today', ['current_team' => $team->slug]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));
    }

    public function test_task_reminder_command_sends_one_digest_and_marks_tasks(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $overdue = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->subDay(),
        ]);
        $today = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->setTime(9, 0),
        ]);

        Notification::fake();

        $this->artisan('bookings:send-task-reminders')->assertSuccessful();

        Notification::assertSentTo($owner, CustomerTaskDigest::class);
        Notification::assertSentTimes(CustomerTaskDigest::class, 1);

        $this->assertNotNull($overdue->fresh()->last_reminded_at);
        $this->assertNotNull($today->fresh()->last_reminded_at);

        $this->artisan('bookings:send-task-reminders')->assertSuccessful();

        Notification::assertSentTimes(CustomerTaskDigest::class, 1);
    }

    public function test_task_reminder_command_dry_run_does_not_send_or_mark(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $task = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
            'due_at' => now($team->timezone)->setTime(9, 0),
        ]);

        Notification::fake();

        $this->artisan('bookings:send-task-reminders', ['--dry-run' => true])
            ->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($task->fresh()->last_reminded_at);
    }

    public function test_customer_can_be_assigned_and_filtered_by_responsible(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'assigned_to' => $owner->id,
        ]);
        Customer::factory()->create([
            'team_id' => $team->id,
            'assigned_to' => $member->id,
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.update', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'name' => $customer->name,
                'email' => $customer->email,
                'assigned_to' => $member->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($member->id, $customer->fresh()->assigned_to);

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
                'assigned' => 'me',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 0));

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
                'assigned' => 'none',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 0));
    }

    public function test_customer_cannot_be_assigned_to_a_foreign_user(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $foreign = User::factory()->create();

        $this->actingAs($owner)
            ->patch(route('bookings.customers.update', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'name' => $customer->name,
                'email' => $customer->email,
                'assigned_to' => $foreign->id,
            ])
            ->assertSessionHasErrors('assigned_to');

        $this->assertNull($customer->fresh()->assigned_to);
    }

    public function test_customer_show_surfaces_possible_duplicates_by_phone(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);

        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'phone' => '5551234567',
        ]);
        $duplicate = Customer::factory()->create([
            'team_id' => $team->id,
            'phone' => '5551234567',
        ]);

        $foreignTeam = Team::factory()->create();
        Customer::factory()->create([
            'team_id' => $foreignTeam->id,
            'phone' => '5551234567',
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('duplicateCandidates', 1)
                ->where('duplicateCandidates.0.id', $duplicate->id));
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

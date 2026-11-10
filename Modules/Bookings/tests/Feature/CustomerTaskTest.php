<?php

namespace Modules\Bookings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\CustomerTaskPriority;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTask;
use Tests\TestCase;

class CustomerTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_create_a_task_and_see_it_on_the_customer(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.tasks.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'title' => 'Confirmar número de invitados',
                'notes' => 'Llamar después de las 10.',
                'due_at' => now()->addDays(2)->toDateTimeString(),
                'priority' => CustomerTaskPriority::High->value,
            ])
            ->assertRedirect();

        $task = CustomerTask::query()->firstOrFail();

        $this->assertSame($team->id, $task->team_id);
        $this->assertSame($customer->id, $task->customer_id);
        $this->assertSame($owner->id, $task->created_by);
        $this->assertSame($owner->id, $task->assigned_to);
        $this->assertSame(CustomerTaskStatus::Open, $task->status);
        $this->assertSame(CustomerTaskPriority::High, $task->priority);
        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => 'Tarea creada: Confirmar número de invitados.',
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tasks', 1)
                ->has('taskPriorities', 3)
                ->where('tasks.0.title', 'Confirmar número de invitados')
                ->where('tasks.0.can_manage', true));

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('customers.data.0.open_tasks_count', 1)
                ->where('customers.data.0.overdue_tasks_count', 0));
    }

    public function test_task_can_be_assigned_to_a_team_member(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.tasks.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'title' => 'Preparar propuesta',
                'priority' => CustomerTaskPriority::Normal->value,
                'assigned_to' => $member->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($member->id, CustomerTask::query()->firstOrFail()->assigned_to);
    }

    public function test_invalid_assignee_is_rejected(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $outsider = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('bookings.customers.tasks.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'title' => 'Tarea inválida',
                'priority' => CustomerTaskPriority::Normal->value,
                'assigned_to' => $outsider->id,
            ])
            ->assertSessionHasErrors('assigned_to');

        $this->assertDatabaseCount('customer_tasks', 0);
    }

    public function test_owner_can_update_complete_and_cancel_tasks(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $task = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.tasks.update', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
                'task' => $task->id,
            ]), [
                'title' => 'Tarea actualizada',
                'notes' => 'Nueva nota',
                'priority' => CustomerTaskPriority::High->value,
                'assigned_to' => $owner->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Tarea actualizada', $task->fresh()->title);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.tasks.complete', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
                'task' => $task->id,
            ]))
            ->assertRedirect();

        $this->assertSame(CustomerTaskStatus::Done, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);

        $second = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.tasks.cancel', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
                'task' => $second->id,
            ]))
            ->assertRedirect();

        $this->assertSame(CustomerTaskStatus::Cancelled, $second->fresh()->status);
    }

    public function test_member_cannot_manage_a_task_they_do_not_own(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $task = CustomerTask::factory()->forCustomer($customer)->create([
            'assigned_to' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($member)
            ->patch(route('bookings.customers.tasks.complete', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
                'task' => $task->id,
            ]))
            ->assertForbidden();
    }

    public function test_free_plan_cannot_create_tasks(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.tasks.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'title' => 'Tarea bloqueada',
                'priority' => CustomerTaskPriority::Normal->value,
            ])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('customer_tasks', 0);
    }

    public function test_dashboard_counts_open_and_overdue_tasks(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        CustomerTask::factory()->forCustomer($customer)->create([
            'due_at' => now()->subDay(),
        ]);
        CustomerTask::factory()->forCustomer($customer)->create([
            'due_at' => now()->addDays(2),
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard', ['current_team' => $team->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.open_tasks', 2)
                ->where('overview.overdue_tasks', 1));
    }

    public function test_tasks_are_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $other = User::factory()->create();
        $customer = Customer::factory()->create(['team_id' => $other->currentTeam->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.tasks.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'title' => 'Tarea ajena',
                'priority' => CustomerTaskPriority::Normal->value,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('customer_tasks', 0);
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

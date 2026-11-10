<?php

namespace Modules\Bookings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;
use Tests\TestCase;

class CustomerActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_add_and_review_a_customer_activity(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.activities.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'type' => CustomerActivityType::Call->value,
                'body' => 'Llamé para confirmar el número de invitados.',
                'occurred_at' => now()->subHour()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customer_activities', [
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'type' => CustomerActivityType::Call->value,
            'body' => 'Llamé para confirmar el número de invitados.',
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Customers/Show')
                ->has('activities', 1)
                ->has('activityTypes', 5)
                ->where('activities.0.type_label', 'Llamada')
                ->where('activities.0.author', $owner->name)
                ->where('activities.0.can_delete', true));
    }

    public function test_free_plan_cannot_add_customer_activities(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.activities.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'type' => CustomerActivityType::Note->value,
                'body' => 'No debería guardarse.',
            ])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('customer_activities', 0);
    }

    public function test_customer_activities_are_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $other = User::factory()->create();
        $foreignCustomer = Customer::factory()->create(['team_id' => $other->currentTeam->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.activities.store', [
                'current_team' => $team->slug,
                'customer' => $foreignCustomer->id,
            ]), [
                'type' => CustomerActivityType::Note->value,
                'body' => 'Intento ajeno.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('customer_activities', 0);
    }

    public function test_only_author_or_admin_can_delete_an_activity(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $activity = CustomerActivity::factory()->forCustomer($customer)->create([
            'user_id' => $owner->id,
        ]);

        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->actingAs($member)
            ->delete(route('bookings.customers.activities.destroy', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
                'activity' => $activity->id,
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('customer_activities', ['id' => $activity->id]);

        $this->actingAs($owner)
            ->delete(route('bookings.customers.activities.destroy', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
                'activity' => $activity->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseMissing('customer_activities', ['id' => $activity->id]);
    }

    public function test_updating_a_customer_adds_a_system_activity(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Nombre anterior',
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.update', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'name' => 'Nombre actualizado',
                'email' => $customer->email,
                'phone' => $customer->phone,
                'whatsapp_phone' => $customer->whatsapp_phone,
                'notes' => $customer->notes,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customer_activities', [
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
            'type' => CustomerActivityType::System->value,
            'body' => 'Datos del cliente actualizados.',
        ]);
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

<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\Customer;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_create_a_customer_manually(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);

        $this->actingAs($owner)
            ->post(route('bookings.customers.store', ['current_team' => $team->slug]), [
                'name' => 'Cliente Manual',
                'email' => 'manual@example.test',
                'phone' => '5555555555',
                'whatsapp_phone' => '5215555555555',
                'notes' => 'Llegó por recomendación.',
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('email', 'manual@example.test')->firstOrFail();

        $this->assertSame($team->id, $customer->team_id);
        $this->assertSame('Cliente Manual', $customer->name);
        $this->assertSame('Llegó por recomendación.', $customer->notes);
    }

    public function test_free_plan_cannot_create_customers_manually(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('bookings.customers.store', ['current_team' => $team->slug]), [
                'name' => 'Cliente Free',
                'email' => 'free@example.test',
            ])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_duplicate_email_is_rejected_within_the_same_team(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        Customer::factory()->create([
            'team_id' => $team->id,
            'email' => 'duplicado@example.test',
        ]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.store', ['current_team' => $team->slug]), [
                'name' => 'Duplicado',
                'email' => 'duplicado@example.test',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Customer::query()->where('email', 'duplicado@example.test')->count());
    }

    public function test_same_email_is_allowed_in_another_team(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$ownerA, $teamA] = $this->ownerWithTeam();
        [$ownerB, $teamB] = $this->ownerWithTeam();
        $this->subscribePro($teamA);
        $this->subscribePro($teamB);

        Customer::factory()->create([
            'team_id' => $teamA->id,
            'email' => 'compartido@example.test',
        ]);

        $this->actingAs($ownerB)
            ->post(route('bookings.customers.store', ['current_team' => $teamB->slug]), [
                'name' => 'Cliente B',
                'email' => 'compartido@example.test',
            ])
            ->assertRedirect();

        $this->assertSame(2, Customer::query()->where('email', 'compartido@example.test')->count());
    }

    public function test_update_rejects_duplicate_email_within_the_same_team(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);

        $first = Customer::factory()->create([
            'team_id' => $team->id,
            'email' => 'primero@example.test',
        ]);
        Customer::factory()->create([
            'team_id' => $team->id,
            'email' => 'segundo@example.test',
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.update', [
                'current_team' => $team->slug,
                'customer' => $first->id,
            ]), [
                'name' => $first->name,
                'email' => 'segundo@example.test',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('primero@example.test', $first->fresh()->email);
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

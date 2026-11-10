<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Bookings\Models\Customer;
use Tests\TestCase;

class CustomerPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_change_the_pipeline_stage(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->assertSame(CustomerPipelineStage::Lead, $customer->pipeline_stage);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.pipeline.update', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), ['stage' => CustomerPipelineStage::Qualified->value])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(CustomerPipelineStage::Qualified, $customer->fresh()->pipeline_stage);
        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => 'Etapa cambiada: Prospecto → Calificado.',
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
                'stage' => CustomerPipelineStage::Qualified->value,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.pipeline_stage_label', 'Calificado'));
    }

    public function test_invalid_stage_is_rejected(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.pipeline.update', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), ['stage' => 'no-existe'])
            ->assertSessionHasErrors('stage');
    }

    public function test_pipeline_is_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $other = User::factory()->create();
        $foreign = Customer::factory()->create(['team_id' => $other->currentTeam->id]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.pipeline.update', [
                'current_team' => $team->slug,
                'customer' => $foreign->id,
            ]), ['stage' => CustomerPipelineStage::Won->value])
            ->assertNotFound();
    }

    public function test_free_plan_cannot_change_pipeline(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.pipeline.update', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), ['stage' => CustomerPipelineStage::Won->value])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertSame(CustomerPipelineStage::Lead, $customer->fresh()->pipeline_stage);
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

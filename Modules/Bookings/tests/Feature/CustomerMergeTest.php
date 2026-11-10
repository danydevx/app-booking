<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;
use Modules\Bookings\Models\CustomerTag;
use Modules\Bookings\Models\CustomerTask;
use Tests\TestCase;

class CustomerMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_merge_customers_and_move_their_relations(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);

        $target = Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Cliente Destino',
            'email' => 'destino@example.test',
            'phone' => null,
        ]);
        $source = Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Cliente Origen',
            'email' => 'origen@example.test',
            'phone' => '5550000000',
        ]);

        $inquiry = BookingInquiry::factory()->forCustomer($source)->create();
        $task = CustomerTask::factory()->forCustomer($source)->create();
        CustomerActivity::factory()->forCustomer($source)->create();
        $tag = CustomerTag::factory()->create(['team_id' => $team->id]);
        $source->tags()->attach($tag);

        $this->actingAs($owner)
            ->post(route('bookings.customers.merge', [
                'current_team' => $team->slug,
                'customer' => $source->id,
            ]), [
                'target_id' => $target->id,
                'reason' => 'Mismo cliente con dos correos.',
            ])
            ->assertRedirect(route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $target->id,
            ]));

        $source->refresh();
        $target->refresh();

        $this->assertSame($target->id, $source->merged_into_id);
        $this->assertNotNull($source->merged_at);
        $this->assertNotNull($source->archived_at);
        $this->assertSame($target->id, $inquiry->fresh()->customer_id);
        $this->assertSame($target->id, $task->fresh()->customer_id);
        $this->assertTrue($target->tags->contains($tag));
        $this->assertFalse($source->tags->contains($tag));
        $this->assertSame('5550000000', $target->phone);
        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $target->id,
            'body' => 'Cliente fusionado desde Cliente Origen (origen@example.test). Motivo: Mismo cliente con dos correos.',
        ]);
    }

    public function test_cannot_merge_customers_from_different_teams(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $source = Customer::factory()->create(['team_id' => $team->id]);
        $foreign = Customer::factory()->create();

        $this->actingAs($owner)
            ->post(route('bookings.customers.merge', [
                'current_team' => $team->slug,
                'customer' => $source->id,
            ]), [
                'target_id' => $foreign->id,
                'reason' => 'Intento inválido.',
            ])
            ->assertSessionHasErrors('target_id');

        $this->assertNull($source->fresh()->merged_into_id);
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

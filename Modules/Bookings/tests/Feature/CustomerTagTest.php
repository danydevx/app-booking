<?php

namespace Modules\Bookings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTag;
use Tests\TestCase;

class CustomerTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_create_assign_filter_and_delete_tags(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.tags.store', ['current_team' => $team->slug]), [
                'name' => 'Corporativo',
                'color' => '#0f766e',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $tag = CustomerTag::query()->firstOrFail();

        $this->assertSame($team->id, $tag->team_id);
        $this->assertSame('corporativo', $tag->slug);
        $this->assertSame('#0f766e', $tag->color);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.tags.sync', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), ['tag_ids' => [$tag->id]])
            ->assertSessionHasNoErrors();

        $this->assertTrue($customer->fresh()->tags->contains($tag));

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
                'tag' => 'corporativo',
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.tags.0.name', 'Corporativo'));

        $this->actingAs($owner)
            ->get(route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tags', 1)
                ->where('tags.0.customers_count', 1)
                ->where('canManageTags', true));

        $this->actingAs($owner)
            ->delete(route('bookings.customers.tags.destroy', [
                'current_team' => $team->slug,
                'tag' => $tag->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseMissing('customer_tags', ['id' => $tag->id]);
        $this->assertDatabaseCount('customer_tag_assignments', 0);
    }

    public function test_member_cannot_create_or_delete_tags(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $tag = CustomerTag::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->post(route('bookings.customers.tags.store', ['current_team' => $team->slug]), [
                'name' => 'No permitida',
                'color' => '#64748b',
            ])
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('bookings.customers.tags.destroy', [
                'current_team' => $team->slug,
                'tag' => $tag->id,
            ]))
            ->assertForbidden();
    }

    public function test_tags_from_another_team_cannot_be_assigned(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $foreignTag = CustomerTag::factory()->create();

        $this->actingAs($owner)
            ->patch(route('bookings.customers.tags.sync', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), ['tag_ids' => [$foreignTag->id]])
            ->assertSessionHasErrors('tag_ids.0');

        $this->assertDatabaseCount('customer_tag_assignments', 0);
    }

    public function test_free_plan_cannot_manage_tags(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('bookings.customers.tags.store', ['current_team' => $team->slug]), [
                'name' => 'Bloqueada',
                'color' => '#64748b',
            ])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('customer_tags', 0);
    }

    public function test_tag_limit_is_twenty_per_team(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);

        CustomerTag::factory()->count(20)->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.customers.tags.store', ['current_team' => $team->slug]), [
                'name' => 'Etiqueta 21',
                'color' => '#64748b',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(20, CustomerTag::query()->where('team_id', $team->id)->count());
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

<?php

namespace Modules\Billing\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_billing_and_price_ids_are_not_exposed(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->get(route('billing.index', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Index')
                ->where('plan.slug', 'free')
                ->has('plans', 2)
                ->missing('plans.0.stripe_price_id')
                ->missing('plans.1.stripe_price_id')
                ->where('subscription.stripe_configured', false));
    }

    public function test_non_owner_member_cannot_open_billing_or_start_checkout(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $member = User::factory()->create();
        $member->teams()->attach($team, ['role' => TeamRole::Member->value]);
        $member->switchTeam($team);

        $this->actingAs($member)
            ->get(route('billing.index', ['current_team' => $team->slug]))
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('billing.checkout', ['current_team' => $team->slug]), [
                'plan' => 'pro',
            ])
            ->assertForbidden();

        $this->assertNotNull($owner->fresh());
    }

    public function test_checkout_is_blocked_without_stripe_configuration_or_price(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        config([
            'billing.plans.pro.stripe_price_id' => null,
            'cashier.secret' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('billing.checkout', ['current_team' => $team->slug]), [
                'plan' => 'pro',
            ])
            ->assertSessionHasErrors('plan');

        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $this->actingAs($owner)
            ->post(route('billing.checkout', ['current_team' => $team->slug]), [
                'plan' => 'pro',
            ])
            ->assertSessionHasErrors('plan');
    }

    public function test_free_plan_cannot_be_checked_out_and_portal_requires_a_customer(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        config(['cashier.secret' => 'sk_test_fake']);

        $this->actingAs($owner)
            ->post(route('billing.checkout', ['current_team' => $team->slug]), [
                'plan' => 'free',
            ])
            ->assertSessionHasErrors('plan');

        $this->actingAs($owner)
            ->post(route('billing.portal', ['current_team' => $team->slug]))
            ->assertSessionHasErrors('billing');
    }

    public function test_success_redirect_alone_does_not_unlock_paid_features(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->get(route('billing.index', [
                'current_team' => $team->slug,
                'checkout' => 'success',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/Index')
                ->where('plan.slug', 'free')
                ->where('checkoutResult', 'success'));
    }

    public function test_owner_of_another_account_cannot_open_this_billing_page(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $otherOwner = User::factory()->create();

        $this->actingAs($otherOwner)
            ->get(route('billing.index', ['current_team' => $team->slug]))
            ->assertForbidden();

        $this->assertNotNull($owner->fresh());
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }
}

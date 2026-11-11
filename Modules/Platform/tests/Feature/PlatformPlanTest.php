<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Billing\Support\Entitlements;
use Modules\Billing\Support\PlanCatalog;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class PlatformPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_plans_and_their_features(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->get(route('platform.plans.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Plans/Index')
                ->has('plans', 2)
                ->where('plans.0.slug', 'free')
                ->where('plans.1.slug', 'pro')
                ->has('limitLabels', 3)
                ->has('featureLabels', 2));
    }

    public function test_superadmin_can_update_plan_limits_and_features(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $superadmin = $this->superadmin();
        $team = Team::factory()->create();

        $this->subscribe($team, 'price_pro_test');

        $this->actingAs($superadmin)
            ->patch(route('platform.plans.update', ['plan' => 'pro']), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('billing_plans', [
            'slug' => 'pro',
            'name' => 'Pro actualizado',
            'stripe_price_id' => 'price_pro_test',
            'active' => true,
        ]);

        $plan = app(PlanCatalog::class)->get('pro');

        $this->assertNotNull($plan);
        $this->assertSame(9, $plan->limit('listings'));
        $this->assertTrue($plan->allows('ai_posts'));
        $this->assertFalse($plan->allows('customers'));

        $entitlements = app(Entitlements::class);

        $this->assertSame(9, $entitlements->limit($team, 'listings'));
        $this->assertTrue($entitlements->allows($team, 'ai_posts'));
        $this->assertFalse($entitlements->allows($team, 'customers'));
    }

    public function test_superadmin_can_create_a_new_plan(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post(route('platform.plans.store'), [
                ...$this->payload(),
                'slug' => 'premium',
                'name' => 'Premium',
                'stripe_price_id' => 'price_premium_test',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('billing_plans', [
            'slug' => 'premium',
            'name' => 'Premium',
            'stripe_price_id' => 'price_premium_test',
        ]);

        $catalog = app(PlanCatalog::class);

        $this->assertNotNull($catalog->get('premium'));
        $this->assertNotNull($catalog->findByPriceId('price_premium_test'));
        $this->assertContains(
            'premium',
            array_map(fn ($plan): string => $plan->slug, $catalog->paid()),
        );
    }

    public function test_free_plan_cannot_be_deactivated(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->patch(route('platform.plans.update', ['plan' => 'free']), [
                ...$this->payload(),
                'name' => 'Gratis',
                'active' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('billing_plans', [
            'slug' => 'free',
            'active' => true,
        ]);
    }

    public function test_normal_users_cannot_manage_plans(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.plans.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('platform.plans.update', ['plan' => 'pro']), $this->payload())
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pro actualizado',
            'description' => 'Plan Pro con más capacidad.',
            'stripe_price_id' => 'price_pro_test',
            'interval' => 'month',
            'active' => true,
            'limits' => [
                'listings' => 9,
                'photos_per_listing' => 30,
                'videos_per_listing' => 5,
            ],
            'features' => [
                'customers' => false,
                'ai_posts' => true,
            ],
        ], $overrides);
    }

    private function subscribe(Team $team, string $priceId): Subscription
    {
        return Subscription::query()->create([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->bothify('??####'),
            'stripe_status' => 'active',
            'stripe_price' => $priceId,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ensure());

        return $user;
    }
}

<?php

namespace Modules\Billing\Support;

use Modules\Billing\Models\BillingPlan;

final class PlanCatalog
{
    /**
     * @return array<string, Plan>
     */
    public function all(): array
    {
        $configPlans = (array) config('billing.plans', []);
        $databasePlans = BillingPlan::query()->get()->keyBy('slug');

        $slugs = array_values(array_unique([
            ...array_keys($configPlans),
            ...$databasePlans->keys()->all(),
        ]));

        $plans = [];

        foreach ($slugs as $slug) {
            $slug = (string) $slug;
            $config = $configPlans[$slug] ?? [];
            $database = $databasePlans->get($slug);

            if (! is_array($config) && $database === null) {
                continue;
            }

            $plans[$slug] = $this->plan($slug, is_array($config) ? $config : [], $database);
        }

        return $plans;
    }

    public function get(string $slug): ?Plan
    {
        return $this->all()[$slug] ?? null;
    }

    public function default(): Plan
    {
        return $this->get('free') ?? Plan::fromConfig('free', [
            'name' => 'Gratis',
            'limits' => ['listings' => 1, 'photos_per_listing' => 5, 'videos_per_listing' => 0],
            'features' => ['customers' => false, 'ai_posts' => false],
        ]);
    }

    public function findByPriceId(?string $priceId): ?Plan
    {
        if ($priceId === null || $priceId === '') {
            return null;
        }

        foreach ($this->all() as $plan) {
            if ($plan->stripePriceId === $priceId) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * @return array<int, Plan>
     */
    public function paid(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Plan $plan): bool => ! $plan->isFree() && $plan->active,
        ));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function plan(string $slug, array $config, ?BillingPlan $database): Plan
    {
        $configPrice = $config['stripe_price_id'] ?? null;
        $configPrice = is_string($configPrice) && $configPrice !== ''
            ? $configPrice
            : null;

        if ($database !== null) {
            return new Plan(
                slug: $slug,
                name: (string) $database->name,
                description: (string) ($database->description ?? ''),
                stripePriceId: $database->stripe_price_id ?: $configPrice,
                interval: (string) $database->interval,
                active: (bool) $database->active,
                limits: array_map('intval', array_merge(
                    (array) ($config['limits'] ?? []),
                    (array) ($database->limits ?? []),
                )),
                features: array_map('boolval', array_merge(
                    (array) ($config['features'] ?? []),
                    (array) ($database->features ?? []),
                )),
            );
        }

        return new Plan(
            slug: $slug,
            name: (string) ($config['name'] ?? ucfirst($slug)),
            description: (string) ($config['description'] ?? ''),
            stripePriceId: $configPrice,
            interval: (string) ($config['interval'] ?? 'month'),
            active: (bool) ($config['active'] ?? true),
            limits: array_map('intval', (array) ($config['limits'] ?? [])),
            features: array_map('boolval', (array) ($config['features'] ?? [])),
        );
    }
}

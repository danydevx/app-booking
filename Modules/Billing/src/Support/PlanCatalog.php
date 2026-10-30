<?php

namespace Modules\Billing\Support;

final class PlanCatalog
{
    /**
     * @return array<string, Plan>
     */
    public function all(): array
    {
        $plans = [];

        foreach ((array) config('billing.plans', []) as $slug => $config) {
            if (! is_array($config)) {
                continue;
            }

            $plans[(string) $slug] = Plan::fromConfig((string) $slug, $config);
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
}

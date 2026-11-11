<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Models\BillingPlan;
use Modules\Billing\Support\Plan;
use Modules\Billing\Support\PlanCatalog;
use Modules\Platform\Http\Requests\StorePlatformPlanRequest;
use Modules\Platform\Http\Requests\UpdatePlatformPlanRequest;

class PlatformPlanController extends Controller
{
    public function index(PlanCatalog $catalog): Response
    {
        $limitLabels = (array) config('billing.limit_labels', []);
        $featureLabels = (array) config('billing.feature_labels', []);

        $plans = collect($catalog->all())
            ->map(fn (Plan $plan): array => [
                ...$plan->toArray(),
                'stripe_price_id' => $plan->stripePriceId,
                'update_url' => route('platform.plans.update', ['plan' => $plan->slug]),
            ])
            ->values()
            ->all();

        return Inertia::render('Platform/Plans/Index', [
            'plans' => $plans,
            'limitLabels' => $limitLabels,
            'featureLabels' => $featureLabels,
            'urls' => [
                'index' => route('platform.plans.index'),
                'store' => route('platform.plans.store'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function store(StorePlatformPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        BillingPlan::query()->create([
            'slug' => (string) $data['slug'],
            'name' => (string) $data['name'],
            'description' => $data['description'] ?? null,
            'stripe_price_id' => $data['stripe_price_id'] ?? null,
            'interval' => (string) $data['interval'],
            'active' => $request->boolean('active'),
            'limits' => $this->limits($data),
            'features' => $this->features($data),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Plan creado.',
        ]);

        return back();
    }

    public function update(
        UpdatePlatformPlanRequest $request,
        string $plan,
        PlanCatalog $catalog,
    ): RedirectResponse {
        abort_if($catalog->get($plan) === null, 404);

        $data = $request->validated();

        BillingPlan::query()->updateOrCreate(
            ['slug' => $plan],
            [
                'name' => (string) $data['name'],
                'description' => $data['description'] ?? null,
                'stripe_price_id' => $data['stripe_price_id'] ?? null,
                'interval' => (string) $data['interval'],
                'active' => $plan === 'free' ? true : $request->boolean('active'),
                'limits' => $this->limits($data),
                'features' => $this->features($data),
            ],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Plan actualizado.',
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int>
     */
    private function limits(array $data): array
    {
        $limits = [];

        foreach (array_keys((array) config('billing.limit_labels', [])) as $key) {
            $limits[(string) $key] = (int) (($data['limits'] ?? [])[$key] ?? 0);
        }

        return $limits;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, bool>
     */
    private function features(array $data): array
    {
        $features = [];

        foreach (array_keys((array) config('billing.feature_labels', [])) as $key) {
            $features[(string) $key] = (bool) (($data['features'] ?? [])[$key] ?? false);
        }

        return $features;
    }
}

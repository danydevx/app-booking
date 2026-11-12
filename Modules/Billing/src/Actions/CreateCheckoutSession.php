<?php

namespace Modules\Billing\Actions;

use App\Models\Team;
use Modules\Billing\Support\Plan;

class CreateCheckoutSession
{
    public function handle(Team $team, Plan $plan): string
    {
        $checkout = $team
            ->newSubscription('default', $plan->stripePriceId)
            ->withMetadata([
                'team_id' => (string) $team->id,
                'plan' => $plan->slug,
            ])
            ->checkout([
                'success_url' => route('billing.index', [
                    'current_team' => $team->slug,
                ]).'?checkout=success',
                'cancel_url' => route('billing.index', [
                    'current_team' => $team->slug,
                ]).'?checkout=cancelled',
                'client_reference_id' => (string) $team->id,
            ]);

        return $checkout->redirect()->getTargetUrl();
    }
}

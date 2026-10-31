<?php

namespace Modules\Platform\Support;

final class StripeDashboard
{
    public static function customerUrl(?string $stripeId): ?string
    {
        if ($stripeId === null || $stripeId === '') {
            return null;
        }

        $mode = str_starts_with((string) config('cashier.secret'), 'sk_live') ? '' : '/test';

        return "https://dashboard.stripe.com{$mode}/customers/{$stripeId}";
    }
}

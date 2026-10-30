<?php

namespace App\Support\Platform;

final class PlatformPermission
{
    public const ACCESS = 'platform.access';

    public const ACCOUNTS_VIEW = 'platform.accounts.view';

    public const ACCOUNTS_SUSPEND = 'platform.accounts.suspend';

    public const LISTINGS_VIEW = 'platform.listings.view';

    public const LISTINGS_MODERATE = 'platform.listings.moderate';

    public const SUBSCRIPTIONS_VIEW = 'platform.subscriptions.view';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::ACCESS,
            self::ACCOUNTS_VIEW,
            self::ACCOUNTS_SUSPEND,
            self::LISTINGS_VIEW,
            self::LISTINGS_MODERATE,
            self::SUBSCRIPTIONS_VIEW,
        ];
    }
}

<?php

namespace Modules\Platform\Support;

final class PlatformPermission
{
    public const ACCESS = 'platform.access';

    public const ACCOUNTS_VIEW = 'platform.accounts.view';

    public const ACCOUNTS_SUSPEND = 'platform.accounts.suspend';

    public const LISTINGS_VIEW = 'platform.listings.view';

    public const LISTINGS_MODERATE = 'platform.listings.moderate';

    public const SUBSCRIPTIONS_VIEW = 'platform.subscriptions.view';

    public const REPORTS_VIEW = 'platform.reports.view';

    public const REPORTS_MANAGE = 'platform.reports.manage';

    public const BRANDING_MANAGE = 'platform.branding.manage';

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
            self::REPORTS_VIEW,
            self::REPORTS_MANAGE,
            self::BRANDING_MANAGE,
        ];
    }
}

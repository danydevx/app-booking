<?php

namespace Modules\Platform\Support;

final class PlatformPermission
{
    public const ACCESS = 'platform.access';

    public const ACCOUNTS_VIEW = 'platform.accounts.view';

    public const ACCOUNTS_SUSPEND = 'platform.accounts.suspend';

    public const USERS_VIEW = 'platform.users.view';

    public const USERS_SUSPEND = 'platform.users.suspend';

    public const USERS_MANAGE_ROLES = 'platform.users.roles';

    public const USERS_NOTIFY = 'platform.users.notify';

    public const ACTIVITY_VIEW = 'platform.activity.view';

    public const SYSTEM_VIEW = 'platform.system.view';

    public const SYSTEM_MANAGE = 'platform.system.manage';

    public const LISTINGS_VIEW = 'platform.listings.view';

    public const LISTINGS_MODERATE = 'platform.listings.moderate';

    public const SUBSCRIPTIONS_VIEW = 'platform.subscriptions.view';

    public const PLANS_VIEW = 'platform.plans.view';

    public const PLANS_MANAGE = 'platform.plans.manage';

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
            self::USERS_VIEW,
            self::USERS_SUSPEND,
            self::USERS_MANAGE_ROLES,
            self::USERS_NOTIFY,
            self::ACTIVITY_VIEW,
            self::SYSTEM_VIEW,
            self::SYSTEM_MANAGE,
            self::LISTINGS_VIEW,
            self::LISTINGS_MODERATE,
            self::SUBSCRIPTIONS_VIEW,
            self::PLANS_VIEW,
            self::PLANS_MANAGE,
            self::REPORTS_VIEW,
            self::REPORTS_MANAGE,
            self::BRANDING_MANAGE,
        ];
    }
}

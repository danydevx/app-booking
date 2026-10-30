<?php

namespace Modules\Platform\Support;

use App\Models\User;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class PlatformAccess
{
    public const ROLE = 'platform_superadmin';

    /**
     * Ensure the platform role and its permissions exist.
     */
    public static function ensure(): RoleContract
    {
        $role = Role::findOrCreate(self::ROLE, 'web');

        $permissions = collect(PlatformPermission::all())
            ->map(fn (string $name): PermissionContract => Permission::findOrCreate($name, 'web'))
            ->all();

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    public static function isSuperadmin(User $user): bool
    {
        return $user->hasRole(self::ROLE);
    }
}

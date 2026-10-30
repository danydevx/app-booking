<?php

namespace App\Support\Platform;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class PlatformAccess
{
    public const ROLE = 'platform_superadmin';

    /**
     * Ensure the platform role and its permissions exist.
     */
    public static function ensure(): Role
    {
        $role = Role::findOrCreate(self::ROLE, 'web');

        $permissions = collect(PlatformPermission::all())
            ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'))
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

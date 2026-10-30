# Phase 2 — Accounts, teams and permissions

**Status: PASS**
**Date:** 2026-10-30
**Branch / commit:** `develop` — phase commit created with this report

## Scope

Converted the Starter Kit's Teams into the product's Cuenta boundary, added
protected platform-level permissions with Spatie, a superadmin command, a guarded
`/platform` area and account billing authorization. No second account system was
created.

## Packages

| Package | Version | Purpose |
|---|---|---|
| `spatie/laravel-permission` | 8.3.2 | Platform roles and permissions |

Published `config/permission.php` and
`database/migrations/2026_10_30_223008_create_permission_tables.php`; migration
ran locally.

## Account model

- Team = Cuenta.
- User may belong to multiple accounts; selected team remains the data boundary.
- Existing Starter Kit team membership roles (`owner`, `admin`, `member`) remain
  the source of truth for account membership.
- Spatie is used for platform-level access, not as a parallel tenant-role system.
- Added `TeamPolicy::manageBilling`, owner-only, for phase 5 billing routes.
- User-facing Team labels in the account switcher, settings, list, edit page and
  related modals now say Cuenta.

## Platform access

Added:

- `App\Support\Platform\PlatformPermission` with the platform permission catalog.
- `App\Support\Platform\PlatformAccess` to create/sync the protected
  `platform_superadmin` role.
- `app:make-platform-superadmin {email} {--force}` command:
  - requires an existing user;
  - requires verified email;
  - requires confirmation unless `--force`;
  - is idempotent;
  - logs the grant event.
- `/platform` route behind `auth`, `verified` and `can:platform.access`.
- Minimal platform dashboard with account/user counts.
- `platform.isSuperadmin` shared Inertia prop and a conditional "Plataforma" nav
  item.

The public registration flow does not assign the platform role.

## Trait collision

Spatie's `HasRoles` and the Starter Kit's `HasTeams` both define `teams`. The User
model resolves the collision in favor of the Starter Kit relation:

```php
HasTeams::teams insteadof HasRoles;
HasRoles::teams as permissionTeams;
```

This preserves the account boundary and keeps Spatie permissions available.

## Tests

| Command | Result |
|---|---|
| `php artisan test` | PASS — 100 tests / 367 assertions |
| New focused tests | PASS — 9 tests / 39 assertions |
| `npm run build` | PASS — 3,616 modules transformed |
| `vendor/bin/pint --test` | PASS |
| `php artisan route:list --path=platform` | One protected route |
| Bootstrap/jQuery scan | No matches |

Coverage includes:

- guest redirect from `/platform`;
- normal user receives 403;
- superadmin can open the platform dashboard;
- command is idempotent and creates all permissions;
- command rejects unknown and unverified users;
- registration cannot grant the platform role;
- only the account owner can manage billing;
- only account members can view the account.

## Residual risks

- Platform moderation screens are a phase 6 deliverable; phase 2 only exposes a
  protected placeholder dashboard.
- Account ownership management remains the Starter Kit implementation.
- No Stripe, listings, maps or domain data were added.
- `permissionTeams` is only a collision alias; Spatie Teams mode is not enabled.

## Next phase

Phase 3 — listings, media, public catalog, map and radius search.

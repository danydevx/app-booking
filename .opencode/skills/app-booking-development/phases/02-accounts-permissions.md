# Phase 2 — Accounts, teams and permissions

**Status: NOT STARTED**

## Objective

Turn the Starter Kit's Teams feature into the product's **Cuenta** boundary and add
platform-level authorization with Spatie. A user may belong to multiple accounts;
the selected account scopes all owner-side data.

## Inspect before coding

- Confirm the generated Team model, team membership table, invitation flow,
  current-team middleware, team switching and team routes.
- Confirm whether the starter kit already has an owner/admin/member role concept.
- Do not rename database tables or routes merely to use the word "Account". Use
  product labels in the UI and keep the framework's team conventions internally.

## Account model

- **Team = Cuenta = tenant/business boundary.**
- One account can own multiple listings, subject to plan limits.
- A user can belong to more than one account and switch the active account.
- Only an account owner can manage billing, account settings and team membership.
- Do not create a second `accounts` table or a second current-account resolver.
- Do not add `team_id` to public request input as authorization.

## Packages

```bash
composer require spatie/laravel-permission
php artisan vendor:publish \
  --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Use Spatie for platform/global permissions in the MVP. Avoid enabling two competing
tenant-role systems: the Starter Kit's team membership role remains the source of
truth for account membership; Spatie must not create a parallel team role model
unless the implementation explicitly migrates both.

## Platform role

Create a platform superadmin role that is never assignable by public registration
or account invitation.

Recommended permissions/roles:

```text
role: platform_superadmin
permissions:
  platform.access
  platform.accounts.view
  platform.accounts.suspend
  platform.listings.view
  platform.listings.moderate
  platform.subscriptions.view
```

Add an Artisan command that creates/promotes a platform superadmin by existing
user email:

```bash
php artisan app:make-platform-superadmin {email}
```

The command must:

- require an existing verified user;
- require an explicit confirmation;
- never create a password;
- never print secrets;
- be safe to run more than once;
- record an audit entry if the activity log is available.

Do not seed a real superadmin password.

## Access rules

- Account owner: account settings, listings, leads, customers, deposits, billing.
- Account member (future): only explicitly granted account capabilities.
- Platform superadmin: platform area only; do not add an implicit global bypass to
  tenant policies.
- Unauthenticated visitor: public catalog and inquiry form only.
- Platform routes use a dedicated middleware/permission such as
  `can:platform.access`.
- Team-owned routes resolve the current team and check membership/ownership through
  policies.

## UI

- Replace user-facing "Team" labels with "Cuenta" where it refers to the business.
- Keep the starter kit's account selection/switcher pattern.
- Add a minimal account settings area if not already present.
- Do not redesign the shell or introduce a new navigation library.

## Tests

- User A cannot access User B's account settings or team member list.
- A user with two accounts only sees data for the selected account.
- Public registration cannot assign `platform_superadmin`.
- A normal owner cannot reach `/platform`.
- The command promotes only an existing verified user and is idempotent.
- Account owner can manage account; non-owner member cannot manage billing/team.

## Commands

```bash
php artisan test
npm run build
vendor/bin/pint --test
php artisan route:list
```

## Exit criteria

- Teams are clearly treated as Cuentas in the UI and domain code.
- Platform superadmin exists as a protected role.
- Account switching and tenant policies are tested.
- No duplicate account/tenant system.
- No Bootstrap or jQuery.
- Phase report and status updated.

## Do not

- Do not build a full RBAC admin UI in this phase.
- Do not create a separate `accounts` table.
- Do not implement Stripe yet.
- Do not add Admin/Superadmin product screens beyond the minimum guard needed.

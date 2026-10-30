# Phase 6 — Platform superadmin and moderation

**Status: NOT STARTED**

## Objective

Give the platform operator a protected area to review accounts, subscriptions and
published listings, investigate reports, and suspend accounts or listings for fraud
or terms violations. Keep tenant data isolated from platform tools except for the
explicit moderation views.

## Packages

```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish \
  --provider="Spatie\Activitylog\ActivitylogServiceProvider" \
  --tag="activitylog-migrations"
php artisan migrate
```

Use activity logging for moderation and privilege changes. Do not log secrets or
full personal data in activity properties.

## Platform access

- Route prefix: `/platform`.
- Guard: authenticated user with `platform.access` / `platform_superadmin`.
- No public registration path can grant this role.
- Normal account owners receive 403.
- Platform area uses its own layout/navigation, reusing the starter kit's design
  tokens. Do not create a Bootstrap admin theme.
- No impersonation in V1. If added later, require explicit audit and consent.

## Screens and actions

### Overview

- Account count, paid/free/canceled counts, listing counts, pending reports.
- Recent signups and recent subscription changes.

### Accounts

- Search by name, owner email or Stripe customer ID.
- Show plan, subscription status, listing count, created date and suspension state.
- View account detail with owner, members, listings and billing status.
- Suspend/restore account with a required reason and audit entry.
- Suspension must:
  - block owner login to account-scoped areas or clearly restrict access;
  - hide the account's listings from public search;
  - keep data intact;
  - be reversible;
  - send an explanatory notification if appropriate.
- Do not delete accounts from the platform UI in V1.

### Listings

- Review published/suspended listings and their media.
- Suspend/restore a listing with reason and audit entry.
- Suspended listings disappear from the public catalog immediately.
- Do not edit owner content silently.

### Subscriptions

- Read-only view of plan, Stripe status and current period.
- Link to Stripe customer in the Stripe dashboard (manual, no embedded secrets).
- Do not create charges or change subscriptions from the platform area.

### Reports

- Public “report listing” form, rate limited.
- Report fields: reason category, message, reporter email optional, listing.
- Platform queue: open, reviewing, resolved, dismissed.
- Resolving a report records the action and reason.

## Audit

Log at minimum:

- superadmin role grant/revoke;
- account suspension/restore;
- listing suspension/restore;
- report resolution;
- billing status changes initiated by an operator (if any).

Include actor, subject, action, reason and timestamp. Do not include passwords,
tokens, full card data or unnecessary personal data.

## Tests

- Guest and normal owner receive 403/redirect from `/platform`.
- Superadmin can access the platform dashboard.
- Account A owner cannot see account B in owner routes.
- Suspending an account hides its listings from public search.
- Suspending a listing makes its public URL unavailable.
- Restore returns the previous state without data loss.
- Suspension requires a reason.
- Actions write an audit entry with actor.
- Report form is rate limited and cannot target draft/foreign listings.
- No Bootstrap/jQuery.

## Commands

```bash
php artisan test
npm run build
vendor/bin/pint --test
```

## Exit criteria

- Superadmin can review accounts, subscriptions and listings.
- Suspension and restoration are audited and reversible.
- Public visibility changes immediately.
- Tenant isolation remains intact.
- Phase report and status updated.

## Do not

- Do not implement AI fraud scoring in this phase.
- Do not add destructive account deletion.
- Do not use a global superadmin bypass in normal tenant queries.
- Do not build a second admin theme or install AdminLTE.

# Phase 6 — Platform superadmin and moderation

**Status: PASS**
**Date:** 2026-10-31
**Branch / commit:** `phase-6-platform-admin` / phase-6 commit

## Scope

Extended `Modules/Platform/` with a protected moderation area:

- Platform layout and navigation reusing the starter-kit design tokens.
- Overview with account, plan, listing and report counters plus recent
  signups, subscription updates and moderation activity.
- Account search and detail with owner, members, listings and billing state.
- Account suspension and restoration with required reason, audit entry and
  queued email notification.
- Listing review with media, suspension and restoration preserving the previous
  status.
- Read-only subscription view with Stripe dashboard links.
- Public report form, rate limited queue and resolution workflow.
- Activity logging for account and listing moderation plus superadmin
  grant/revoke through a new revoke command.

## Decisions

- Suspending an account blocks every team-scoped route (listings, bookings,
  billing, settings) with 403 and hides its listings from public search, public
  listing pages, availability pages and inquiry submission. Data is preserved.
- Login itself is not blocked; users can still sign in and read account-level
  errors. Support is handled by email.
- A platform-suspended listing keeps its previous status in
  `suspended_from_status`. Restore returns to that status, so a published listing
  becomes public again and a draft returns to draft.
- Owners cannot publish, unpublish or delete a platform-suspended listing while
  moderation is active, so moderation cannot be bypassed. They can still read
  and edit it.
- Resolving a report with action `listing_suspended` suspends the listing in the
  same transaction and writes both audit entries.
- Report submissions are limited to 5 per minute per IP with a dedicated
  `listing-reports` rate limiter, separate from inquiry throttling.
- Only published listings from active accounts can be reported. A deleted
  listing cascades its reports.
- The platform area has no impersonation, no destructive account deletion and no
  subscription mutation. Subscriptions link out to the Stripe dashboard.
- Platform access remains CLI-only (`app:make-platform-superadmin`,
  `app:revoke-platform-superadmin`); registration never grants the role.

## Packages and versions

| Package                      | Version | Why                                  |
| ---------------------------- | ------- | ------------------------------------ |
| `spatie/laravel-activitylog` | 4.12.0  | Moderation and privilege audit trail |

## Database changes

Applied locally:

- `create_activity_log_table`, `add_event_column_to_activity_log_table`,
  `add_batch_uuid_column_to_activity_log_table` — published Spatie migrations.
- `add_suspension_to_teams_table` — `suspended_at` (indexed) and
  `suspension_reason`.
- `add_moderation_to_listings_table` — `suspended_at` (indexed),
  `suspended_from_status` and `suspension_reason`.
- `create_listing_reports_table` — listing and account foreign keys,
  reporter email, reason, message, status, resolution action/note, handler and
  timestamps with queue indexes.

## Testing

| Command                    | Result | Tests / assertions         |
| -------------------------- | ------ | -------------------------- |
| `php artisan test`         | PASS   | 181 tests / 959 assertions |
| Platform module tests      | PASS   | 21 tests / 229 assertions  |
| `npm run build`            | PASS   | 3,678 modules transformed  |
| `vendor/bin/pint --test`   | PASS   | —                          |
| `composer run types:check` | PASS   | 0 PHPStan errors           |
| `npm run types:check`      | PASS   | 0 TypeScript errors        |
| Bootstrap/jQuery scan      | PASS   | no matches                 |

Coverage includes:

- superadmin opens dashboard, accounts, account detail, listings, listing
  detail, subscriptions and reports;
- normal owners receive 403 on every platform screen and moderation action;
- suspension requires a reason;
- account suspension hides listings from search and public URLs, blocks member
  routes, sends the owner a notification and writes an audit entry with causer;
- restoration clears the suspension, restores public visibility without data
  loss and audits the action;
- listing suspension makes the public URL unavailable, keeps the listing out of
  search, blocks owner publish/delete and audits the action;
- restoring a published listing returns it to published and a draft to draft;
- public report form stores account-scoped reports and returns 429 after five
  attempts per minute;
- draft listings and suspended accounts cannot be reported;
- `other` reason requires a message;
- resolving a report requires action and note, records handler and timestamp,
  suspends the listing when selected and audits both actions;
- normal owners cannot resolve reports;
- superadmin grant and revoke are audited.

## Authorization and isolation checks

- `/platform` requires `auth`, `verified` and the `platform.access` permission.
- Each screen and action also requires its granular platform permission.
- Public reporters never provide `team_id`; the report copies it from the
  listing.
- Platform reads are explicit and separate from tenant scopes; no global
  superadmin bypass was added to tenant queries.
- Team-scoped routes still resolve the current account from the authenticated
  membership, never from request input.
- The Stripe dashboard link only uses the customer id; no secret or price id is
  exposed.

## No Bootstrap / jQuery check

Both dependency and import scans returned no matches. The platform area uses the
starter-kit layout primitives, shadcn-vue components and Tailwind utilities.

## Manual verification

- `php artisan migrate` applied the activity log, suspension, moderation and
  report migrations locally.
- `php artisan route:list --path=platform` lists 12 guarded routes; the public
  report routes are `/terrazas/{slug}/reportar`.
- The full feature suite exercises every screen and moderation transition with
  Inertia assertions, including the public visibility changes.
- No real Stripe, mail or external service was used; notifications are faked in
  tests and queued in production.

## Residual risks

- Suspension notifications are queued; a worker must be running in staging and
  production.
- An owner can still delete a reported (non-suspended) listing, which cascades
  and removes its reports. Preserving reports after owner deletion would require
  a product decision.
- The audit UI shows the latest 10 entries per subject; a global audit browser is
  not built.
- Reports are not deduplicated; the queue may contain repeated submissions from
  different IPs.
- Platform staff management is CLI-only and relies on the existing superadmin
  command.
- Account suspension blocks billing access; an owner with a payment issue must
  contact support.

## Next phase

Phase 7 — account branding and communications.

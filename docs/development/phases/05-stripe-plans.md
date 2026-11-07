# Phase 5 — Stripe subscriptions, plans and entitlements

**Status: PASS**
**Date:** 2026-10-31
**Branch / commit:** `phase-5-stripe-plans` / phase-5 commit

## Scope

Built the billing capability inside `Modules/Billing/`:

- Cashier customer/subscription tables attached to the Team (Cuenta), never to an
  individual user.
- Config-backed plan catalog (`config/billing.php`) with Free and Pro limits,
  features, interval and Stripe price id.
- `Entitlements` service: `planFor`, `allows`, `limit`, `canCreateListing`,
  `canUpload` and subscription status resolution.
- Owner-only Stripe Checkout for paid plans and Customer Portal redirect.
- Cashier webhook route with signature verification and duplicate-delivery
  logging keyed by Stripe event id.
- Server-side enforcement of listing, photo, video and customer-CRM limits in the
  Listings and Bookings modules.
- Billing page with current plan, limits, usage, subscription state, invoice list
  with hosted Stripe invoice links, portal button and upgrade CTAs.

## Decisions

- The Team is the billable entity; `Cashier::useCustomerModel(Team::class)`.
- Only the account owner can open billing, start checkout or open the portal.
- Grace policy:
    - active, trialing and canceling subscriptions keep paid access;
    - past-due keeps paid access for 3 days after the last subscription update;
    - after that, or after a canceled subscription's `ends_at`, the account falls
      back to Free limits without deleting data.
- Paid state is derived from Cashier subscription rows synced by Stripe webhooks;
  a success redirect alone never unlocks anything.
- Price ids stay in config/env. Vue receives only public plan metadata plus a
  `price_configured` boolean.
- Invoice list uses Stripe hosted invoice URLs; no PDF proxy route is added.
- Webhook duplicates are recorded in cache for 24 hours; Cashier's own sync is
  upsert-based, so replays are safe.

## Packages and versions

| Package           | Version | Why                                    |
| ----------------- | ------- | -------------------------------------- |
| `laravel/cashier` | 16.9.0  | Stripe subscriptions, portal, invoices |

## Database changes

Published Cashier migrations and applied locally:

- `create_customer_columns` — adds `stripe_id`, `pm_*` and `trial_ends_at` to
  `teams`.
- `create_subscriptions_table` — `team_id` foreign key, `type`, `stripe_id`,
  `stripe_status`, `stripe_price`, quantity and period columns.
- `create_subscription_items_table` — items linked to subscriptions, `team_id`
  cascades with the account.
- Cashier meter columns on `subscription_items`.

No migrations were edited after being applied.

## Testing

| Command                    | Result | Tests / assertions         |
| -------------------------- | ------ | -------------------------- |
| `php artisan test`         | PASS   | 167 tests / 764 assertions |
| Phase 5 focused tests      | PASS   | 20 tests / 114 assertions  |
| `npm run build`            | PASS   | 3,658 modules transformed  |
| `vendor/bin/pint --test`   | PASS   | —                          |
| `composer run types:check` | PASS   | 0 PHPStan errors           |
| `npm run types:check`      | PASS   | 0 TypeScript errors        |
| Bootstrap/jQuery scan      | PASS   | no matches                 |

Coverage includes:

- Free account cannot create a second listing or exceed 5 photos / 0 videos;
- active Pro subscription allows 5 listings, 20 photos and 3 videos;
- customer CRM redirects Free accounts to billing;
- successful checkout redirect without a webhook leaves the Free plan;
- valid subscription webhook activates Pro entitlements;
- invalid webhook signature returns 403 and creates nothing;
- duplicate webhook delivery is idempotent;
- past-due keeps Pro only during the grace window;
- canceling keeps Pro until the paid period ends;
- non-owner members cannot open billing or start checkout;
- another account owner receives 403 on the billing page;
- price ids and secrets are absent from Inertia props;
- invoices are empty without a Stripe customer or secret (no network call).

`phpunit.xml` now blanks `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
and `STRIPE_PRICE_PRO` so the suite never calls the real Stripe API or depends on
local secrets.

## Authorization and isolation checks

- Billing routes require `auth`, `verified` and `EnsureTeamMembership`.
- `BillingPolicy::manage` allows only the team owner.
- The billable model is resolved from `current_team_id`, never from request
  input.
- Cross-account billing access returns 403 in tests.
- Plan and limit enforcement happens in backend controllers before writes, not
  only in Vue.
- No Stripe secret or price id is serialized to the frontend.

## No Bootstrap / jQuery check

```bash
npm ls bootstrap jquery bootstrap-vue admin-lte   # empty
grep -R "bootstrap.min|bootstrap.css|from 'jquery'" resources package.json
# no matches
```

## Manual verification

Performed locally against the Stripe test account with `stripe listen
--forward-to 127.0.0.1:8000/stripe/webhook`:

- Checkout Session created for Pro: host `checkout.stripe.com`, id `cs_...`.
- Customer Portal Session created: host `billing.stripe.com`.
- Real test subscription created in Stripe for a local team, then synced only
  through webhooks:
    - `customer.subscription.created` → 200;
    - `invoice.payment_succeeded` → 200;
    - local subscription row `active`, plan `pro`, listing limit 5, CRM enabled.
- `cancel_at_period_end` update:
    - `customer.subscription.updated` → 200;
    - local `ends_at` set to 2026-11-30, status `canceling`, plan still `pro`.
- Invoice sync returned one paid invoice for 499.00 MXN with a hosted URL.

Test-mode product "Terraza Pro" (499.00 MXN/month) and price
`price_1UWkGZPrWL9Ue4NxovJB4Sgg` exist only in the Stripe test account and local
`.env`. No secrets or price ids are committed. Temporary users, teams,
subscriptions and Stripe test customers created for the run were deleted
afterwards.

## Residual risks

- Completing the Stripe-hosted Checkout form itself requires a browser; the
  session creation, customer, subscription, invoice and portal flows were
  verified through the real test-mode API and webhooks.
- The 499.00 MXN test price is provisional; the product decision on final pricing
  remains deferred.
- Local webhook delivery uses the Stripe CLI secret. Production and staging need
  their own dashboard endpoint and `STRIPE_WEBHOOK_SECRET`.
- Dunning emails are Stripe's; no custom recovery campaign exists yet.
- Invoice history shows hosted links only, without an in-app PDF download route.

## Next phase

Phase 6 — platform admin, moderation, suspensions and audit. It can start because
phase 5 exit criteria are met.

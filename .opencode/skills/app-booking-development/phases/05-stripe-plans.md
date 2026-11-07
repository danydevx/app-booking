# Phase 5 — Stripe subscriptions, plans and entitlements

**Status: NOT STARTED**

## Objective

Let an account owner subscribe to a monthly paid plan with Stripe, manage billing
through Stripe's customer portal, and have the backend enforce plan limits and
capabilities. Stripe test mode only until launch approval.

## Billing model

- The **Team/Cuenta is the billable entity**, not the individual user. A team can
  have multiple users later without duplicating subscriptions.
- Only the account owner can start, change or cancel billing.
- The Free plan does not require Stripe.
- A platform superadmin can view subscription state but cannot impersonate an owner
  or change a subscription outside Stripe's supported flow.
- Renter deposits remain outside Stripe and are recorded manually in phase 4.

## Packages

```bash
composer require laravel/cashier
php artisan vendor:publish --tag="cashier-migrations"
php artisan vendor:publish --tag="cashier-config"
```

Adapt the published migrations/configuration so the billable model is the Team
model. Do not leave subscriptions attached to individual users. Verify the cashier
customer and subscription tables reference the account/team correctly.

## Configuration

```ini
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
CASHIER_CURRENCY=mxn
CASHIER_CURRENCY_LOCALE=es_MX
```

Add real values only to local `.env` or deployment secrets. `.env.example` contains
empty keys only.

## Plans

Create a small `plans` table or equivalent config-backed catalog:

- `slug` (`free`, `pro`);
- display `name`;
- `stripe_price_id` nullable (null for Free);
- monthly flag;
- `limits` JSON: `listings`, `photos_per_listing`, `videos_per_listing`;
- `features` JSON: `customers`, `ai_posts`, etc.;
- `active` boolean.

Recommended initial limits (confirm in phase 0):

| Capability         | Free |   Paid |
| ------------------ | ---: | -----: |
| Listings           |    1 |      5 |
| Photos per listing |    5 |     20 |
| Videos per listing |    0 |      3 |
| Customer CRM       |   No |    Yes |
| AI social posts    |   No | Future |

Do not hardcode the Stripe price IDs in Vue or controllers. Read them from the plan
catalog/configuration.

## Entitlements

Build a small backend service, e.g. `Entitlements`:

```text
planFor(Team): Plan
allows(Team, feature): bool
limit(Team, key): int
canCreateListing(Team): bool
canUpload(Team, listing, mediaType): bool
```

Rules:

- Resolve the active plan from Cashier subscription state plus the plan catalog.
- Treat past-due/canceled/expired subscriptions according to an explicit grace
  policy. Recommended: keep access until the paid period ends, then fall back to
  Free limits without deleting data.
- Enforce limits and features on the server for every create/upload/action route.
- UI may hide unavailable actions, but must never be the only enforcement.
- Free downgrade must preserve existing data while preventing new over-limit
  creation. Do not delete a customer's content automatically.

## Stripe flow

- Checkout for paid plan: owner selects plan, backend creates Checkout session with
  team metadata, success/cancel URLs.
- Customer Portal: owner can update payment method, view invoices and cancel.
- Webhooks: configure Cashier's webhook route and verify signatures.
- Handle at minimum: checkout completed, subscription created/updated/deleted,
  invoice payment succeeded/failed. Rely on Cashier's subscription sync, but test
  the application's entitlement behavior after status changes.
- Make webhook handling idempotent. Log event IDs and ignore duplicates safely.
- Never trust a success redirect alone to activate a subscription; the webhook is
  the source of truth.
- Use Stripe test clocks or test-mode events for time-based tests where possible.

## UI

- Billing page showing current plan, limits, usage and invoices.
- Upgrade CTA from listing/customer limits.
- Customer Portal button.
- Clear state for past due, canceled, grace period and Free fallback.
- Use existing shadcn-vue components; do not add a billing UI framework.

## Tests

- Free account can create only the Free listing/photo limits.
- Paid account limits are enforced after a subscription webhook.
- Redirect success without webhook does not unlock features.
- Canceled/past-due behavior follows the documented grace policy.
- Non-owner account member cannot open billing or checkout.
- User A cannot manage User B's subscription.
- Webhook signature is verified; invalid signatures are rejected.
- No Stripe secret or price ID is exposed to Vue props.
- `php artisan test`, `npm run build`, Pint pass.

## Exit criteria

- Stripe test-mode subscription works end-to-end locally or in staging.
- Backend entitlements match the plan catalog.
- No live charges enabled.
- No PayPal introduced.
- Phase report and status updated.

## Do not

- Do not enable live Stripe keys.
- Do not charge renters or implement marketplace payouts.
- Do not create a second subscription system.
- Do not make plan limits client-only.

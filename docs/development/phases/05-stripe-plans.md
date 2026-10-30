# Phase 5 — Stripe subscriptions, plans and entitlements

**Status: PARTIAL**
**Date:** 2026-10-30
**Branch:** `phase-5-stripe-plans`

## Implemented

- Cashier 16.9.0 with `Team` as the billable model.
- Cashier customer columns on `teams`; subscriptions and items keyed by
  `team_id`.
- Config-backed Free/Pro catalog in `config/billing.php`.
- `Entitlements` resolves plan, limits and features from Cashier state.
- Free limits enforced server-side:
    - 1 listing;
    - 5 photos per listing;
    - 0 videos;
    - customer CRM locked.
- Pro limits:
    - 5 listings;
    - 20 photos;
    - 3 videos;
    - customer CRM enabled.
- Owner-only billing page, checkout and Stripe Customer Portal.
- Checkout uses the configured price ID and team metadata.
- Webhook signature verification through Cashier at `/stripe/webhook`.
- Webhook event IDs logged; duplicate deliveries are idempotent.
- Billing page exposes no Stripe price IDs or secrets.
- Listing and customer UI show upgrade paths when limits are reached.

## Grace policy

- Active, trialing and cancellation grace periods keep paid access.
- `past_due` keeps paid access for 3 days after the last subscription update,
  then falls back to Free.
- Existing data is never deleted on downgrade; only new over-limit actions are
  blocked.

## Database

Published and adapted Cashier migrations:

- customer columns on `teams`;
- `subscriptions.team_id`;
- `subscription_items.subscription_id`;
- meter columns.

## Verification

| Command                        | Result                            |
| ------------------------------ | --------------------------------- |
| `php artisan test`             | PASS — 166 tests / 751 assertions |
| Phase 5 focused tests          | PASS — 19 tests / 101 assertions  |
| `npm run build`                | PASS — 3,658 modules              |
| `vendor/bin/pint --test`       | PASS                              |
| `composer types:check`         | PASS — 0 PHPStan errors           |
| `npm run types:check`          | PASS                              |
| Vite check on touched frontend | PASS                              |
| Bootstrap/jQuery scan          | PASS — no matches                 |

Coverage includes:

- free listing/photo/video limits;
- Pro limits after an active subscription;
- past-due grace and cancellation fallback;
- CRM feature gating;
- non-owner billing denial;
- cross-account billing denial;
- checkout guards without Stripe configuration;
- success redirect not unlocking features;
- signed webhook activation of Pro;
- invalid signature rejection;
- duplicate webhook idempotency;
- no price ID in Inertia props.

## Blocker

The Stripe test-mode end-to-end checkout/portal flow was not executed because
local `.env` has no `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` or
`STRIPE_PRICE_PRO`. Code is test-mode ready, but no live charges were enabled and
no Stripe API calls were made.

To finish the exit criteria:

1. Add Stripe test keys and a test Pro price ID locally.
2. Run Checkout and confirm webhook-driven activation.
3. Open the Customer Portal and verify invoices/payment method.
4. Re-run the phase checks and mark PASS.

## Residual risks

- No live Stripe keys or charges.
- No automatic plan-change proration UI beyond Checkout/Portal.
- Webhook duplicate logging does not short-circuit Cashier; Cashier's
  `updateOrCreate` handling remains the idempotency guard.
- Billing page does not render an invoice list; invoices live in Stripe Portal.

## Next phase

Phase 6 — platform superadmin operations, account moderation and audit.

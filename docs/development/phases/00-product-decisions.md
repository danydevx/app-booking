# Phase 0 — Product decisions

**Status: PASS**
**Date:** 2026-10-30
**Branch / commit:** `main` — documentation commit created with this phase

## Scope

Locked the MVP decisions required before scaffolding. No application code or
packages were created in this phase.

## Decisions

Recorded in `docs/decisions/0001-mvp.md`:

- Starter Kit Team = Cuenta.
- One account can own multiple listings, subject to plan limits.
- Guest visitors can send rental inquiries without an account.
- Inquiries create leads and do not block dates until the owner accepts.
- Free: 1 listing, 5 photos, 0 videos, no CRM, no AI posts.
- Paid: 5 listings, 20 photos, 3 videos, CRM; price configured in Stripe.
- MXN, Stripe subscriptions only, no PayPal.
- Deposits are recorded manually; the platform does not process renter payments.
- WhatsApp V1 is a click-to-chat link.
- Leaflet/OpenStreetMap for maps.
- Spanish UI first, UTC storage, account timezone `America/Mexico_City`.
- Platform moderation can suspend accounts/listings.

## Unresolved non-blockers

- Final subscription price and trial.
- AI post provider and quotas.
- Automated WhatsApp Business API.
- Manual pre-approval of every listing.
- Advanced staff roles.

## Testing

No application tests in this phase; only decision documentation.

## Next phase

Phase 1 — bootstrap Laravel 13 + Vue Starter Kit.

# Phase 4 — Availability, inquiries, customers and deposits

**Status: PASS**
**Date:** 2026-10-30
**Branch:** `phase-4-availability`

## Scope

Built the operational booking flow inside `Modules/Bookings/`:

- owner-managed blocked/booked dates;
- public availability page and inquiry form;
- owner inquiry inbox, detail and status transitions;
- account-scoped customers linked from inquiries;
- manual deposit records;
- queued email notifications;
- WhatsApp click-to-chat links with sanitized prefilled text.

## Module layout

```text
Modules/Bookings/
├── src/
│   ├── Actions/
│   ├── Database/Factories/
│   ├── Enums/
│   ├── Http/Controllers/
│   ├── Http/Requests/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Support/
│   └── BookingsServiceProvider.php
├── routes/web.php
├── database/migrations/
├── resources/js/
│   ├── components/
│   ├── pages/
│   └── types.ts
└── tests/Feature/
```

Shared public shell and pagination were moved from `Listings` to
`resources/js/components/` because both modules use them.

## Decisions

- A public inquiry never blocks dates. Only owner/admin acceptance creates
  `booked` rows.
- Date ranges are inclusive. Acceptance is transactional and the unique
  `(listing_id, date)` constraint is the final concurrency guard.
- Re-accepting an already accepted inquiry is idempotent.
- Declining or cancelling releases only rows linked to that inquiry; manually
  blocked dates remain untouched.
- New and contacted inquiries can be accepted. Accepted inquiries can be
  cancelled. Final inquiries cannot be accepted again.
- Customers are unique by account + email. The same person can be a separate
  customer in another account.
- Deposits are manual records only. No renter payment processing is introduced.
- Owner/admin can accept, decline, cancel and manage deposits. Any account member
  can view and update follow-up.
- Notifications implement `ShouldQueue`; no SMTP call is made synchronously.
- Public inquiry submission is rate limited to five attempts per minute and has a
  honeypot plus consent and input-size validation.
- WhatsApp is a `wa.me` link with URL-encoded user text. No automated sending.

## Database changes

Applied locally:

- `create_customers_table` — account-scoped customer records, unique by
  `(team_id, email)`.
- `create_booking_inquiries_table` — visitor data, inclusive dates, status,
  consent, anti-abuse metadata and transition timestamps.
- `create_listing_unavailability_table` — blocked/held/booked dates, unique by
  `(listing_id, date)`, optional inquiry and creator links.
- `create_booking_deposits_table` — integer minor units, currency, status,
  method, reference and received date.

## Testing

| Command                              | Result | Tests / assertions         |
| ------------------------------------ | ------ | -------------------------- |
| `php artisan test`                   | PASS   | 146 tests / 648 assertions |
| Phase 4 focused tests                | PASS   | 21 tests / 155 assertions  |
| `npm run build`                      | PASS   | 3,656 modules transformed  |
| `vendor/bin/pint --test`             | PASS   | —                          |
| `composer types:check`               | PASS   | 0 PHPStan errors           |
| `npm run types:check`                | PASS   | —                          |
| Vite check on module/shared frontend | PASS   | 17 files, no warnings      |

Coverage includes:

- public inquiry persisted, linked to account customer and notifications queued;
- draft/suspended listings reject inquiries;
- past/oversized/out-of-range dates rejected;
- honeypot and rate limiting;
- owner block/unblock dates and booked-date protection;
- public availability page exposes unavailable dates;
- inclusive acceptance creates one booked row per day;
- acceptance is idempotent;
- blocked/overlapping dates prevent acceptance;
- decline/cancel release only linked dates;
- cross-account inquiry, customer and deposit access returns 404;
- deposit amount converted to integer minor units and received timestamp set;
- owner inbox, inquiry detail, customers and availability pages render.

## Authorization and isolation checks

- Booking routes require `auth`, `verified` and `EnsureTeamMembership`.
- Inquiry and customer policies authorize view and management by account.
- Accept/decline/cancel/deposit operations require owner or admin.
- Every inquiry/customer/deposit operation checks the current team before the
  action, returning 404 for another account.
- Public availability only renders published listings.
- Customer creation always uses the listing's account, never a request value.

## No Bootstrap / jQuery check

Both dependency and import scans returned no matches.

## Manual verification

- Local public URLs return HTTP 200: `/`, `/terrazas`,
  `/terrazas/terraza-jardin-reforma/disponibilidad`, `/login`.
- Local demo data was seeded outside Git with two published listings, three
  inquiries, one accepted booking, one received deposit and one blocked date.
- `storage:link` is configured locally.

## Residual risks

- Inquiry expiry implemented after phase 4: `bookings:expire-inquiries` runs
  daily and expires new/contacted inquiries after their requested end date
  (configurable grace via `BOOKINGS_EXPIRE_AFTER_END_DAYS`).
- No payment processing; deposits remain manual as required.
- No automated WhatsApp API.
- Email delivery uses the local `log` mailer and queued jobs; a worker is needed
  to process queued notifications.
- Calendar is intentionally lightweight and read-only; dates are blocked through
  the owner form.
- Account suspension and plan limits remain phase 5/6 work.

## Next phase

Phase 5 — Stripe subscriptions, plans, entitlements and webhooks.

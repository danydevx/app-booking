# Phase 4 — Availability, booking inquiries, customers and deposits

**Status: NOT STARTED**

## Objective

Implement the operational core: owners block non-rentable dates, visitors submit
interest for available dates, owners follow up and accept/decline, and accepted
requests can become booked dates with a customer and manual deposit record.

## Data model

### `listing_unavailability`

One row per blocked/booked/held date:

- `listing_id` foreign key/index;
- `date` date, unique together with `listing_id`;
- `status` enum/string: `blocked`, `held`, `booked`;
- `reason` nullable;
- `booking_inquiry_id` nullable foreign key;
- `created_by` user nullable;
- timestamps.

Absence of a row means the date is available. The owner can block future dates for
maintenance or personal use.

### `booking_inquiries`

- `team_id`/account foreign key and `listing_id`;
- `customer_id` nullable;
- visitor `name`, `email`, `phone`, `whatsapp_phone`;
- `start_date`, `end_date` (inclusive semantics must be explicit and tested);
- `event_type`, `guests`, `message`;
- `status`: `new`, `contacted`, `accepted`, `declined`, `expired`, `cancelled`;
- `source` (`public_web` initially);
- `consent_at`, `ip_hash` or equivalent anti-abuse metadata;
- timestamps and status transition timestamps.

### `customers`

- `team_id`/account foreign key;
- `name`, `email`, `phone`, unique per account where reasonable;
- `notes`;
- timestamps.

### `booking_deposits`

- `booking_inquiry_id`;
- `amount_cents`, `currency`;
- `status`: `pending`, `received`, `refunded`, `cancelled`;
- `method` (cash/transfer/other), `reference`, `received_at`, `notes`;
- timestamps.

This is a record of a deposit the owner received outside the platform. Do not process
renter payments here.

## Booking rules

- A public inquiry does **not** block dates.
- Only an owner action can create `held` or `booked` dates.
- Accepting an inquiry must run in a transaction and must fail if any requested date
  is already blocked/held/booked.
- Re-accepting the same inquiry must be idempotent.
- Declining/cancelling must release only dates linked to that inquiry.
- Date ranges are inclusive; validate `end_date >= start_date` and a reasonable
  maximum stay.
- Use database unique constraints to prevent duplicate date rows; do not rely only
  on UI checks.
- All date operations use the listing/account timezone for input validation and
  store dates as date values, not ambiguous timestamps.

## Public flow

- Public availability view for a listing.
- “Me interesa rentarla” form: name, email, phone, dates, guests, message, consent.
- Guest submission; no visitor account required.
- Rate limiting, honeypot or equivalent anti-spam, and server-side validation.
- On success:
    - persist the inquiry;
    - create or link a customer only for the owning account, never across accounts;
    - queue an email to the owner and a confirmation to the visitor;
    - show a success state without exposing another account's data.

## Owner flow

- Inbox of inquiries with filters.
- Detail view with visitor contact and dates.
- Actions: mark contacted, accept, decline, cancel, add internal note.
- Customer list/detail scoped to the account.
- Record/update deposit status and reference.
- Block/unblock dates.
- WhatsApp button builds a `https://wa.me/<phone>?text=...` link with a sanitized
  prefilled message. Do not auto-send WhatsApp in this phase.

## Email

Use queued Laravel mail notifications:

- owner notification for a new inquiry;
- visitor confirmation;
- owner notification when an inquiry is accepted/declined (optional but useful).

Do not block the HTTP response on SMTP. Configure a local log/Mailpit transport in
development.

## Tests

- Inquiry cannot be submitted for draft/suspended listings.
- Inquiry dates outside availability are rejected at acceptance, not silently
  booked.
- Two concurrent accepts cannot book the same dates twice.
- Inquiry A cannot see/mutate Inquiry B from another account.
- Customer/deposit records are account-scoped.
- Decline/cancel releases dates correctly.
- Public form is rate limited and rejects invalid/oversized input.
- Mail is queued, not sent synchronously in tests.
- WhatsApp link escapes user-controlled data.

## Commands

```bash
php artisan test
npm run build
vendor/bin/pint --test
```

## Exit criteria

- A complete public inquiry can be submitted and reaches the owner.
- An owner can accept it and mark the dates booked.
- Customer and manual deposit records work and are isolated by account.
- No payment processing is introduced.
- Phase report and status updated.

## Do not

- Do not charge the visitor or split payments.
- Do not implement automated WhatsApp sending.
- Do not build an AI CRM or social-post generator yet.
- Do not create a second availability system in Vue; the server is authoritative.

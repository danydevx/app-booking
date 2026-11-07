# Phase 0 — Product decisions

**Status: NOT STARTED**

## Objective

Lock only the decisions that would cause rework in phases 1–5. Do not install
packages or build application code in this phase.

## Recommended defaults

Use these unless the user changes them in this conversation. Record the accepted
decision in `docs/decisions/0001-mvp.md`.

| Decision               | Recommended MVP default                                                                                     |
| ---------------------- | ----------------------------------------------------------------------------------------------------------- |
| Account model          | Starter Kit Team = Cuenta; one owner can have multiple listings, subject to plan limits                     |
| Team members           | Owner only at launch; invitations/staff roles can be added later without changing the account model         |
| Public visitor account | Not required; booking inquiry is guest checkout                                                             |
| Booking inquiry effect | Creates a lead/request only. Dates are not blocked until the owner accepts and marks them booked            |
| Date model             | One row per listing/date with `available`, `blocked`, `held`, `booked`; absence means available             |
| Free plan              | 1 listing, 5 photos, 0 videos, no customer CRM, no AI posts                                                 |
| Paid plan              | 5 listings, 20 photos, 3 videos, customer CRM and future AI posts; exact price set in Stripe, not hardcoded |
| Billing currency       | MXN                                                                                                         |
| Subscription owner     | The Team/Cuenta, not the individual user                                                                    |
| Billing provider       | Stripe only; no PayPal in MVP                                                                               |
| Deposit                | Manual record by owner; platform does not process renter payments                                           |
| WhatsApp               | Click-to-chat `wa.me` link with prefilled message; no automated API                                         |
| Maps                   | Leaflet + OpenStreetMap tiles; Nominatim or manual coordinates for geocoding with caching                   |
| Search                 | Radius search with a bounding box query and exact distance refinement                                       |
| Moderation             | Owner can publish a complete listing; visitors can report; platform can suspend accounts/listings           |
| Language               | Spanish UI first; keep strings translatable                                                                 |
| Timezone               | Store UTC; account timezone defaults to `America/Mexico_City`                                               |
| Listing vertical       | `terrace` for V1; keep shared listing fields and a small validated `details` JSON seam for future verticals |

## Questions to ask if the user has not answered

1. Confirm one Account can own several terraces and that the paid limits above are
   acceptable.
2. Confirm a booking inquiry does not reserve the date until the owner accepts.
3. Confirm guests can send inquiries without creating an account.
4. Confirm WhatsApp V1 can be a click-to-chat link rather than automatic sending.
5. Confirm the map can use OpenStreetMap/Leaflet without a paid provider.
6. Confirm a listing can publish without manual pre-approval, with reporting and
   later suspension.

Do not block phase 1 on pricing amounts, final brand, legal copy or a paid map
provider. Those can be configured later.

## Deliverables

- `docs/decisions/0001-mvp.md` with the accepted/default decisions and date.
- Any unresolved item marked explicitly as a blocker or non-blocker.
- Updated phase status.

## Exit criteria

- The six questions above are either answered or explicitly accepted as defaults.
- No Laravel installation has been run yet.
- Phase 1 file has been read.

## Do not

- Do not scaffold Laravel in this phase.
- Do not create database tables.
- Do not install Composer/NPM packages.
- Do not write product UI.

# 0004 — Open product and operational decisions

**Date:** 2026-11-06
**Status:** Open — resolve before the relevant milestone

This ADR tracks decisions that are deliberately still open. When one is decided,
record the choice in a new ADR (or amend this one) instead of leaving it implicit.

## Before Stripe live

| Decision                 | Current state                               | Needed by   |
| ------------------------ | ------------------------------------------- | ----------- |
| Exact subscription price | Configured in Stripe via `STRIPE_PRICE_PRO` | Live launch |
| Trial length             | Not defined                                 | Live launch |
| Refund/support policy    | Not defined                                 | Live launch |
| Tax treatment (MXN)      | Not reviewed                                | Live launch |

## Before staging/production

| Decision                                  | Current state                                      | Needed by |
| ----------------------------------------- | -------------------------------------------------- | --------- |
| Hosting and domain                        | Not chosen                                         | Phase 8   |
| Staging environment                       | Not provisioned                                    | Phase 8   |
| Backup provider and retention             | Not chosen                                         | Launch    |
| Mail provider / from domain               | Local `log` mailer                                 | Launch    |
| Media storage                             | Local public disk                                  | Launch    |
| Error tracking / uptime / log aggregation | Not chosen                                         | Launch    |
| Legal pages (terms, privacy)              | Editable platform settings exist, text not written | Launch    |
| Data retention / deletion policy          | Not defined                                        | Launch    |

## Before scaling search

| Decision                     | Current state                                   | Needed by              |
| ---------------------------- | ----------------------------------------------- | ---------------------- |
| External geocoding provider  | `NullGeocoder`, manual coordinates              | Public search at scale |
| Radius search implementation | Bounding box + PHP distance, max 500 candidates | Catalog growth         |

## Post-MVP (Phase 9)

| Decision                                         | Current state                 | Needed by |
| ------------------------------------------------ | ----------------------------- | --------- |
| AI post provider, cost and retention             | Not started                   | Post-MVP  |
| WhatsApp Business API provider/consent/templates | Click-to-chat only            | Post-MVP  |
| Advanced CRM pipeline/tasks                      | Basic customer records only   | Post-MVP  |
| Per-member granular permissions                  | Owner/Admin/Member roles only | Post-MVP  |
| Second vertical                                  | Not chosen                    | Post-MVP  |

## Rule

Do not implement a paid or multi-vertical capability before the MVP has real
users and the decision above is recorded. Keep the terrace product stable first.

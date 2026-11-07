# Phase 7 — Branding, email and communications

**Status: PASS**
**Date:** 2026-10-31
**Branch / commit:** `phase-7-branding-communications` / phase-7 commit

## Scope

- Account branding settings: display name, logo upload, contact email, phone,
  WhatsApp, timezone and public contact details.
- Public listing pages show the account logo and fall back to account contact
  details when the listing has none.
- Platform branding singleton: app name, logo, support email, default sender and
  legal links, editable by superadmins and used in public shell and emails.
- Queued welcome email on registration.
- Queued payment-failure/grace email driven by Stripe `invoice.payment_failed`
  webhooks, idempotent per event id.
- Branding-aware From header on all transactional emails; owner inquiry emails
  use the visitor as Reply-To.
- Basic user notification preference to disable owner inquiry emails.
- Shared WhatsApp click-to-chat builder with strict phone validation, used by
  listing contact links and booking inquiry actions.

## Decisions

- Branding is edited on a dedicated account page linked from team settings,
  keeping the starter-kit team screen intact.
- Logos are stored on the public disk under `teams/{id}/branding` and
  `platform/branding`, limited to JPG/PNG/WebP and 2 MB. No arbitrary CSS/HTML or
  theme editor is exposed.
- Platform branding lives in a singleton `platform_settings` row managed by
  superadmins; values fall back to mail config when absent.
- Email delivery is mail-only in the MVP. No in-app notification center or
  database notification channel is added; the phase allowed it only where
  useful and there is no notification list UI yet.
- Notification preferences are stored per user as JSON and currently expose one
  toggle: owner emails for new inquiries (enabled by default).
- Inquiry owner notifications now target the owner user directly instead of an
  on-demand mail route, so preferences apply consistently.
- WhatsApp uses `https://wa.me/<digits>?text=<rawurlencoded>`; phone numbers must
  contain 10–15 digits after normalization (a leading `00` is removed). Numbers
  outside that range return `null` and no link is rendered. The UI states that
  the message is opened manually.
- Local development uses the `log` mailer by default; Mailpit can be used by
  pointing `MAIL_HOST`/`MAIL_PORT` at it. No real email is sent in tests.

## Packages and versions

| Package | Version | Why                                                                       |
| ------- | ------- | ------------------------------------------------------------------------- |
| —       | —       | No new packages; Laravel notifications/Markdown mail and existing storage |

## Database changes

Applied locally:

- `add_branding_to_teams_table` — `logo_path`, `contact_email`, `contact_phone`,
  `whatsapp_phone`.
- `add_notification_preferences_to_users_table` — JSON preferences.
- `create_platform_settings_table` — singleton branding row with legal links,
  sender, logo and updater.

## Testing

| Command                            | Result | Tests / assertions           |
| ---------------------------------- | ------ | ---------------------------- |
| `php artisan test`                 | PASS   | 197 tests / 1,041 assertions |
| Branding, email and WhatsApp tests | PASS   | 16 tests / 82 assertions     |
| `npm run build`                    | PASS   | 3,683 modules transformed    |
| `vendor/bin/pint --test`           | PASS   | —                            |
| `composer run types:check`         | PASS   | 0 PHPStan errors             |
| `npm run types:check`              | PASS   | 0 TypeScript errors          |
| Bootstrap/jQuery scan              | PASS   | no matches                   |

Coverage includes:

- owners update branding with a valid logo and account-scoped permissions;
- unsupported logo files are rejected;
- members and owners of other accounts cannot update branding;
- public listing exposes account logo, contact email/phone and WhatsApp URL
  fallback;
- registration sends a queued welcome notification;
- a failed Stripe invoice notifies only the affected owner and ignores duplicate
  event ids and unknown customers;
- disabling inquiry emails suppresses only the owner email while the visitor
  notification and inquiry record remain;
- the owner inquiry email contains listing, visitor contact and message data and
  sets Reply-To;
- WhatsApp URLs are encoded and malformed numbers are rejected;
- superadmins manage platform branding and normal users receive 403;
- invalid legal URLs are rejected;
- account suspension notification is sent only to the affected owner.

## Authorization and isolation checks

- Account branding uses `TeamPolicy::update`, so only owners/admins of the
  current account can change it; cross-account requests return 403.
- Uploaded logos are account-scoped by storage path and old files are replaced.
- Platform branding requires `platform.branding.manage`.
- Public pages only expose the branding of publicly visible listings.
- No secret, price id or internal storage path is sent to the frontend; only
  public URLs.

## No Bootstrap / jQuery check

Dependency and import scans returned no matches. The new screens use existing
shadcn-vue primitives and Tailwind utilities.

## Manual verification

- `php artisan migrate` applied the three new migrations locally.
- `php artisan route:list` shows `settings/teams/{team}/branding` and
  `platform/branding` protected routes.
- `storage:link` remains required locally for logos; the public disk is used.
- Mailer remains `log` locally; no real emails were sent. Queue worker is needed
  to deliver queued notifications outside tests.

## Residual risks

- Laravel's built-in verification and password-reset emails keep framework
  defaults; they are not wrapped in the branded queue pipeline.
- No in-app/database notification list yet; preferences only control email.
- The optional weekly lead summary was not built.
- Logo files are not cropped or resized; very large images within the 2 MB limit
  are served as uploaded.
- Queued mail requires a running worker in staging and production.
- Emails use the platform From address and the owner/visitor Reply-To; sending
  from an owner-owned domain is intentionally not supported.

## Next phase

Phase 8 — production launch: staging, CI/CD, backups, security and launch
checklist.

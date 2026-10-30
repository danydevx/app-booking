# Engineering standards

These rules apply to every phase.

## Stack and UI

- Laravel 13, Inertia 3, Vue 3 Composition API, TypeScript, Tailwind 4, shadcn-vue.
- Do not install Bootstrap, jQuery, AdminLTE or a Bootstrap-based component library.
- Do not install a second component library or a parallel design system.
- Use existing layout, spacing, colors and typography from the starter kit.
- Prefer server-rendered Inertia pages over a separate SPA or API-first rewrite.
- Use TypeScript for application code. Avoid `any` unless the boundary is external
  and documented.
- Use Wayfinder-generated routes if the Vue starter kit provides them. Do not
  hand-write duplicate route helpers.

## Backend

- Controllers validate through FormRequests and delegate to actions/services.
- Policies own tenant authorization. Do not rely on hidden UI controls.
- Use database transactions for availability/booking state transitions.
- Use `DB::transaction` plus unique constraints to prevent double booking.
- Use queued mail notifications for user-facing notifications.
- Use rate limiting on public forms and report endpoints.
- Use `Hash::make`/the model cast for passwords; never store plain text.
- Never trust uploaded MIME type alone: validate MIME, extension and size.
- Store uploaded media outside the public code path and serve it through the
  public disk or a controlled route. Never allow executable uploads.
- Use signed URLs where media should not be permanently public.
- Do not log secrets, full payment payloads, passwords or unnecessary personal data.
- Add indexes for tenant columns, foreign keys, status columns used in filters and
  date columns used in availability queries.

## Multi-account isolation

Every tenant-owned table must include the account/team foreign key. At minimum:

```text
listings
listing_media
listing_unavailability
booking_inquiries
customers
booking_deposits
account_settings
```

Rules:

- Resolve the current account from the authenticated user's selected team.
- Never accept the current account ID from a request field.
- Use route model binding together with policy checks.
- Public endpoints may only return published listings from active accounts.
- Platform queries are explicit, read-only where possible, and separately
  authorized. Do not add a global superadmin bypass to tenant scopes.
- Tests must prove user A cannot read or mutate user B's row even when guessing an
  ID.

## Data and money

- Use `bigIncrements`/`id` consistently unless a ULID is already established by
  the starter kit.
- Use `decimal(10,7)` for latitude and longitude.
- Use integer minor units for money, e.g. `amount_cents` plus `currency`.
- Use `timestampTz`/UTC or Laravel's standard timestamps and convert for display.
- Use enums or string-backed casts for statuses; do not scatter magic strings.
- Seed only reference data and plans. Never seed a production superadmin password.

## Testing

- Feature tests are mandatory for authorization, multi-account isolation, public
  listing visibility, booking conflicts and billing webhooks.
- Unit tests cover pricing/entitlement and date/availability rules.
- Use factories for domain models.
- Use a separate in-memory SQLite database for tests unless a phase requires
  database-specific behavior.
- Do not use real Stripe, mail, WhatsApp, AI or map credentials in tests. Fake the
  HTTP/client boundary.
- Every phase must keep the full suite green, not only the new tests.

## Git

- One branch per phase unless the user asks otherwise.
- Commit a phase only after tests and build pass.
- Stage explicit paths. Never `git add .`.
- Do not push, tag, merge or deploy unless explicitly requested.
- Preserve unrelated local changes. Never reset, clean or stash someone else's work.

## Documentation

After each phase:

- update `docs/development/PHASE-STATUS.md`;
- add `docs/development/phases/NN-name.md` using the report template;
- record package versions and commands actually executed;
- record any decision that changes the product or architecture.

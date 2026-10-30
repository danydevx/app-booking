# Phase 9 — Post-MVP and second vertical

**Status: NOT STARTED**

## Objective

Add paid differentiators and prove reuse only after the terrace MVP has real users.
Do not build these before launch.

## Candidate features

### AI social posts

- Generate draft posts for a listing using a configured AI provider.
- Keep generation asynchronous, rate-limited and cost-capped.
- Store generated drafts; require the owner to edit/approve.
- Do not auto-publish to social networks without explicit OAuth and approval.
- Never send private customer data to the AI provider.
- Provider, model, cost and retention must be an explicit decision.

### WhatsApp Business API

Only after deciding provider, consent, template approval and cost:

- opt-in records;
- message templates;
- webhook status handling;
- retry/idempotency;
- fallback to click-to-chat;
- no unsolicited marketing messages.

### Advanced CRM

- Notes, tags, follow-up tasks and reminders.
- Pipeline stages only if owners actually need them.
- Keep customer records account-scoped.
- Avoid becoming a generic CRM.

### Video and media

- Larger plan quotas.
- Optional transcoding/thumbnails with a queued job.
- Storage/egress cost controls.
- Do not block web requests on transcoding.

### Second vertical

Choose a real second vertical (e.g. balloon decorators or stands) and implement only:

- vertical labels and public copy;
- a small validated `details` schema;
- vertical-specific filters if required.

Do not fork the entire application. Reuse Accounts, billing, media, availability,
inquiries, customers and moderation.

## Extraction rule

Extract a module/package only when:

1. the second vertical genuinely uses it;
2. the boundary is stable;
3. tests exist;
4. extracting does not add a runtime installer or a second deployment model.

Until then, keep domain folders inside the application.

## Tests

- Each new paid capability is entitlement-gated server-side.
- AI/WhatsApp jobs are queued, retry-safe and rate-limited.
- Opt-out/consent rules are enforced.
- Second vertical cannot read the first vertical's data.
- No regression in terrace flows.

## Exit criteria

- A paid feature has measurable usage and support documentation.
- A second vertical works without duplicating the whole app.
- No plugin marketplace or runtime ZIP installer was introduced.
- Production remains stable and rollback tested.

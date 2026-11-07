# 0003 — Production operations and launch gates

**Date:** 2026-11-06
**Status:** Proposed — preparation only; no deployment is authorized

## Context

The MVP is functionally complete and CI is green locally, but there is no
staging or production environment yet. Phase 8 in the development plan is
partially complete: the checklist exists, but hosting, secrets, backups and
observability are still undecided.

This ADR records the operating requirements that must be true before launch. It
does **not** approve a deployment or choose a hosting provider.

## Decision

- Use three environments: local, staging/sandbox and production. Staging must
  never point at production data.
- Use MySQL 8.4 in staging/production, with secrets managed outside Git.
- Run queued notifications with a supervised worker
  (`php artisan queue:work --tries=3 --timeout=90`).
- Run the scheduler through cron (`php artisan schedule:run` every minute).
- Serve the app only from `public/` over HTTPS, with `APP_ENV=production`,
  `APP_DEBUG=false` and secure session cookies.
- Keep media on persistent storage (local persistent disk or object storage) and
  expose it via `storage:link` or a controlled route.
- Require a tested backup and restore procedure before launch. Backups must cover
  MySQL and media storage, be encrypted and access-controlled.
- Keep Stripe in test mode until the full flow (checkout, portal, webhook,
  failure, cancellation, reactivation and grace period) passes in staging.
- Deploy the same commit that passed CI/staging; use an atomic release and a
  documented rollback to the previous release.
- Run `composer production:check` in the target environment before deploying.
- Do not run `composer update` or destructive migrations in production.
- No production deployment without explicit approval and a named target
  environment.

## Consequences

- `docs/development/PRODUCTION.md` is the executable checklist for this ADR.
- The current `composer setup` already runs `storage:link` and migrations.
- Production readiness remains **blocked** until hosting, domain, database,
  storage, mail, backup and observability decisions are made.
- Queue, scheduler and backups become launch blockers, not post-launch tasks.
- Stripe live keys and webhook secrets stay only in production secrets.

## Open items

- Hosting/domain and target provider.
- Backup provider and retention policy.
- Mail provider with SPF/DKIM/DMARC.
- Storage choice (persistent disk vs object storage).
- Error tracking, uptime checks and log aggregation.
- Legal/terms/privacy/tax/refund policy.

These are tracked in `0004-open-decisions.md`.

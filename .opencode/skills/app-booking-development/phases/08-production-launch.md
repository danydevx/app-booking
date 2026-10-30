# Phase 8 — Staging, CI/CD and production launch

**Status: NOT STARTED**

## Objective

Prepare a reproducible staging and production path without turning this phase into
an infrastructure rewrite. No production deployment happens unless the user
explicitly authorizes it and provides the target environment.

## Environments

Use three clear environments:

| Environment | Purpose | Data |
|---|---|---|
| Local | Development | SQLite or local MySQL; fake mail/Stripe |
| Staging / Sandbox | Integration and release candidate | Isolated QA database and storage; Stripe test mode |
| Production | Real customers | Dedicated database, storage, mail and Stripe live mode |

- Never point staging at production data.
- Never copy production `.env`, uploads or database dumps into Git.
- Production secrets live in GitHub Environments/secrets or the hosting provider,
  never in the repository.
- Deploy the same commit that passed staging; do not rebuild an untested branch.

## Packages and tooling

Install only after approval:

```bash
composer require spatie/laravel-backup
npm install -D @playwright/test
```

Useful but not mandatory: Sentry/Laravel Nightwatch or the host's error tracking.
Do not add a monitoring vendor without the user's choice.

## CI (GitHub Actions)

A pull request workflow must run:

```bash
composer install --no-interaction --prefer-dist
cp .env.example .env
php artisan key:generate
php artisan test
npm ci
npm run build
vendor/bin/pint --test
```

Rules:

- Use a disposable SQLite/MySQL service for tests.
- Do not use production or staging secrets in pull request jobs.
- Cache Composer/npm safely.
- Fail on test/build/format errors.
- Keep deployment jobs separate from test jobs.

## Deployment (only with explicit approval)

The exact mechanism depends on the hosting provider. A typical SSH release flow:

1. Build/test a tagged commit in CI.
2. Create a new release directory on the server.
3. Deploy the same commit/artifact; do not edit files on the server.
4. Install Composer from `composer.lock` in production mode.
5. Build assets from `package-lock.json` or deploy the built artifact.
6. Link persistent `.env` and `storage`.
7. Put the app in maintenance mode if the release requires it.
8. Run migrations with `--force` as a controlled step.
9. Clear/rebuild config, route and view caches.
10. Restart queue workers.
11. Switch the `current` symlink atomically.
12. Run health checks and a browser smoke test.
13. Roll back to the previous release if checks fail.

Do not run `composer update` in production. Do not run destructive migrations.

## Server requirements

- PHP version supported by Laravel 13; required extensions.
- Database: MySQL 8+ compatible.
- Redis optional; start with database queue/cache only if volume is low.
- Queue worker managed by Supervisor/systemd.
- Scheduler via cron: `php artisan schedule:run`.
- Public document root points to `public/`.
- HTTPS/TLS, secure cookies, `APP_DEBUG=false`, `APP_ENV=production`.
- Writable `storage/` and `bootstrap/cache/` with least privilege.
- Object storage or persistent disk for listing media.
- Mail provider with SPF, DKIM and DMARC.
- Backups and restore test.
- Log rotation and error alerting.

## Security checklist

- All tenant routes have auth, verified and policy checks.
- Public forms are rate limited.
- Uploads validate MIME, extension, size; no executable files.
- No public listing of `.env`, storage, logs or backups.
- Stripe webhook signature verified; endpoint not CSRF-exempt incorrectly.
- Session cookies secure/httpOnly/sameSite appropriate.
- Superadmin cannot be registered publicly.
- Suspended accounts/listings are not publicly visible.
- Dependencies audited; known critical vulnerabilities addressed.
- No Bootstrap/jQuery.
- Database user has least privileges.
- Backups encrypted and access controlled.

## Stripe live checklist

Do not switch to live mode until:

- Stripe products/prices exist and match the plan catalog.
- Webhook endpoint is reachable and signature-verified in staging.
- Successful payment, failed payment, cancellation and reactivation tested.
- Grace-period behavior tested.
- Customer portal configured and return URLs correct.
- Refund/support process documented.
- Tax/legal treatment reviewed for the operating country.
- Live keys are only in production secrets.
- The app fails closed if Stripe is unavailable (does not grant paid access).

## Launch checklist

- Restore a backup into a disposable environment and verify it.
- Run `php artisan migrate --force` against a staging copy first.
- Run a production-like smoke test: register, verify email, create/publish listing,
  public search, inquiry, owner inbox, subscription checkout in test mode.
- Check error logs, queue health, scheduler, mail delivery and disk usage.
- Document rollback commit and database restore procedure.
- Confirm support/contact and legal pages.
- Announce only after acceptance.

## Tests and evidence

- CI workflow passes on the release commit.
- Staging smoke test passes.
- Backup restore test passes.
- No secrets in Git (`git grep` for keys; inspect staged diff).
- Production is not declared ready until all evidence is recorded.

## Exit criteria

- Staging is reproducible from Git.
- CI covers tests, build and formatting.
- Deployment/rollback procedure is documented and rehearsed.
- Security and Stripe checklists pass.
- The user explicitly approves production deployment.

## Do not

- Do not deploy to production automatically from this skill without authorization.
- Do not commit `.env` or credentials.
- Do not run migrations against production from a local machine.
- Do not use `git add .`.

# Development status

> Git reality: all phases were committed directly to `main` (trunk-based).
> Branch names in older phase reports are historical labels, not existing refs.

Last updated: 2026-11-07

| Phase                           | Status      | Evidence                                                                                                                    | Commit         |
| ------------------------------- | ----------- | --------------------------------------------------------------------------------------------------------------------------- | -------------- |
| 0 — Product decisions           | PASS        | `docs/decisions/0001-mvp.md`, phase report                                                                                  | docs commit    |
| 1 — Bootstrap                   | PASS        | Laravel 13.39.0; 91 tests/328 assertions; build 3,614 modules; Pint PASS                                                    | docs commit    |
| 2 — Accounts and permissions    | PASS        | Spatie 8.3.2; `/platform` protected; modules convention; 103 tests/372 assertions; build 3,616 modules                      | docs commit    |
| 3 — Listings, media and map     | PASS        | `Modules/Listings`; Leaflet 1.9.4; radius search; 124 tests/491 assertions; build 3,638 modules                             | phase-3 commit |
| 4 — Availability and bookings   | PASS        | `Modules/Bookings`; inquiries, customers, deposits, notifications; 146 tests/648 assertions; build 3,656 modules            | phase-4 commit |
| 5 — Stripe and plans            | PASS        | `Modules/Billing`; Cashier 16.9; test-mode checkout/portal/webhooks verified; 167 tests/764 assertions; build 3,658 modules | phase-5 commit |
| 6 — Platform admin              | PASS        | `Modules/Platform`; activitylog 4.12; moderation, reports, audit; 181 tests/959 assertions; build 3,678 modules             | phase-6 commit |
| 7 — Branding and communications | PASS        | `Modules/Platform` branding + account branding, queued mail, WhatsApp; 197 tests/1,041 assertions; build 3,683 modules      | phase-7 commit |
| 8 — Production launch           | PARTIAL     | `docs/development/PRODUCTION.md` + `app:production-check`; deployment pending target/approval                               | —              |
| 9 — Post-MVP                    | NOT STARTED | —                                                                                                                           | —              |

## Current blockers

- None. Database migration to MySQL phase 2 applied; SQLite remains
  legacy/test-only.
- CI verified locally: SQLite `composer ci:check` and MySQL 8.4 backend suite pass.
- Production launch pending target environment and explicit approval; checklist in
  `docs/development/PRODUCTION.md` and `composer production:check`.

## Environment facts

- Laravel version: 13.39.0
- PHP version: 8.3.6
- Node version: 24 (`.nvmrc` and CI)
- Test framework: PHPUnit
- Test suite: 263 tests / 1,410 assertions (SQLite and MySQL 8.4)
- Architecture decisions: `docs/decisions/README.md` (ADR 0001–0004)
- Local database: MySQL 8.4 (Docker, `127.0.0.1:3307`); SQLite legacy at `database/legacy/database.sqlite`
- CI databases: SQLite (fast suite) + MySQL 8.4 (compatibility job)
- Database migration: phase 2 applied (schema + data); SQLite remains legacy/test-only
- Frontend: Vue 3 + Inertia 3 + Tailwind 4 + shadcn-vue
- Bootstrap/jQuery: not installed
- Business modules: `Modules/{Name}/` with module-owned backend, routes, migrations,
  Vue pages/components, Blade views and tests; see `docs/development/MODULES.md`
- Current business modules: `Modules/Platform`, `Modules/Listings`,
  `Modules/Bookings`, `Modules/Billing`
- Moderation audit: `spatie/laravel-activitylog` 4.12 with `moderation` log
- Operational status: `/platform/system` shows queue, scheduler heartbeat and failed jobs
- Local mailer: `log`; Mailpit optional via `MAIL_HOST`/`MAIL_PORT`

# Phase 1 — Bootstrap Laravel 13 + Vue Starter Kit

**Status: PASS**
**Date:** 2026-10-30
**Branch / commit:** `main` — scaffold commits plus the phase documentation commit

## Scope

Created the application from the official Laravel 13 Vue Starter Kit and prepared
local development. No domain models, Stripe, Spatie, maps or custom modules were
added.

## Installed stack

| Component            | Version / result |
| -------------------- | ---------------- |
| Laravel Framework    | 13.39.0          |
| PHP                  | 8.3.6            |
| Starter Kit          | Official Vue     |
| Inertia              | 3.x              |
| Vue                  | 3.5.x            |
| Tailwind CSS         | 4.x              |
| shadcn-vue / Reka UI | present          |
| Authentication       | Fortify          |
| Testing              | PHPUnit          |
| Database             | SQLite           |
| Frontend build       | Vite / vite-plus |
| Laravel Boost        | installed (dev)  |

Teams support is enabled. A personal team is created on registration and routes are
team-scoped through the Starter Kit.

## Configuration

- `APP_NAME="App Booking"`.
- `APP_LOCALE=es`, fallback `en`, Faker `es_MX`.
- Application timezone is UTC; account-level timezone will be handled by product
  settings later.
- Local database migrated with the Starter Kit schema.
- Mail uses the local `log` driver.
- `.env.example` contains no real secrets.
- Email verification is enabled (`User implements MustVerifyEmail`).
- Registration, password reset, 2FA and passkeys are enabled by the Starter Kit.

## Verification

| Command                                                   | Result                           |
| --------------------------------------------------------- | -------------------------------- |
| `php artisan --version`                                   | Laravel 13.39.0                  |
| `php artisan test`                                        | PASS — 91 tests / 328 assertions |
| `npm run build`                                           | PASS — 3,614 modules transformed |
| `vendor/bin/pint --test`                                  | PASS                             |
| `npm ls bootstrap jquery bootstrap-vue admin-lte`         | No Bootstrap/jQuery/AdminLTE     |
| `grep` for Bootstrap/jQuery application imports           | No matches                       |
| Local HTTP `/`, `/login`, `/register`, `/forgot-password` | HTTP 200                         |

The root `/dashboard` path is not a standalone route in this Starter Kit; dashboards
are account/team scoped (`/{current_team}/dashboard`). This is expected.

## No Bootstrap / jQuery

Verified through npm and source scanning. The Laravel `bootstrap/` directory is the
framework bootstrap directory and is not the Bootstrap CSS/JS library.

## Residual risks

- Team/account UI labels still use the Starter Kit's "Team" wording. Renaming to
  "Cuenta" belongs to phase 2.
- Local HTTP checks verify rendering and assets; interactive auth behavior is
  covered by the 91 passing tests.
- No staging or production environment exists yet.
- Laravel Boost generated `.agents/`, `AGENTS.md`, `boost.json` and `opencode.json`;
  these are development/agent configuration, not product features.

## Next phase

Phase 2 — use Teams as Cuentas and add protected Spatie platform permissions.

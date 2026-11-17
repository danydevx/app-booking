# Development status

> Git reality: all phases were committed directly to `main` (trunk-based).
> Branch names in older phase reports are historical labels, not existing refs.

Last updated: 2026-11-17

| Phase                           | Status      | Evidence                                                                                                                                                                                                                                                                                                                                                                                                                                                      | Commit         |
| ------------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------- |
| 0 — Product decisions           | PASS        | `docs/decisions/0001-mvp.md`, phase report                                                                                                                                                                                                                                                                                                                                                                                                                    | docs commit    |
| 1 — Bootstrap                   | PASS        | Laravel 13.39.0; 91 tests/328 assertions; build 3,614 modules; Pint PASS                                                                                                                                                                                                                                                                                                                                                                                      | docs commit    |
| 2 — Accounts and permissions    | PASS        | Spatie 8.3.2; `/app/platform` protected; modules convention; 103 tests/372 assertions; build 3,616 modules                                                                                                                                                                                                                                                                                                                                                    | docs commit    |
| 3 — Listings, media and map     | PASS        | `Modules/Listings`; Leaflet 1.9.4; radius search; 124 tests/491 assertions; build 3,638 modules                                                                                                                                                                                                                                                                                                                                                               | phase-3 commit |
| 4 — Availability and bookings   | PASS        | `Modules/Bookings`; inquiries, customers, deposits, notifications; 146 tests/648 assertions; build 3,656 modules                                                                                                                                                                                                                                                                                                                                              | phase-4 commit |
| 5 — Stripe and plans            | PASS        | `Modules/Billing`; Cashier 16.9; test-mode checkout/portal/webhooks verified; 167 tests/764 assertions; build 3,658 modules                                                                                                                                                                                                                                                                                                                                   | phase-5 commit |
| 6 — Platform admin              | PASS        | `Modules/Platform`; activitylog 4.12; moderation, reports, audit; 181 tests/959 assertions; build 3,678 modules                                                                                                                                                                                                                                                                                                                                               | phase-6 commit |
| 7 — Branding and communications | PASS        | `Modules/Platform` branding + account branding, queued mail, WhatsApp; 197 tests/1,041 assertions; build 3,683 modules                                                                                                                                                                                                                                                                                                                                        | phase-7 commit |
| 8 — Production launch           | PARTIAL     | `docs/development/PRODUCTION.md` + `app:production-check`; deployment pending target/approval                                                                                                                                                                                                                                                                                                                                                                 | —              |
| 9 — Post-MVP                    | IN PROGRESS | CRM Pasos 1–14 (timeline, tareas, Hoy, cotizador, plantillas/correo, atribución, reportes, notificaciones, portal, contratos y ficha por secciones) + cotizaciones PDF, logo de terrazas, búsqueda guiada, cobertura geográfica, contacto público y reclamación de terrazas del Directorio (ADR 0006). Plan CRM: `docs/development/CRM-PLAN.md`; cotizaciones: `docs/decisions/0005-commercial-quotations.md`; reclamos: `docs/development/LISTING-CLAIMS.md` | —              |

## Current blockers

- None. Database migration to MySQL phase 2 applied; SQLite remains
  legacy/test-only.
- `npm audit --omit=dev` PASS: 0 vulnerabilidades de producción. El árbol
  dev/build conserva 17 alertas altas de `picomatch` sin parche upstream; ver el
  addendum 2026-11-17 en
  `docs/BOOKING-RC1-DEPENDENCIES-SECURITY-FIX-REPORT.md`.
- CI verified locally: SQLite `composer ci:check` and MySQL 8.4 backend suite pass.
- RC1 consolidado localmente y **READY FOR STAGING**; no hay tag, release ni despliegue. Ver
  `docs/BOOKING-MVP-RC1-AUDIT.md`, `docs/BOOKING-MVP-RC1-RELEASE-CHECKLIST.md` y
  `docs/BOOKING-MVP-RC1-REPORT.md`.
- Production launch pending target environment and explicit approval; checklist in
  `docs/development/PRODUCTION.md` and `composer production:check`.

## Environment facts

- Laravel version: 13.39.0
- PHP version: 8.3.6
- Node version: 24 (`.nvmrc` and CI)
- Test framework: PHPUnit
- Test suite: 519 tests / 4,615 assertions (SQLite; MySQL 8.4 compatibility job)
- Architecture decisions: `docs/decisions/README.md` (ADR 0001–0006)
- Local database: MySQL 8.4 (Docker, `127.0.0.1:3307`); SQLite legacy at `database/legacy/database.sqlite`
- CI databases: SQLite (fast suite) + MySQL 8.4 (compatibility job)
- Database migration: phase 2 applied (schema + data); SQLite remains legacy/test-only
- Frontend: Vue 3 + Inertia 3 + Tailwind 4 + shadcn-vue
- Public frontend identity: landing uses `resources/css/home.css` (`.home-site`,
  pink `#F50557` + dark CTAs, Plus Jakarta Sans) via `HomeLayout`; catalog/detail keep
  `resources/css/public.css` (`.public-site`). Neither reaches the admin, which
  keeps its shadcn-vue tokens, fonts and layout untouched
- Bootstrap/jQuery: not installed
- Business modules: `Modules/{Name}/` with module-owned backend, routes, migrations,
  Vue pages/components, Blade views and tests; see `docs/development/MODULES.md`
- Cotizaciones: PDF privado con Dompdf, folios por cuenta, IVA/descuentos en
  centavos y snapshots históricos; la emisión es inmutable y la descarga exige
  entitlement Pro y pertenencia al equipo.
- Current business modules: `Modules/Platform`, `Modules/Listings`,
  `Modules/Bookings`, `Modules/Billing`
- Moderation audit: `spatie/laravel-activitylog` 4.12 with `moderation` log
- Operational status: `/app/platform/system` shows queue, scheduler heartbeat and failed jobs
- CRM: timeline, notes, tasks, tags, metrics, pipeline, lifecycle, daily board, assignment, quotes, email templates, attribution, reports, in-app notifications, client portal, contracts and sectioned customer workspace are available (Pasos 1–14)
- Availability: interactive calendar, manual blocks and recurring series (weekly/biweekly/monthly)
- Admin prefix: public site remains at `/` and `/terrazas`; account administration lives under `/app/{team}` and platform under `/app/platform`
- Public design guidelines: `docs/design/Guidelines.md` (home, catalog, detail, owners landing)
- Owners landing: `/propietarios` presents Free vs Pro with real plan limits/prices and the public registration/billing flows
- Public geo search: `/terrazas` supports city/state/postal-code text search, map viewport search, card↔marker sync and clustered markers; see `docs/development/GEO-SEARCH.md`
- Listing features: config-driven catalog with 8 categories and 50 MVP amenities, grouped in admin and public detail
- Directory listings and claims: `teams.is_directory`, `listings.claimed_at`, `listing_claims`, `app:directory-team`, `listings:import`, `listings:make-claimable`, public `/terrazas/{slug}/reclamar` and platform queue `/app/platform/claims`; see `docs/development/LISTING-CLAIMS.md`
- Commercial MVP: optional base prices, seasonal price ranges, packages, commercial services and time-bound promotions per listing; public pages only show active/current records
- Full-flow smoke test covers landing → solicitud → CRM → cotización → contrato → portal → reserva → opinión → reportes
- Terrace admin: `/app/{team}/listings/{listing}` hub with scoped reservations, new booking, customers, quotes, availability and media links
- Local mailer: `log`; Mailpit optional via `MAIL_HOST`/`MAIL_PORT`

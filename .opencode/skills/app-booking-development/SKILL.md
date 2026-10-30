---
name: App Booking Development
description: Implement the app-booking Laravel 13 + Vue SaaS phase by phase, with Vue starter-kit conventions, no Bootstrap/jQuery, teams as accounts, Spatie permissions, listings, availability, leads, Stripe subscriptions and platform moderation.
---

# App Booking Development

Use this skill for any implementation work on the terrace-rental SaaS at
`/mnt/desarrollo/server/laravel/apps/app-booking`.

## Product in one paragraph

A multi-account SaaS where terrace owners publish listings, manage availability,
leads, customers and deposits. Visitors search published terraces by location and
radius, view a listing, and submit a rental-interest request. Owners pay a monthly
Stripe subscription to unlock higher limits and paid capabilities. A platform
superadmin reviews accounts, subscriptions and published content and can suspend
accounts or listings for fraud or terms violations. The same product should later
be adaptable to other verticals (balloon decorators, stands) without duplicating
the whole application.

## Non-negotiable stack

- Laravel 13.
- Official Laravel Vue Starter Kit: Inertia 3, Vue 3 Composition API,
  TypeScript, Tailwind CSS 4, shadcn-vue.
- Laravel Fortify authentication from the starter kit.
- PHPUnit for backend tests unless the generated Starter Kit cannot support it;
  if that happens, stop and report before switching test frameworks.
- Vite for frontend assets.
- SQLite locally for the first phases; MySQL-compatible schema for staging and
  production.
- Stripe through Laravel Cashier. No PayPal in the MVP.
- Spatie Laravel Permission for platform-level access control.
- Leaflet + OpenStreetMap for maps. No Google Maps requirement in the MVP.
- Mail notifications through Laravel's mail system. WhatsApp V1 is a
  click-to-chat `wa.me` link; automated WhatsApp API is post-MVP.

## Hard UI and dependency rules

- **Never install or introduce Bootstrap**, Bootstrap CSS, Bootstrap JS,
  BootstrapVue, AdminLTE, or any Bootstrap-based theme.
- **Never install or introduce jQuery.**
- Do not add another component library that duplicates shadcn-vue.
- Use Tailwind utility classes and existing shadcn-vue components. Add a shadcn-vue
  component with `npx shadcn-vue@latest add <component>` when needed.
- Do not add custom CSS frameworks, icon packs, date-picker libraries, or table
  libraries without a concrete blocked requirement and approval.
- Use the existing starter-kit layouts and design tokens. Do not redesign the
  application shell.
- The `bootstrap/` directory in Laravel is framework bootstrapping, not Bootstrap
  CSS. Never confuse the two when checking dependencies.

Verify after every frontend dependency change:

```bash
npm ls bootstrap jquery bootstrap-vue admin-lte 2>/dev/null || true
grep -R "bootstrap.min\|bootstrap.css\|from ['\"]jquery\|require(['\"]jquery" resources package.json 2>/dev/null || true
```

Both commands must show no application dependency or import.

## Architecture rules

- Start from a fresh Laravel 13 Vue Starter Kit application. Do not copy the old
  `base-monorepo` architecture into this project.
- Do not import AIDG modules, AIDG repositories or AIDG domain code.
- Do not create a plugin marketplace, runtime ZIP installer, Composer private
  registry, or a second module registry.
- Use the Starter Kit's Teams feature as the account/business boundary. A Team is
  the **Cuenta** in product language. Do not create a second `accounts` table.
- Organize business capabilities as explicit modules under `Modules/{Name}/`, not
  as scattered files under `app/`. Each module owns its backend, routes,
  migrations, Vue pages/components, Blade views and tests. The canonical layout is
  in `docs/development/MODULES.md` and
  `references/module-structure.md`.
- Do not build a runtime module loader or plugin marketplace. Register each module
  provider explicitly in `bootstrap/providers.php`.
- Keep shared Starter Kit code in `app/` and `resources/js/`; module code must not
  duplicate shared UI components.
- Keep HTTP controllers thin. Put business operations in actions/services and
  authorization in policies.
- Every tenant-owned query must be scoped to the current team/account. Never
  accept `team_id` or `account_id` from request input for authorization.
- A published public listing must still be validated server-side for account
  status and publication status.
- Store money as integer minor units plus an ISO currency code. Never use floats
  for money.
- Store timestamps in UTC. Store/display the account timezone explicitly.
- Do not edit migrations that have already run outside local development. Add a
  new migration instead.
- No secrets, `.env`, vendor, node_modules, storage, logs or build output in Git.

## Phase index

Execute one phase at a time, in order. Read `phases/NN-*.md` before touching the
project. Do not start a later phase until the current phase's exit criteria pass.

| Phase | File                                   | Main outcome                                                      |
| ----- | -------------------------------------- | ----------------------------------------------------------------- |
| 0     | `phases/00-product-decisions.md`       | Lock MVP decisions and recommended defaults                       |
| 1     | `phases/01-bootstrap.md`               | Laravel 13 + Vue starter kit, no Bootstrap/jQuery, Git, local app |
| 2     | `phases/02-accounts-permissions.md`    | Teams as Accounts, Spatie roles, platform superadmin              |
| 3     | `phases/03-listings-media-map.md`      | Listings, media, public catalog, map and radius search            |
| 4     | `phases/04-availability-bookings.md`   | Availability, inquiries, customers, deposits, email/WhatsApp      |
| 5     | `phases/05-stripe-plans.md`            | Stripe subscriptions, plans, entitlements, webhooks               |
| 6     | `phases/06-platform-admin.md`          | Superadmin operations, moderation, suspensions and audit          |
| 7     | `phases/07-branding-communications.md` | Account branding, branded email, notifications                    |
| 8     | `phases/08-production-launch.md`       | Staging, CI/CD, backups, security, launch checklist               |
| 9     | `phases/09-post-mvp.md`                | AI posts, WhatsApp API, advanced CRM, second vertical             |

## Execution protocol

For each phase:

1. Read this skill, the target phase file, and `references/engineering-standards.md`.
2. Inspect the actual current project and dependency versions before changing
   anything. Never assume a starter-kit file path or package version.
3. State a short scope: files/areas to change, packages to install, tests to add.
4. Implement only that phase's scope.
5. Run the phase's verification commands and the existing test suite/build.
6. Update `docs/development/PHASE-STATUS.md` with the phase status, commands,
   results, versions and date.
7. Write a phase report in `docs/development/phases/` using the template in
   `references/phase-report-template.md`.
8. Commit only reviewed files for that phase. Never `git add .`.
9. Stop and ask before proceeding to the next phase if the user has requested
   phase-by-phase approval.

Do not claim a phase is complete if commands were not run or a requirement failed.
Use `PASS`, `PARTIAL`, `BLOCKED` or `FAIL` explicitly.

## Global definition of done

A phase is done only when all are true:

- The scope in the phase file is implemented.
- Authorization is enforced server-side and covered by tests.
- Tenant data isolation is covered by tests where relevant.
- No Bootstrap, jQuery or duplicate UI framework was introduced.
- `php artisan test` passes locally.
- `npm run build` passes.
- `vendor/bin/pint --test` passes or formatting is fixed.
- No secrets or generated artifacts are staged.
- The phase report and status are updated.
- The Git commit contains only the reviewed phase files.

If a requirement conflicts with the Starter Kit, stop and report the conflict
instead of silently building a parallel subsystem.

## Default commands

```bash
# Backend tests
php artisan test

# Frontend build
npm run build

# Formatting / lint
vendor/bin/pint --test
npm run lint        # only if the starter kit provides it

# Framework and dependency facts
php artisan --version
composer show
npm ls --depth=0
```

## Escalation points

Ask the user before:

- installing a package not listed in the phase;
- enabling live Stripe charges;
- changing the account/tenant model;
- adding a second vertical or generic field builder;
- introducing an external WhatsApp API, AI provider or paid map service;
- deleting/importing production data;
- deploying to a real server;
- rewriting or copying the old monorepo/AIDG architecture.

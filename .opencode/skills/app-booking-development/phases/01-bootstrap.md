# Phase 1 — Bootstrap Laravel 13 + Vue Starter Kit

**Status: NOT STARTED**

## Objective

Create the real application at the repository root from the official Laravel 13
Vue Starter Kit. Establish Git, local configuration, authentication, email
verification, local database, tests and a working build. No domain tables yet.

## Preconditions

- `app-booking` exists and is empty, or contains only user-owned notes that must be
  preserved. If it is not empty, stop and ask before moving or replacing anything.
- PHP, Composer, Node, npm and the Laravel installer are available.
- Phase 0 decisions are recorded.

## Install

The Laravel installer must create the application from the parent directory. Do
not run it inside a non-empty `app-booking`.

```bash
cd /path/to/parent-directory

# Only if app-booking is proven empty:
rmdir app-booking

laravel new app-booking \
  --vue \
  --teams \
  --phpunit \
  --database=sqlite \
  --npm \
  --boost \
  --git
```

If the installer cannot run non-interactively in this environment, run it
interactively and record the exact selected options. Do not silently fall back to
a different frontend or testing framework.

After installation:

```bash
cd app-booking
php artisan --version
composer show laravel/framework inertiajs/inertia-laravel laravel/fortify
npm ls --depth=0
```

The framework must be Laravel 13.x. The frontend must be the Vue Starter Kit
(Inertia 3 + Vue 3 + TypeScript + Tailwind + shadcn-vue).

## Configure

- `APP_NAME` to the product name; use a neutral working name until branding is
  decided.
- `APP_LOCALE=es`, sensible fallback, UTC application timezone.
- Local SQLite database; do not point local development at production.
- Enable email verification if the starter kit did not already enable it:
    - `App\Models\User` implements `MustVerifyEmail`.
    - protected application routes use `verified` as appropriate.
- Confirm Fortify registration, login, password reset, email verification and 2FA
  features are enabled unless product decisions say otherwise.
- Configure the local mail transport for development. Use log or Mailpit; do not
  use a real mailbox.
- Add a `.env.example` entry for every non-secret environment variable introduced.
- Keep the existing Vue starter-kit layout and component library.

## Repository

```bash
git status
git branch --show-current
```

Use `main` as the initial branch and create `develop` for integration if there is
no existing team convention. Do not add a remote, push, tag or deploy.

## Required checks

```bash
php artisan test
npm run build
vendor/bin/pint --test
npm ls bootstrap jquery bootstrap-vue admin-lte 2>/dev/null || true
grep -R "bootstrap.min\|bootstrap.css\|from ['\"]jquery\|require(['\"]jquery" resources package.json 2>/dev/null || true
```

Expected:

- PHPUnit passes.
- Vite production build passes.
- No application dependency/import for Bootstrap or jQuery.
- `git status` shows no `.env`, vendor, node_modules, storage logs or build output.

Run the application locally and verify registration, login, logout, dashboard and
password-reset screens render without console errors.

## Deliverables

- Fresh Laravel 13 + Vue application.
- `docs/development/PHASE-STATUS.md` initialized from the template.
- `docs/development/phases/01-bootstrap.md` phase report.
- Initial commit in `main` (and `develop` if created).

## Exit criteria

- Laravel 13 and the Vue Starter Kit are verified by command output.
- Authentication screens work locally.
- No Bootstrap or jQuery.
- Tests and build pass.
- No secrets or generated directories are committed.
- No domain models, Stripe, maps or custom modules added yet.

## Do not

- Do not install Bootstrap, jQuery or AdminLTE.
- Do not copy old `base-monorepo` or AIDG code.
- Do not create a second accounts system before inspecting the Starter Kit teams.
- Do not add Stripe, Spatie or maps in this phase.
- Do not deploy.

# Phase 3 — Listings, media, public catalog and map

**Status: PASS**
**Date:** 2026-10-30
**Branch:** `phase-3-listings`

## Scope

Built the first customer-visible product slice inside `Modules/Listings/`:

- owner listing CRUD with account-scoped access;
- listing statuses, publish/unpublish rules and stable slugs;
- image/video upload, ordering and deletion;
- public catalog and public listing detail;
- Leaflet + OpenStreetMap map;
- server-side radius search with a bounding-box prefilter and PHP distance
  refinement;
- module-owned Vue pages, components, types, routes, migrations and tests.

## Module layout

The feature follows the project module convention:

```text
Modules/Listings/
├── src/
│   ├── Actions/
│   ├── Contracts/
│   ├── Database/Factories/
│   ├── Enums/
│   ├── Http/Controllers/
│   ├── Http/Requests/
│   ├── Models/
│   ├── Policies/
│   ├── Queries/
│   ├── Support/
│   └── ListingsServiceProvider.php
├── routes/web.php
├── database/migrations/
├── resources/js/
│   ├── components/
│   ├── pages/
│   └── types.ts
├── resources/views/
└── tests/Feature/
```

Inertia pages resolve as `Listings/Index`, `Listings/Create`, `Listings/Edit`,
`Listings/Public/Index` and `Listings/Public/Show`.

## Decisions

- Teams remain the account boundary. Listing queries are always scoped by
  `team_id`; `team_id` is not mass assignable.
- Any account member can create or edit drafts. Only owner/admin can publish,
  unpublish or delete.
- Slugs are generated on creation and remain stable when a listing is renamed, so
  public URLs do not break.
- Publish requires at least one image, address, coordinates and one contact method.
- Media limits are configurable at 20 images and 3 videos. Plan enforcement is
  deliberately deferred to phase 5.
- The first image is the cover. Images and videos use Laravel's public disk.
- Radius search uses a bounded candidate set (default max 500) and refines with
  Haversine distance in PHP for SQLite/MySQL portability.
- Coordinates are entered manually. `Geocoder` is a seam with a `NullGeocoder`
  implementation; no external geocoding call is made in the MVP.
- Public pages use a lightweight module-owned public shell instead of the
  authenticated application sidebar.

## Packages and versions

| Package          | Version | Why                |
| ---------------- | ------- | ------------------ |
| `leaflet`        | 1.9.4   | OpenStreetMap map  |
| `@types/leaflet` | 1.9.22  | TypeScript support |

## Database changes

Applied locally:

- `2026_10_30_230001_add_timezone_to_teams_table.php` — account timezone,
  default `America/Mexico_City`.
- `2026_10_30_230002_create_listings_table.php` — listing fields, tenant key,
  status, coordinates, services, contact data and indexes.
- `2026_10_30_230003_create_listing_media_table.php` — image/video metadata,
  ordering and tenant link through `listing_id`.

## Testing

| Command                       | Result | Tests / assertions         |
| ----------------------------- | ------ | -------------------------- |
| `php artisan test`            | PASS   | 124 tests / 491 assertions |
| Phase 3 focused tests         | PASS   | 20 tests / 117 assertions  |
| `npm run build`               | PASS   | 3,638 modules transformed  |
| `vendor/bin/pint --test`      | PASS   | —                          |
| `composer types:check`        | PASS   | 0 PHPStan errors           |
| `npm run types:check`         | PASS   | —                          |
| Vite check on module frontend | PASS   | 15 files, no warnings      |

Coverage includes:

- owner create/update/publish/delete;
- publish requirement failures;
- deterministic duplicate slugs;
- member create allowed but publish forbidden;
- cross-account edit attempts returning 404;
- owner index scoped to the current team;
- eager-loaded cover images and media counts;
- image upload, MIME/executable rejection, size rejection and video count limit;
- media reorder and file deletion;
- public catalog hiding draft/suspended listings;
- public detail 404 for non-published listings;
- city/capacity/service filters;
- radius search returning nearby listings and excluding far listings;
- latitude/longitude pairing validation.

## Authorization and isolation checks

- Owner routes require `auth`, `verified` and `EnsureTeamMembership`.
- Every owner listing operation resolves the current team and rejects a listing
  from another team with 404.
- A member can create/edit but receives 403 on publish.
- Public queries use the `published` scope and never return drafts or suspended
  listings.
- `team_id` is not fillable and is assigned server-side from the current team.

## No Bootstrap / jQuery check

Commands returned no application dependency or import. `npm ls bootstrap jquery
bootstrap-vue admin-lte` shows only the empty tree.

## Manual verification

- `php artisan storage:link` created the local public media link.
- Route list exposes two public routes and eleven account routes.
- Public and owner pages are rendered by feature tests through Inertia.

## Residual risks

- No account suspension/active-account check yet; phase 6 adds platform
  moderation.
- Plan entitlement is not enforced yet; phase 5 will gate free/paid media limits.
- No external geocoding provider, image resizing or video duration probing.
- Availability summaries and booking inquiries are intentionally deferred to
  phase 4.
- Radius search loads at most 500 bounding-box candidates before refining in PHP;
  this is appropriate for the MVP, not a high-volume search backend.

## Next phase

Phase 4 — availability, booking inquiries, customers, deposits and email/WhatsApp
communication.

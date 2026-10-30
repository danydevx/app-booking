# Phase 3 — Listings, media, public catalog and map

**Status: NOT STARTED**

## Module placement

Implement the business capability as `Modules/Listings/`:

- backend in `Modules/Listings/src/`;
- owner and public routes in `Modules/Listings/routes/web.php`;
- migrations in `Modules/Listings/database/migrations/`;
- Inertia pages in `Modules/Listings/resources/js/pages/`, addressed as
  `Listings/Index`, `Listings/Public/Index`, `Listings/Public/Show`;
- module-only components in `Modules/Listings/resources/js/components/`;
- Blade views, if needed, in `Modules/Listings/resources/views/`;
- tests in `Modules/Listings/tests/Feature/`;
- provider registered explicitly in `bootstrap/providers.php`.

Follow `docs/development/MODULES.md`.

## Objective

Let an owner create, edit, publish and unpublish a terrace listing, upload images
and videos, and let visitors browse published listings and search by location and
radius. This phase is the first customer-visible product slice.

## Data model

Create migrations and models for:

### `listings`

Required fields:

- `team_id` foreign key/index (account boundary);
- `type` string, default `terrace`;
- `name`, `slug` unique;
- `description` text;
- `address`, `city`, `state`, `country`, `postal_code`;
- `latitude`, `longitude` using `decimal(10,7)` and indexed;
- `capacity` unsigned integer nullable;
- `services` JSON array of validated service keys;
- `details` JSON nullable for small vertical-specific fields;
- `rules` text nullable;
- `contact_name`, `contact_email`, `contact_phone`, `whatsapp_phone`;
- `timezone` string, default from the account;
- `status` enum/string: `draft`, `published`, `suspended`;
- `published_at` nullable;
- timestamps.

Do not create a generic field-builder or arbitrary schema editor. `details` is a
small, validated seam for future verticals, not an escape hatch for unvalidated
user input.

### `listing_media`

- `listing_id` foreign key/index;
- `type` (`image`/`video`);
- `disk`, `path`, `mime_type`, `size_bytes`, `width`, `height`, `duration_seconds`;
- `alt_text`, `sort_order`;
- timestamps.

Use native Laravel filesystem/storage. Do not add a media library unless a concrete
conversion requirement cannot be met with native storage.

## Backend

- Listing policy: owner/member with the right capability can create/edit; only
  owner or authorized member can publish/delete; platform moderation is separate.
- Always scope listing queries to the current account on owner routes.
- Public catalog only returns `published` listings from active accounts.
- Prevent changing `team_id` through mass assignment or request payload.
- Generate slugs from the name but preserve uniqueness. Avoid an extra slug package
  unless needed.
- Validate services against `config/verticals.php` (or the equivalent config).
- Validate media MIME, extension, size and count. Reject disguised executables.
- Store images/videos on the configured disk; use a controlled public URL or signed
  URL. Never serve uploaded PHP files.
- Add upload limits aligned with the account plan, but keep plan enforcement in
  phase 5.

## Public experience

- Public listing index with pagination and basic filters.
- Public listing detail with image gallery, services, rules, map location and
  availability summary (availability fully implemented in phase 4).
- Public search page:
    - Leaflet + OpenStreetMap, no Google Maps dependency;
    - location/radius controls;
    - server-side radius filtering;
    - results list synchronized with the map.

### Map and geocoding

Install only the frontend map dependency:

```bash
npm install leaflet
npm install -D @types/leaflet
```

Use Leaflet's vanilla JS API from Vue. Do not add jQuery or Bootstrap for the map.

- Default map tiles: OpenStreetMap with required attribution.
- Use a small `Geocoder` interface/config so the provider can change later.
- For the MVP, allow manual latitude/longitude entry plus optional geocoding.
- If Nominatim is used, cache results, set a user agent, respect usage limits and do
  not call it in tests.
- Radius search: apply a bounding-box SQL filter first, then refine distance in PHP
  for database portability. Index latitude/longitude. Do not fetch the entire table.
- Show distance in the result but never expose exact private coordinates beyond the
  public listing location policy.

## Owner experience

- Listing list with status and plan usage.
- Create/edit form using existing shadcn-vue controls.
- Media upload/reorder/delete.
- Publish/unpublish with validation: at least one image, address, coordinates and
  contact method.
- Preview public page.

## Tests

- Owner can create/edit/publish own listing.
- User A cannot view/edit/publish User B's listing, including by guessing IDs.
- Public catalog hides draft and suspended listings.
- Radius search returns expected results and excludes outside listings.
- Upload rejects wrong MIME, oversized files and executable files.
- Duplicate slug is handled deterministically.
- No N+1 explosion on listing index.
- No Bootstrap/jQuery introduced.

## Commands

```bash
php artisan test
npm run build
vendor/bin/pint --test
```

## Exit criteria

- An owner can publish a complete listing.
- A visitor can find it on the public catalog and map.
- Radius search works with test fixtures.
- Tenant isolation and media validation are tested.
- No custom design system or new CSS framework.
- Phase report and status updated.

## Do not

- Do not implement bookings, payments or subscriptions yet.
- Do not add AI-generated descriptions or social posts.
- Do not add a generic custom-field builder.
- Do not use Google Maps as a hard requirement.

# Public frontend — technical notes

The public design system is documented in
[`docs/design/Guidelines.md`](../design/Guidelines.md). That file is the single
source of truth for colors, typography, layout, components and accessibility.
Do not duplicate those values here.

## Entry points and layouts

- `resources/css/home.css` — public design tokens and component classes, scoped
  under `.home-site`. Imported through `resources/js/homeSite.ts`.
- `resources/js/layouts/HomeLayout.vue` — landing, owners commercial landing and
  listing detail.
- `resources/js/layouts/ExploreLayout.vue` — full-height `/terrazas` catalog.
- `resources/js/pages/Owners.vue` — commercial landing at `/propietarios`.
- `resources/js/app.ts` — maps `Owners` → `HomeLayout`,
  `Listings/Public/Home` → `HomeLayout`, `Listings/Public/Index` →
  `ExploreLayout`, other `Listings/Public/*` → `HomeLayout`.
- Public fonts (Plus Jakarta Sans) are requested from each public layout's
  `<Head>`; the admin never downloads them.

## Isolation

- Public CSS is scoped to `.home-site` and emitted only for public page chunks.
- The admin uses `resources/css/app.css`, shadcn-vue and its own layouts; it
  loads only `app.css` in production.
- Public pages never import shadcn components or admin tokens.
- `Bookings/Public/Availability` uses `HomeLayout` and the public variant of the
  shared calendar; the admin calendar keeps its default theme.
- The legacy `PublicShell` and the remaining transactional documents still use
  app/shadcn styles; they are listed as pending migrations in the Guidelines.

## Geo search

See [`GEO-SEARCH.md`](GEO-SEARCH.md) for the search filters, map/viewport
behaviour, suggestions endpoint and limitations.

## Shared public components

- `resources/js/components/home/` — header, footer, venue card, pagination.
- `resources/js/components/public/GeoSearchBar.vue` — catalog search bar.
- `Modules/Listings/resources/js/components/GeoResultsMap.vue` — map with
  clustering, selection and viewport search.
- `Modules/Listings/resources/js/components/ListingMap.vue` — single-listing map
  on the detail page.

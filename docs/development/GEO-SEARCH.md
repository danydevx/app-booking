# Public geo search

Geographic discovery for the public catalog (`/terrazas`), built on the existing
Leaflet + OpenStreetMap setup and the public listing data.

## What existed before

- `/terrazas` with `state`, `city`, `capacity`, `service` and radius search via
  `lat`, `lng`, `radius` (`PublicListingSearch`).
- Leaflet map (`ListingMap`) with markers clustered only by server pagination,
  no selection sync and no viewport search.
- Listings store `country`, `state`, `city`, `postal_code`, `latitude`,
  `longitude`; all published demo listings have coordinates.
- `Geocoder` contract bound to `NullGeocoder` (no external geocoding).
- No price filter, no postal-code filter, no free-text location search, no
  `colonia` field, no map bounds search, no suggestions endpoint.

## Backend

`PublicListingSearch` now supports:

- `q` free text resolved by `ResolveSearchLocation` into state / municipality /
  postal code. Unknown text returns a `location` validation error instead of a
  silent empty search.
- `postal_code` exact match.
- `price_min` / `price_max` in MXN pesos against `price_from_cents`. Listings
  without a published starting price are excluded while the filter is active.
- Viewport bounds (`north`, `south`, `east`, `west`) applied in SQL with
  `whereBetween`; a partial or inverted viewport is rejected.
- Radius search is still supported (`lat`, `lng`, `radius`) for compatibility.
- `mapListings`: every matching listing with coordinates (cap:
  `listings.search.map_markers`, default 300), independent from pagination and
  without owner data.
- `PublicListingController@locations` (`/terrazas/ubicaciones?q=`): real
  suggestions from the municipality catalog, states and postal codes of
  publicly visible listings, cached for 60 seconds.

All queries keep `publiclyVisible()` (published, not suspended, active team).

## Frontend

- `GeoSearchBar`: location input with debounced suggestions and keyboard
  navigation, guests, price, characteristic, mobile filter toggle.
- Two-panel catalog on desktop: map left, results right, map sticky.
- Card ↔ marker sync: selecting a card highlights and flies to its marker;
  selecting a marker highlights and scrolls to its card.
- Map clustering at low zoom (70 px grid), individual markers from zoom 14.
- `Buscar en esta zona`: the map only emits its bounds on `moveend`; the page
  stores them and performs a single request when the user confirms. Filters are
  preserved.
- Mobile: list first, segmented `Lista / Mapa` control, map refreshes its size
  when shown, filters stay in the URL.
- Price, guests, characteristic, postal code and location are stored in the URL
  and survive pagination.

The catalog now uses the approved public identity from the home page
(`home.css`, Plus Jakarta Sans, white surfaces, pink accents, dark CTAs) and
`ExploreLayout`: full viewport-height exploration area, full-width header/search,
58/42 map and list split with independent list scroll. The site footer follows at
full width below the exploration area (the page scrolls only to reach it).

## Not supported yet

- **Colonia names**: the listing model has no neighborhood field and the geocoder
  is `NullGeocoder`. Areas can be explored with the map viewport search, but a
  free-text neighborhood cannot be resolved.
- **Date availability filter**: availability is per listing, not part of the
  public search. No fake date filter was added.
- **Radius UI**: the backend/URL support remains, but the interface uses the map
  viewport as the geographic control. No arbitrary center can be resolved
  without a geocoder.
- **Street addresses / geocoding**: not configured. Postal codes and
  municipalities are resolved from the local catalog and existing listings.

## Tests

- `Modules/Listings/tests/Feature/PublicListingTest.php`: location resolution
  (municipality, postal code, unknown), bounds, price, map points, suggestions,
  partial bounds and price ordering.
- Playwright: city search, free text, postal code, zone search, card/marker
  sync, empty zone, filters + capacity, mobile list/map, URL preservation.

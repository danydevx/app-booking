# AApp Booking — Public Frontend Design Guidelines

> Toda nueva página o componente del Frontend público de AApp Booking debe seguir
> estas Guidelines.
>
> Antes de implementar una nueva vista pública, el agente debe revisar este
> documento y reutilizar los tokens y componentes existentes.
>
> No se permite introducir una identidad visual alternativa ni recuperar estilos
> antiguos sin una decisión explícita de diseño.
>
> Los cambios al sistema visual deberán reflejarse también en estas Guidelines.

This document describes the system **as implemented** in `resources/css/home.css`
and the public Vue components. The landing (`/`) is the visual reference.

---

## A. Product identity

AApp Booking is a directory and booking platform for terraces, gardens and event
venues in Mexico. The public audience is people planning celebrations
(birthdays, weddings, XV years, family gatherings, corporate events) and venue
owners who want to publish their space.

**Visual personality:** clean, commercial, photographic and aspirational. White
surfaces, a vibrant brand accent, large photography and generous spacing. It
must look like a real discovery platform, not a SaaS landing template.

**Design principles**

1. Photography first; interface second.
2. White, airy layouts with one strong brand accent.
3. Clear hierarchy: oversized headings, readable body, quiet secondary text.
4. Useful data only: real listings, real cities, real prices. Never invent
   ratings, availability or statistics.
5. Search and exploration are the primary interactions.
6. One public identity; the admin is a separate product surface.

**Public vs Admin:** the public frontend uses its own tokens, fonts and
components. The admin keeps shadcn-vue, its tokens, its layout and its own
typography. They never share component styles.

---

## B. Colors

Tokens live on `.home-site` in `resources/css/home.css`.

| Token                  | Value     | Use                                                        |
| ---------------------- | --------- | ---------------------------------------------------------- |
| `--home-primary`       | `#F50557` | Primary buttons, active tabs, brand mark, selected markers |
| `--home-primary-hover` | `#D9044A` | Primary hover                                              |
| `--home-primary-deep`  | `#C90345` | Small text links, prices, eyebrows (text contrast)         |
| `--home-soft`          | `#FFE4EC` | Soft badges, selected background, image fallbacks          |
| `--home-dark`          | `#111827` | Main text, headings                                        |
| `--home-secondary`     | `#4B5563` | Body copy, secondary labels                                |
| `--home-muted`         | `#6B7280` | Meta, captions, placeholders                               |
| `--home-bg`            | `#FFFFFF` | Page background                                            |
| `--home-bg-alt`        | `#FAFAFA` | Alternate sections, hover backgrounds                      |
| `--home-surface`       | `#FFFFFF` | Cards, panels, header                                      |
| `--home-border`        | `#E5E7EB` | Borders and dividers                                       |
| `--home-cta`           | `#1F2937` | Dark CTA (owner actions, WhatsApp)                         |
| `--home-cta-hover`     | `#111827` | Dark CTA hover                                             |

**Color rules**

- Keep the page predominantly white. Pink is an accent, not a background.
- Do not paint large areas solid pink or dark. Dark is for primary contrast CTAs
  and selected markers only.
- On white, small pink text uses `--home-primary-deep` for contrast; `#F50557`
  is for fills, icons and active states.
- No decorative gradients on sections, cards, CTAs or banners. Photography keeps
  its natural color; the owners hero may use one functional white wash
  (`.home-owners-hero-wash`, top-left to bottom-right) over its background photo
  for legibility, while the main landing keeps the flat dark
  `.home-hero-overlay`. Avoid any other gradient.
- No additional palettes without an explicit design decision. Admin colors never
  apply to public pages.

---

## C. Typography

**Family:** Plus Jakarta Sans (Bunny), weights 400/500/600/700/800. Fallback:
`ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif`.
Loaded in the public layouts' `<Head>`, never in the admin.

| Role             | Size                                 | Weight  | Notes                                         |
| ---------------- | ------------------------------------ | ------- | --------------------------------------------- |
| Hero H1          | 44px mobile → 48 → 60 → 64px desktop | 800     | `line-height: 1.04`, max width ~4xl           |
| Page H1 (detail) | 30px → 36 → 48px                     | 800     | `line-height: 1.1`                            |
| Section H2       | 32px → 40px                          | 800     | `line-height: 1.1`                            |
| Sub-section H3   | 18–20px                              | 700     |                                               |
| Card title       | 20–22px                              | 700     | `line-height: 1.35`                           |
| Body             | 16px                                 | 400     | `line-height: 1.6–1.7`                        |
| Lead             | 18–20px                              | 400     | Hero/detail intros                            |
| Small / meta     | 13–14px                              | 500–600 | Never smaller than 12px for labels            |
| Eyebrow          | 13px                                 | 700     | Uppercase, `letter-spacing: .14em`, deep pink |
| Field label      | 12px                                 | 700     | Uppercase, `letter-spacing: .1em`, muted      |
| Button           | 16px                                 | 600     | 14px for small buttons                        |

Rules: headings `letter-spacing: -0.02em`; body never below 16px for primary
copy; secondary text uses `--home-secondary`/`--home-muted`, never light gray for
important content.

---

## D. Layout

### Editorial / Home

- Container: `max-width: 1440px`, centered, padding `20px / 24px / 32px`
  (mobile / tablet / desktop).
- Sections: vertical rhythm `py-16` to `py-24`.
- Long copy max 720–800px (`max-w-2xl` / `max-w-3xl`).
- Full-bleed hero; inner copy aligned to the 1440 container.
- Header: sticky, white, 68px tall, wide variant available for explore.
- Footer: white, border-top, 4 columns on desktop.

### Exploration / Catalog (`/terrazas`)

- `ExploreLayout`: `min-h-[100dvh]`; header row; full-width search/toolbar row;
  exploration area with height `calc(100dvh - 4.3125rem)`.
- Desktop split: map `58%` / results `42%`, both `min-w-0`; no `max-width` on the
  pair; results column scrolls independently; map fills its region with no card,
  border, radius or padding.
- The public footer follows the exploration area at full width; the page scrolls
  only to reveal it.
- Mobile: one column, `Lista / Mapa` toggle; the chosen view fills the remaining
  height; filters and URL are preserved.

### Detail (`/terrazas/{slug}`)

- 1440 container; back link, header block, full-width gallery (16:10 main image
    - thumbnail grid), then a two-column grid `1fr / 22rem` with a sticky sidebar
      on `lg`. On mobile the sidebar (price/contact) appears before the long content,
      single column.
- Sections separated by `border-t` + `pt-8`; no dashboard-like card grids for
  reading content. Cards only for services, packages, promotions and price
  ranges. Seasonal price-range cards span the full content width (one column).

### Commercial landing for owners (`/propietarios`)

- Purpose: recruit new owners and explain the product as a complete business
  tool (publish → receive requests → organize bookings → manage clients →
  quote/contract).
- Composition, in order: split hero (copy left, three-image slider right) over a
  washed venue background, "Todo tu negocio, mejor organizado", visibility,
  operations, **CRM Pro** benefits grouped by business outcome, three
  photographic step cards, product feature tabs, video walkthrough, plan
  comparison, FAQ and a final conversion band.
- Section classes for styling/analytics: `.section-hero`, `.section-features`,
  `.section-visibility`, `.section-operations`, `.section-crm-pro`,
  `.section-steps`, `.section-product-tabs`, `.section-video`, `.section-plans`,
  `.section-faq` and `.section-cta`.
- CTA hierarchy: primary dark "Comenzar gratis" (register or panel), secondary
  outline "Conoce las herramientas" (anchor), pink/dark Pro CTA that uses the
  real registration/billing flow. Never implement a parallel checkout.
- Content rules: real capabilities only; Free must feel useful (one terrace,
  five photos, requests, availability, manual bookings). Pro capabilities must
  be clearly labeled as Pro. Do not promise guaranteed bookings, income or
  occupancy. Do not mention Stripe identifiers, feature flags or internals.
- Plan data: Free comes from `config/billing.php`; Pro from `billing_plans`
  (editable at `/app/platform/plans`). The public price is the active Stripe
  amount, cached; a local fallback amount is used when Stripe is unavailable.
- Images: real venue photography; do not fabricate dashboard screenshots or
  metrics. Product screenshots may be added later only if they show real,
  non-private data.
- Reuse `HomeLayout`, `HomeHeader`, `HomeFooter`, `.home-*` tokens and the
  editorial spacing rules. No new visual identity.

### Breakpoints (Tailwind)

`sm 640`, `md 768`, `lg 1024`, `xl 1280`, `2xl 1536`. Mobile-first; the public
nav collapses below `md`; the explore split starts at `lg`; result grids are
1 / 2 / 3 / 4 columns depending on context.

---

## E. Public components

All public components live in `resources/js/components/home/` (landing,
catalog cards, pagination) and `resources/js/components/public/` (search bar).

| Component               | Variants / usage                                                                    |
| ----------------------- | ----------------------------------------------------------------------------------- |
| `HomeHeader`            | Wide prop for `/terrazas`; sticky white header, mobile menu                         |
| `HomeFooter`            | `flush` prop removes top margin; brand + links + legal                              |
| `HomeSearch`            | Landing hero search (state/city/guests/service)                                     |
| `GeoSearchBar`          | Catalog search: location suggestions, guests, price, characteristic, mobile filters |
| `HomeVenueCard`         | Venue card; `selectable` + `selected` for map sync                                  |
| `HomePagination`        | Catalog pagination, pink active page                                                |
| `GeoResultsMap`         | Leaflet map, clustering, selection, bounds callback                                 |
| `ListingMap`            | Single-listing map on the detail page                                               |
| `FeatureShowcaseSlider` | Reusable Swiper product carousel: 32% copy / 68% screenshot, arrows + progress      |
| `ProductFeatureTabs`    | Tabbed product tour: pill tabs, 35/65 panel, feature list and expandable screenshot |
| `HeroImageSlider`       | Owners hero: 3-image Swiper, loop, 700 ms, pagination pills below                   |
| `VideoShowcase`         | Reusable video section: heading + 16:9 native player, poster and optional captions  |
| `FeatureListItem`       | Feature/benefit row: primary circle, 20px icon, centered against text; solid/soft   |
| `FeatureIcon`           | Public Lucide icon with token-based size, variant and optional accessible label     |

**Feature Showcase Slider**

- Purpose: present software capabilities as a product carousel. The owners
  landing now uses `ProductFeatureTabs` for the full software tour; this
  component remains available for other pages and shorter feature sets.
- File: `resources/js/components/public/FeatureShowcaseSlider.vue`; data type:
  `FeatureSlide` in `resources/js/components/public/featureShowcase.ts`.
- Props:
    - `slides: FeatureSlide[]` — required. Each slide: `id`, `eyebrow?`, `title`,
      `description?`, `features[]`, `image?` (`string | null`), `imageAlt?`.
    - `ariaLabel?: string` — region label, default “Funcionalidades del producto”.
- Content stays in the page; the slider never contains CRM copy.
- Layout: desktop `lg` grid `32fr / 68fr`; copy vertically centered; image
  16:10 with `object-contain`, neutral `--home-bg-alt` background, 18px radius
  and a subtle border. Tablet keeps the two columns while there is room; below
  `lg` the order is category/title → features → image → arrows/progress.
- Navigation: Swiper 11 with one visible slide, `effect: 'slide'`, 24px gap,
  no loop, no autoplay, no 3D or coverflow, 650 ms transition with a standard
  ease-in-out curve. Arrows sit under the copy, next to the progress bar; the
  bar and `01 / 05` counter reflect the active slide. Arrows disable at the
  ends; touch and keyboard (arrow keys when the region is focused) work.
- Screenshots: store real captures in `public/images/showcase/crm/` with
  descriptive names (`clientes.webp`, `seguimiento.webp`, `cotizaciones.webp`,
  `reportes.webp`, `portal-cliente.webp`). Pass the public path in `image`.
  Missing or broken images render the neutral placeholder (image icon +
  “Captura próximamente”). Never fabricate dashboards, tables or metrics.
- Animation: text and screenshot live in the same `SwiperSlide` and move
  together with the native wrapper transform; no per-element entrance
  animations. `prefers-reduced-motion: reduce` disables the Swiper transition
  and the progress-bar width animation.
- Accessibility: region with `aria-label`/`aria-roledescription`, slides as
  groups with “N de M”, non-active slides `aria-hidden` + `inert`, arrows with
  `aria-label` and disabled states, progressbar values, and a polite live region
  announcing “Slide N de M”. Do not rely on gestures alone.
- Reuse:

    ```vue
    <FeatureShowcaseSlider
        :slides="bookingSlides"
        aria-label="Funcionalidades de reservas"
    />
    ```

**Product Feature Tabs**

- Purpose: interactive product tour on `/propietarios` covering dashboard,
  spaces, requests, customers, pipeline, quotes, bookings and contracts.
- File: `resources/js/components/public/ProductFeatureTabs.vue`; tab types in
  `resources/js/components/public/productFeatureTabs.ts`.
- Props: `tabs: ProductFeatureTab[]`, `ariaLabel?` and
  `screenshotPlaceholder?`. Each tab: `id`, `label`, `eyebrow`, `title`,
  `description`, `features[]` (`text`, `icon`), `screenshot` (`string | null`)
  and `screenshotAlt`.
- Data and screenshots stay in the page. Screenshots live under
  `public/images/showcase/plataforma/`; when `screenshot` is `null` the
  component renders a neutral “Captura próximamente” surface. When present, the
  image is lazy-loaded, contained at 16:10 and opens full size in a new tab.
- Behavior: ARIA tabs with roving focus and Arrow/Home/End keys; 35/65 columns
  from `md`, stacked on mobile; horizontal tab scrolling with the active tab
  kept in view; 180 ms fade/slide transition disabled by
  `prefers-reduced-motion`; no autoplay.
- Feature icons use `FeatureListItem variant="soft"` for the low-opacity
  primary circle, while benefit lists keep the solid circle.

**Video Showcase**

- Purpose: present a locally hosted product walkthrough, first used on
  `/propietarios` between “Cómo funciona” and the plans section.
- File: `resources/js/components/public/VideoShowcase.vue`; media types in
  `resources/js/components/public/videoShowcase.ts`.
- Props: `title`, `description`, `src` (`string | null`), `poster`
  (`string | null`), `fallback`, `unavailable` and optional `tracks`
  (`VideoShowcaseTrack[]`). Copy always comes from the page.
- Media: MP4 at `public/videos/plataforma-demo.mp4`, poster at
  `public/images/showcase/plataforma/portada-demo.webp` and optional WebVTT at
  `public/videos/plataforma-demo.es.vtt`. `OwnersController` exposes each file
  only when it exists, so dropping the files in is enough to enable the player.
- Player: native HTML5 `<video>` with `controls`, `preload="metadata"`,
  `playsinline`, no autoplay and no loop. The 16:9 surface reserves layout
  space and uses the card radius, border and subtle shadow tokens. If the
  source is missing or fails, the component renders a neutral placeholder
  instead of a broken player.

**Hero Image Slider**

- Purpose: rotate three venue photographs in the owners hero (`/propietarios`).
- File: `resources/js/components/public/HeroImageSlider.vue`; type `HeroSlide`
  (`src`, `alt`) in `resources/js/components/public/heroSlider.ts`.
- Props: `slides: HeroSlide[]` and `ariaLabel?`.
- Media: local images at `public/images/owners/hero/slide-1.webp`,
  `slide-2.webp` and `slide-3.webp`; the hero background is `fondo.webp`.
  Replace the files to change the photos without touching the page.
- Behavior: Swiper `slide` effect, one visible slide, loop, 700 ms, 7 s
  autoplay that pauses on hover and stops after interaction. Pagination sits
  below the image in a small white pill. `prefers-reduced-motion` disables
  autoplay and the horizontal transition.
- Layout: 4:3 with 20px radius, same footprint as the old hero image; two
  columns from `lg`, stacked below. The hero uses `.home-owners-hero-wash` for
  the white top-left to transparent bottom-right wash.

### Iconografía pública

- Library: **Lucide** through `@lucide/vue` (already installed and used by the
  admin). Do not add Material Symbols, Heroicons or a second icon library.
- Component: `resources/js/components/public/FeatureIcon.vue`; the kebab-case
  registry and types live in `resources/js/components/public/featureIcon.ts`.
- Props: `name` (registry key), `size` (`sm | md | lg`), `variant`
  (`primary | neutral | plain | muted`), `label?` and `strokeWidth?`.
- Sizes and containers:
    - `sm`: 36px mobile / 40px desktop, 18px icon. Compact circular use.
    - `md`: 44px mobile / 48px desktop, 22px icon. Default highlighted icon.
    - `lg`: 48px mobile / 56px desktop, 24px icon. Featured or CTA icon.
    - Inline icons use `variant="plain"` or `muted` with `size="sm"` and no
      container.
- Variants and tokens:
    - `primary`: soft pink circle (`--home-soft`) with brand icon
      (`--home-primary`).
    - `neutral`: alt surface (`--home-bg-alt`), border (`--home-border`) and dark
      icon (`--home-dark`).
    - `plain`: no container, brand icon; for inline meaning, standalone checks
      and the comparison table.
    - `muted`: no container, muted icon (`--home-muted`); for “not included” or
      secondary states.
- Feature and benefit lists use `FeatureListItem` (`icon` + `text` or default
  slot). The default `solid` variant is a 36px `--home-primary` circle with a
  white 20px Lucide icon; `soft` uses primary at low opacity with a primary
  icon for the product tabs. Both use a 12px gap and `items-center`, so the
  circle stays centered against one, two or three lines of text. Do not rebuild
  the row with `FeatureIcon variant="plain"` plus margin offsets.
- Use circular containers for section-level benefits and CRM categories.
  Process steps use photographic cards with large numerals and no icons.
  Pricing card lists use `FeatureListItem`; the comparison table keeps inline
  `plain`/`muted` icons next to Sí/No.
- Keep icons purposeful: not every heading, paragraph or button needs one.
  Avoid three identical icon cards when an editorial list communicates better.
- Decorative icons are `aria-hidden="true"` by default. If an icon conveys
  meaning on its own, pass `label` so the root becomes `role="img"` with an
  accessible name; keep the text visible as well. Never rely on color alone.
- Lucide stroke width stays at the default `1.8`; do not mix filled, duotone or
  colored icon sets.
- Examples:

    ```vue
    <FeatureIcon name="calendar-days" size="md" variant="primary" />
    <FeatureIcon name="check" size="sm" variant="plain" />
    <FeatureIcon
        name="badge-check"
        size="md"
        variant="neutral"
        label="Plan verificado"
    />
    ```

**Buttons** (`.home-btn`)

- `--primary`: pink fill, white text — main action, search, availability.
- `--dark`: dark fill, white text — owner CTA, WhatsApp.
- `--outline`: white with border — secondary actions.
- `--sm`: compact size.
- Hover changes background only; active translates 1px; disabled opacity `.55`.

**Fields** (`.home-field-label`, `.home-input`, `.home-select`, `.home-control`)

- `.home-input` / `.home-select`: 16px/600, transparent background inside the
  bordered `.home-search` panel; focus ring pink.
- `.home-control`: standalone bordered control (1px `#E5E7EB`, 12px radius,
  white, 16px) for public forms; focus border pink + 3px soft pink ring.
- `.home-checkbox`: native checkbox with `accent-color` pink.
- Labels uppercase 12px muted. No native select arrows outside the shared style.

**Surfaces**

- `.home-card`: white, 18px radius, `#E5E7EB` border, soft shadow, hover lift.
- `.home-search`: white, 20px radius, border, floating shadow.
- Panels: white, 18px radius, border; no heavy shadows.
- `.home-chip`: pill, 13px/600, alt background, border.
- `.home-tab`: pill, 15px/600; active pink fill.
- Empty states: white panel, heading + body + primary/outline action.
- Loading: Inertia progress bar; suggestions show “Sin coincidencias” when empty;
  no skeleton screens yet.
- Gallery/lightbox: main image 16:10 + square thumbnails; lightbox on black with
  close, previous/next, counter, Escape/arrow keys.
- Request panel (detail): white panel with price, host, availability CTA,
  WhatsApp, email, phone, unavailable dates.

**Icons:** Lucide only when an icon adds meaning; never one large icon per
paragraph. The public header/footer use no decorative iconography.

---

## F. Photography

- Cards: `aspect-[3/2]`, `object-fit: cover`, radius 18px, subtle 1.04 zoom on
  hover.
- Detail gallery: main `16:10` radius 18px; thumbnails square radius 12px.
- Space-type tiles: `4:3`, caption below (no text over photos).
- Hero: full-bleed, `object-fit: cover`, flat translucent overlay for legibility.
- Lazy loading for below-the-fold images; `fetchpriority="high"` for the hero.
- Always provide `alt`; if a cover fails, show a soft pink monogram fallback.
- Do not invent listing photography; demo placeholders are data, not design.

---

## G. Visual rules

- Page background is white (`#FFFFFF`); alternate sections may use `#FAFAFA`.
- No decorative gradients in sections, cards, CTAs or banners. The owners hero
  uses the functional `.home-owners-hero-wash` white wash for text legibility.
- No glassmorphism, oversized shadows, floating decorative cards or invented
  statistics.
- One accent (pink) + one contrast CTA (dark). Never multiple accent colors.
- Radii: 12px controls, 18px cards/panels, pill chips/tabs.
- Spacing: generous but controlled; `py-16`–`py-24` between sections.
- Borders are 1px `#E5E7EB`; shadows are subtle.
- Do not reproduce generic SaaS patterns (three feature cards in a row,
  badge-per-paragraph, huge icon blocks).
- The public site must never import admin/shadcn components.

---

## H. Responsive

- Mobile first; test 390, 768, 1024, 1440, 1920.
- Public nav collapses below `md`; explore switches below `lg`.
- Landing catalog: 1 / 2 / 3 / 4 columns.
- Catalog results: 1 column in narrow panels, 2 at `xl`.
- Detail: single column on mobile; two columns with sticky sidebar at `lg`.
- Avoid horizontal overflow; tables scroll inside their own container.
- Explore uses `100dvh` and `min-h-0` to avoid mobile browser height issues.

---

## I. Accessibility

- WCAG AA contrast: main text `#111827` on white (~16:1), secondary `#4B5563`
  (~7.5:1), muted `#6B7280` (~4.9:1). Small interactive text uses deep pink.
- Visible focus: 2px `#F50557` outline with 3px offset on `.home-site`.
- Every input has a `<label>`; suggestions use combobox/listbox semantics and
  keyboard navigation (arrows, Enter, Escape).
- Cards used for map selection are buttons with `aria-label`; the detail link is
  a separate link.
- Lightbox: `role="dialog"`, `aria-modal`, Escape/arrow keys, close button.
- Disabled buttons use opacity and `cursor-not-allowed`; do not rely on color
  alone.
- Touch targets: buttons and controls at least ~44px high on mobile.
- Use semantic `header`, `main`, `section`, `footer`, `nav`, `button`, `a`.

---

## J. Admin isolation

**The public visual system and the admin visual system are independent.**

- Public tokens and component classes live in `resources/css/home.css` and are
  scoped under `.home-site`. Nothing there overrides `--primary`, `--background`,
  `--radius` or other admin tokens.
- Public pages import `@/homeSite` (`resources/js/homeSite.ts`, which imports
  `resources/css/home.css`), so Vite emits the public CSS only for the public
  page chunks.
- Layouts: `HomeLayout` for the landing and detail, `ExploreLayout` for the
  catalog; both load the public fonts through `<Head>`.
- The admin uses `resources/css/app.css`, shadcn-vue and `AppLayout`/
  `PlatformLayout`. It never loads the public CSS.
- Verified in production: `/` and `/terrazas` load the public CSS chunk; the
  admin loads only `app.css` and keeps Instrument Sans and its tokens.
- Never modify the admin to implement a public change. Never import shadcn
  components, admin tokens or admin layouts into public pages.

---

## K. Destructive actions in the admin

- Never use `window.confirm`. Use `resources/js/components/ConfirmDialog.vue`
  (shadcn `Dialog`) for delete, archive, merge, anonymize, cancel or ownership
  transfer actions.
- Show the object name in the description when possible: "Se eliminará la
  terraza «X»".
- The destructive button is the last action, uses `variant="destructive"` and
  states the verb ("Eliminar terraza", "Anonimizar cliente").
- Cancel is secondary and comes first.
- Disable both buttons while the request is processing.
- Deleting a listing, media, package, service, promotion, price range, customer
  data, availability series or a team must always ask for confirmation first.

---

## Current public views

| Route                                           | Component                      | Layout                 | Status                                                                            |
| ----------------------------------------------- | ------------------------------ | ---------------------- | --------------------------------------------------------------------------------- |
| `/`                                             | `Listings/Public/Home`         | `HomeLayout`           | Consistent                                                                        |
| `/terrazas`                                     | `Listings/Public/Index`        | `ExploreLayout`        | Consistent                                                                        |
| `/terrazas/{slug}`                              | `Listings/Public/Show`         | `HomeLayout`           | Consistent                                                                        |
| `/terrazas/{slug}/disponibilidad`               | `Bookings/Public/Availability` | `PublicShell` (legacy) | Pending: needs a public calendar/form variant without touching the admin calendar |
| `/terrazas/{slug}/reportar`                     | `Platform/Public/Report`       | `PublicShell` (legacy) | Pending                                                                           |
| `/terrazas/{slug}/reclamar`                     | `Platform/Public/Claim`        | `HomeLayout`           | Consistent: CTA en el detalle y formulario con el sistema público                 |
| Booking portal / contract / quote / unsubscribe | `Bookings/Public/*`            | `PublicShell` (legacy) | Pending: transactional documents outside discovery                                |
| `/login`, `/register`, auth                     | starter kit                    | `AuthLayout`           | Starter-kit auth, outside the discovery identity                                  |

`PublicShell` and the legacy transactional pages are intentionally left
untouched in this iteration. The availability calendar keeps a `variant` prop:
the admin uses the default theme and the public page opts into the home theme, so
the admin calendar is not affected. Any future public migration must follow
these Guidelines.

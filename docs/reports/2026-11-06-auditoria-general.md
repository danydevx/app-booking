# Auditoría general — app-booking

**Fecha:** 2026-11-06
**Commit auditado:** `0ba967a` (`main`, `origin/main`)
**Alcance:** repositorio completo — arquitectura, código, tests, CI, dependencias, documentación y proceso de trabajo.
**Método:** lectura de código/configuración/docs + ejecución de los checks del proyecto.
**Estado del árbol:** limpio (`git status` sin cambios).

---

## 1. Resumen ejecutivo

El proyecto es un SaaS multi-cuenta para renta de terrazas, construido sobre Laravel 13 + Inertia 3 + Vue 3/TypeScript + Tailwind 4 + shadcn-vue, con un monolito modular bajo `Modules/`. El MVP funcional (fases 0–7) está implementado y tiene una base de tests sólida: **266 tests / 1,462 aserciones, todos en verde**.

La calidad del backend es buena: Pint, PHPStan nivel 7, tipos de Vue y build pasan. Los **dos bloqueadores** detectados en la auditoría ya están resueltos:

1. **Formato/lint frontend:** `npm run check` pasa sobre 144 archivos y 109 archivos lintados.
2. **Avisos de Symfony:** actualizados a `v7.4.23`; `composer audit` ya no reporta vulnerabilidades.

| Área                  | Comando                      | Resultado                                    |
| --------------------- | ---------------------------- | -------------------------------------------- |
| Tests backend         | `php artisan test`           | **PASS** — 266 tests, 1,462 assertions, ~8 s |
| Formato PHP           | `vendor/bin/pint --test`     | **PASS**                                     |
| Análisis estático     | `vendor/bin/phpstan analyse` | **PASS** — 0 errores, nivel 7                |
| Tipos frontend        | `npm run types:check`        | **PASS**                                     |
| Build producción      | `npm run build`              | **PASS** — ~6 s                              |
| Formato/lint frontend | `npm run check`              | **PASS** — 144 archivos, 109 lintados        |
| Auditoría PHP         | `composer audit`             | **PASS** — 0 advisories                      |
| Auditoría npm         | `npm audit --omit=dev`       | **PASS** — 0 vulnerabilidades                |
| CI global             | `composer ci:check`          | **PASS**                                     |

**Veredicto:** MVP bien construido y bien probado, con CI verde. Queda pendiente la fase 8 (producción) y la deriva menor entre documentación/proceso y la realidad del repositorio.

---

## 2. Cómo estábamos trabajando

### 2.1 Historia real del repositorio

- **13 commits**, lineales, del **2026-10-30** al **2026-11-06**.
- Una sola rama: `main`. No hay ramas de fase, tags ni merges en el historial.
- Dos identidades de autor: `Daniel` (12 commits) y `Daniel Lopez` (1).
- Commits convencionales y consistentes a partir del scaffold: `feat(app-booking): …`, `docs(app-booking): …`, `chore: …`.

| Fecha      | Commit    | Mensaje                                                          | Fase asociada |
| ---------- | --------- | ---------------------------------------------------------------- | ------------- |
| 2026-10-30 | `57d01fe` | Set up a fresh Laravel app                                       | Scaffold      |
| 2026-10-30 | `4a60c88` | Install Laravel Boost                                            | Tooling       |
| 2026-10-30 | `8b104f8` | Configure Boost post-update script                               | Tooling       |
| 2026-10-30 | `e0b7964` | docs(app-booking): record phase 0 and bootstrap phase 1          | 0–1           |
| 2026-10-30 | `fc69125` | feat(app-booking): add account permissions and platform access   | 2             |
| 2026-10-30 | `29f5a9e` | refactor(app-booking): adopt module-owned structure              | 2–3           |
| 2026-10-30 | `d692507` | feat(app-booking): add listings catalog media and map            | 3             |
| 2026-10-30 | `b01529b` | feat(app-booking): add availability inquiries and deposits       | 4             |
| 2026-10-30 | `ae7b15a` | feat(app-booking): add stripe plans and entitlements             | 5             |
| 2026-10-31 | `2e59073` | feat(app-booking): finish stripe billing phase                   | 5             |
| 2026-10-31 | `89ac6f4` | feat(app-booking): add platform moderation and reports           | 6             |
| 2026-10-31 | `3e2f466` | feat(app-booking): add branding and transactional communications | 7             |
| 2026-11-06 | `0ba967a` | chore: set Node.js 24                                            | Mantenimiento |

### 2.2 Flujo de trabajo por fases

El trabajo siguió un esquema de **fases 0–7 con documentación de cierre**:

1. Leer la skill `.opencode/skills/app-booking-development/SKILL.md` y el archivo de la fase.
2. Implementar el alcance de la fase.
3. Ejecutar tests, build, Pint y análisis estático.
4. Actualizar `docs/development/PHASE-STATUS.md`.
5. Escribir el reporte de fase en `docs/development/phases/NN-*.md`.
6. Commit con los archivos revisados.

Esto se ve reflejado en los 8 reportes de fase existentes (`00` a `07`) y en las
métricas de `PHASE-STATUS.md` (197 tests en fase 7, más los tests añadidos
después: localización, singleton, clientes manuales, reservas manuales con confirmación, reprogramación, historial de reservas y anticipos, filtros y exportación de auditoría, métricas de ocupación y conversión, expiración, rangos, usuarios/roles, detalles de terraza, recordatorios, CSV, auditoría, dashboard, soporte de acceso, CSV endurecido y estado del sistema; 266 tests / 1,462 aserciones actuales).

### 2.3 Fortalezas del proceso

- **Cultura de tests:** 263 tests, 48 archivos con `RefreshDatabase`, 17 archivos con aserciones de Inertia. Se prueban autorización, aislamiento multi-cuenta, límites de plan, webhooks y doble reserva.
- **Documentación de decisiones:** `docs/decisions/0001-mvp.md` fija el alcance; `docs/PRODUCT-PLAN.md` describe el producto; `docs/development/MODULES.md` define la estructura modular.
- **Disciplina de alcance:** no se introdujo Bootstrap/jQuery ni una segunda librería de componentes; se respetó el starter kit.
- **Cierre por fase:** cada fase dejó reporte, estado y evidencia de comandos.

### 2.4 Deriva de proceso (mitigada)

- Los reportes de fase mencionan ramas `develop`, `phase-5-...`, etc. que **no
  existen**; todo está en `main`. `PHASE-STATUS.md` ahora aclara que el trabajo
  fue **trunk-based** y que esos nombres son etiquetas históricas.
- `PHASE-STATUS.md` actualizado al **2026-11-06**; `.nvmrc` y CI usan **Node 24**.
- La skill y su plantilla ya usan rutas relativas al repositorio.
- `AGENTS.md` sigue gitignoreado junto con `CLAUDE.md`/`opencode.json`
  (decisión pendiente).
- `README.md` y `LICENSE` añadidos.

---

## 3. Arquitectura actual

### 3.1 Stack y entrypoints

- **Backend:** Laravel `13.39.0`, PHP `8.3`, PHPUnit `12`, Laravel Cashier `16.9`, Fortify, Spatie Permission `8.3`, Spatie Activitylog `4.12`.
- **Frontend:** Inertia `3`, Vue `3.5`, TypeScript `5`, Tailwind `4`, shadcn-vue (`reka-ui`), Leaflet `1.9`, Vite Plus (`vp`).
- **Entrypoints:** `public/index.php` → `bootstrap/app.php`; providers en `bootstrap/providers.php`; rutas core en `routes/web.php` y `routes/settings.php`; rutas de módulos cargadas por cada `*ServiceProvider`.
- **Build:** `npm run build` → `vp build`; `composer dev` → `php artisan dev` (server + queue + pail + Vite).
- **Tests:** SQLite en memoria (`phpunit.xml`), colas/mail/cache en memoria o `array`, Stripe sin credenciales reales.

### 3.2 Mapa de módulos

| Área                                 | Responsabilidad                                                              | Archivos PHP/TS/Vue | LOC    | Tests             |
| ------------------------------------ | ---------------------------------------------------------------------------- | ------------------- | ------ | ----------------- |
| Núcleo (`app/`)                      | Auth Fortify, teams/cuentas, invitaciones, settings, branding base, WhatsApp | 56                  | 3,275  | 107 (en `tests/`) |
| `Modules/Platform`                   | Superadmin, moderación, reportes, branding global, auditoría                 | 49                  | 5,070  | 24                |
| `Modules/Listings`                   | Terrazas, media, catálogo público, mapa y búsqueda por radio                 | 47                  | 4,338  | 20                |
| `Modules/Bookings`                   | Disponibilidad, inquiries, clientes, depósitos, notificaciones               | 65                  | 5,163  | 24                |
| `Modules/Billing`                    | Planes, entitlements, Stripe Checkout/Portal/webhooks                        | 16                  | 1,749  | 22                |
| Frontend compartido (`resources/js`) | Layouts, componentes UI, composables, páginas core                           | 340                 | 28,704 | —                 |

> Nota: `resources/js` incluye el starter kit y componentes shadcn-vue; no todo es código propio.

- **71 rutas** registradas (sin vendor).
- **29 migraciones** entre `database/migrations` y `Modules/*/database/migrations`.
- **593 archivos** versionados.

### 3.3 Flujo de una request y aislamiento multi-cuenta

1. Middleware web: `HandleAppearance`, `HandleInertiaRequests`, `AddLinkHeadersForPreloadedAssets`, `SetTeamUrlDefaults`.
2. Rutas de cuenta con prefijo `{current_team}` + `EnsureTeamMembership`:
    - resuelve el equipo por slug,
    - exige pertenencia (`403`),
    - bloquea cuentas suspendidas (`403`),
    - cambia `current_team_id` si hace falta.
3. Controlador delgado → `FormRequest` → `Action`/servicio → modelo/política.
4. Políticas explícitas: `ListingPolicy`, `CustomerPolicy`, `BookingInquiryPolicy`, `BillingPolicy`, `TeamPolicy`.
5. Consultas tenant siempre filtradas por `team_id`; nunca se acepta `team_id` desde el request para autorizar.
6. Rutas públicas `terrazas/*` validan publicación y cuenta activa vía `Listing::scopePubliclyVisible()`.

### 3.4 Frontend

- `resources/js/app.ts` descubre páginas core (`resources/js/pages`) y de módulos (`Modules/*/resources/js/pages`) con `import.meta.glob`.
- Convención de nombre Inertia: `{Module}/{Page}` → `Modules/{Module}/resources/js/pages/{Page}.vue`.
- `App\Support\Inertia\PageAsset` resuelve la ruta Vite en servidor; `vite.config.ts` registra las páginas de módulos como inputs.
- Layouts: `AppLayout`, `AuthLayout`, `PlatformLayout`, `SettingsLayout`; las páginas públicas no usan layout.
- Wayfinder genera `resources/js/actions`, `resources/js/routes`, `resources/js/wayfinder` (gitignoreados; se regeneran en `dev`/`build`).

### 3.5 Datos, dinero y fechas

- SQLite local; esquema compatible con MySQL para staging/producción.
- Dinero en **unidades menores enteras** (`amount_cents`) + moneda ISO (`MXN`).
- Timestamps en UTC; zona horaria por equipo/listing (`America/Mexico_City` por defecto).
- Estados con enums respaldados por string (`ListingStatus`, `InquiryStatus`, `DepositStatus`, `ReportStatus`, etc.).

### 3.6 Integraciones

- **Stripe/Cashier:** `Team` es el customer model; Checkout, Portal, webhooks y entitlements. El webhook de Cashier se registra fuera del grupo `web` (sin CSRF/sesión) y valida firma dentro del controlador.
- **Idempotencia de webhooks:** `LogStripeWebhook` usa `Cache::add("stripe:webhook:{id}")` por 1 día y dispara el correo de pago fallido una sola vez.
- **Notificaciones:** todas las notificaciones de usuario implementan `ShouldQueue`; en local se procesan con el queue listener de `composer dev`.
- **Storage:** logos y media en disco `public`; en local existe `public/storage` (symlink, no versionado).
- **WhatsApp:** enlace click-to-chat `wa.me`, sin API automatizada (MVP).

---

## 4. Verificación ejecutada

| Comando                                  | Resultado | Evidencia                            |
| ---------------------------------------- | --------- | ------------------------------------ |
| `php artisan test`                       | PASS      | 266 passed, 1,462 assertions, 7.9 s  |
| `vendor/bin/pint --test`                 | PASS      | sin archivos por corregir            |
| `vendor/bin/phpstan analyse`             | PASS      | 0 errores, nivel 7                   |
| `npm run types:check`                    | PASS      | `vue-tsc --noEmit` sin errores       |
| `npm run build`                          | PASS      | build en 5.95 s                      |
| `npm run check`                          | **FAIL**  | 27 archivos con problemas de formato |
| `composer audit`                         | **FAIL**  | 10 advisories, exit 1                |
| `npm audit --omit=dev`                   | PASS      | 0 vulnerabilidades                   |
| `php artisan route:list --except-vendor` | OK        | 71 rutas                             |
| `git status`                             | limpio    | sin cambios locales                  |

Estos resultados están alineados con `PHASE-STATUS.md` en tests y aserciones; la diferencia es que el check de frontend y la auditoría de Composer no formaban parte de la evidencia de cierre de fase.

---

## 5. Hallazgos

### 5.1 Alta prioridad (P0/P1) — resueltos

#### A-01 — Check de frontend/format (resuelto)

- **Estado:** resuelto el 2026-11-06.
- **Evidencia:** `npm run check` → `pass: All 144 files are correctly formatted` y `Found no warnings or lint errors in 109 files`.
- **Acción aplicada:** `npm run check:fix`, revisión del diff y `npm run check` de nuevo. `composer ci:check` pasa completo.
- **Nota:** el fix también formateó documentación y archivos de la skill; no cambió lógica.

#### A-02 — Advisories de seguridad en Symfony (resuelto)

- **Estado:** resuelto el 2026-11-06.
- **Evidencia:** `symfony/http-foundation`, `symfony/http-kernel`, `symfony/routing`, `symfony/serializer` y `symfony/yaml` actualizados a `v7.4.23`; `composer audit` → `No security vulnerability advisories found`.
- **Verificación:** suite SQLite y suite MySQL 8.4 → 266 tests / 1,462 assertions en ambas.
- **Nota:** se eliminó `@php artisan boost:update --ansi` de `post-update-cmd` porque fallaba cuando Boost no estaba inicializado (`boost:install`); el comando sigue disponible manualmente.

### 5.2 Prioridad media (P2)

#### M-01 — Deriva entre documentación de fases y la realidad de Git (mitigado)

- Los reportes históricos mencionan ramas `develop`, `phase-5-...`, etc.; **no
  existen**. `PHASE-STATUS.md` ahora incluye una nota que aclara que el trabajo
  fue trunk-based sobre `main` y que esos nombres son etiquetas históricas.
- `PHASE-STATUS.md` actualizado (fecha y Node 24/CI 22).
- La skill y la plantilla de estado usan rutas relativas al repositorio.
- **Acción pendiente:** no reescribir reportes históricos; mantener la nota.

#### M-02 — Onboarding incompleto (parcialmente resuelto)

- **Resuelto:** `README.md` con requisitos, Docker/MySQL, setup, comandos,
  arquitectura y enlaces; `LICENSE` MIT añadido.
- `AGENTS.md`, `CLAUDE.md` y `opencode.json` siguen en `.gitignore` (convención
  del starter kit); el contexto para agentes no se comparte por Git.
- No se añadieron `CHANGELOG.md` ni `SECURITY.md`.
- **Acción pendiente:** decidir si `AGENTS.md` se versiona; opcionalmente añadir
  `CHANGELOG`/`SECURITY`.

#### M-03 — Mensajes de validación/autenticación en inglés (resuelto)

- **Resuelto:** añadidos `lang/es/{validation,auth,passwords,pagination}.php`
  con traducciones al español y atributos comunes (`email`, `password`, `team`,
  `listing`, fechas, teléfono, etc.).
- Añadido `tests/Unit/LocalizationTest.php` para verificar un mensaje real.
- `APP_LOCALE=es` y `APP_FALLBACK_LOCALE=en` se mantienen.

#### M-04 — Huecos de CI y cadena de suministro (resuelto)

- **Resuelto:** `composer setup` usa `npm ci`; CI usa Node 24; Dependabot cubre
  `github-actions`, `composer` y `npm`; el job SQLite ejecuta `composer audit` y
  `npm audit --omit=dev`.
- `composer ci:check` sigue sin incluir auditorías; se ejecutan como paso de CI.
- Pendiente opcional: caché de `vendor/`/`node_modules` y `concurrency`.

#### M-05 — Controladores por encima de la regla de “controladores delgados” (resuelto)

- `ListingController` bajó de 307 a 235 líneas extrayendo `OwnerListingQuery` y
  `ListingFormData`.
- `BookingInquiryController` bajó de 297 a 223 líneas extrayendo
  `BookingInquiryQuery` y `BookingOptionCatalog`.
- `PlatformAccountController` bajó de 240 a 126 líneas con
  `PlatformAccountQuery` + `PlatformAccountPresenter`.
- `BillingController` bajó de 226 a 134 líneas con `BillingPageData`.
- **Verificación:** Pint, PHPStan y las suites SQLite/MySQL pasan.

#### M-06 — SSR: bundle no construido y configuración incompleta (resuelto)

- `package.json` define `build:ssr: vp build && vp build --ssr`. El plugin `@inertiajs/vite` genera el entrypoint SSR automáticamente desde `resources/js/app.ts`; no hace falta un `ssr.ts` manual. El build funciona y produce `bootstrap/ssr/app.js`.
- El problema real era operativo: `composer setup` solo ejecutaba `npm run build` (cliente), por lo que `php artisan inertia:start-ssr` fallaba con **“Inertia SSR bundle not found”**. Además, `config/inertia.php` tenía `ssr.enabled` fijo en `true` sin variables de entorno.
- **Resuelto:** `composer setup` ahora ejecuta `npm run build:ssr`; se añadieron `composer ssr` (arranca el servidor) y `composer ssr:check` (health check); la configuración SSR es env-driven (`INERTIA_SSR_ENABLED`, `INERTIA_SSR_URL`, etc.) y los tests desactivan SSR con `INERTIA_SSR_ENABLED=false`.
- **Verificación:** `npm run build:ssr` → OK; `composer ssr` + `composer ssr:check` → “Inertia SSR server is running”; request real → HTML renderizado en servidor.

#### M-07 — `PlatformSetting::current()` sin garantía de fila única (resuelto)

- `current()` devuelve la primera fila existente o inserta explícitamente `id = 1`
  con `insertOrIgnore` y luego `findOrFail(1)`; las primeras requests concurrentes
  no duplican la fila.
- Añadido `Modules/Platform/tests/Feature/PlatformSettingTest.php`.

### 5.3 Prioridad baja (P3)

#### B-01 — Deriva menor en documentación de módulos (resuelto)

- `docs/development/MODULES.md` aclara que los tests de módulo se descubren
  automáticamente por `phpunit.xml` (`<directory>Modules</directory>`).
- La guía de ingeniería ya no lista `account_settings`; usa `listing_reports`
  entre las tablas tenant-owned. Branding/timezone siguen en `teams`.

#### B-02 — Duplicación de resolución de equipo (resuelto)

- Unificado en `App\Concerns\ResolvesCurrentTeam`.
- Usado por Listing, ListingMedia, BookingInquiry, Customer, Availability y
  Billing; eliminado el trait duplicado de Bookings y los métodos privados
  `team()`.

#### B-03 — Búsqueda por radio en memoria con tope de 500 candidatos

- `PublicListingSearch` hace bounding box en SQL y luego filtra/ordena distancias en PHP, con `max_candidates = 500` y `max_radius_km = 100` (validado en el request).
- **Impacto:** correcto para MVP; el coste crece con el catálogo. Documentado en configuración.
- **Acción:** si el catálogo crece, mover el filtro de radio a SQL (Haversine/`ST_Distance`) o a un índice espacial; no antes.

#### B-04 — Sin tests de frontend, E2E ni cobertura

- No hay Vitest/Playwright/Cypress ni reporte de cobertura PHP.
- La lógica crítica está cubierta por feature tests de PHP, pero no hay verificación automatizada de interacción Vue.
- **Acción:** añadir cobertura PHP (`php artisan test --coverage`) y, si se prioriza, un smoke E2E de los flujos públicos (catálogo → detalle → inquiry) y del panel.

#### B-05 — `TeamName` es el archivo PHP más grande (388 líneas)

- Puede contener lógica copiada del starter kit; no es un problema funcional.
- **Acción:** revisar si se puede simplificar o reutilizar reglas del starter kit al tocar teams.

#### B-06 — Otros huecos de gobierno

- Sin `CHANGELOG.md` (los reportes de fase hacen de historial).
- Sin `SECURITY.md` para reporte de vulnerabilidades.
- Sin `CODEOWNERS`.
- Sin política de backup/rollback (corresponde a fase 8).

---

## 6. Lo que está bien (no tocar sin motivo)

- **Aislamiento multi-cuenta probado:** hay tests explícitos de “otra cuenta no puede leer/mutar”, “no puede adivinar el slug”, “no puede abrir billing”, “no puede resolver reportes”.
- **Autorización server-side:** políticas + `EnsureTeamMembership` + comprobaciones `ensureOwned`; la UI no es la única barrera.
- **Doble reserva:** `AcceptBookingInquiry` usa transacción, `lockForUpdate`, unicidad `(listing_id, date)` y manejo de `QueryException`; hay test de dos inquiries solapadas.
- **Webhooks Stripe:** firma inválida rechazada, entrega duplicada idempotente, pago fallido notifica solo al owner afectado.
- **Validación de uploads:** MIME + extensión + tamaño; imágenes/videos con límites por plan; test de archivos ejecutables rechazados.
- **Anti-abuso:** honeypot (`website` prohibido), consentimiento, throttle 5/min en inquiries y 5/min por IP en reportes.
- **Dinero y fechas:** enteros en centavos, moneda ISO, UTC + timezone por cuenta/listing.
- **Calidad automatizada:** Pint, PHPStan nivel 7, TypeScript `strict`, `vue-tsc`, build de producción.
- **UI:** sin Bootstrap/jQuery, sin segunda librería de componentes; shadcn-vue y Tailwind del starter kit.

---

## 7. Plan recomendado

### P0 — Dejar el repo en verde (completado)

1. **Formato/CI** — completado: `npm run check` y `composer ci:check` pasan.
2. **Seguridad Symfony** — completado: paquetes en `v7.4.23`, `composer audit` limpio.
3. **Verificación** — completado: suites SQLite y MySQL en verde, build correcto.

### P1 — Alinear proceso y documentación (completado salvo AGENTS)

4. ~~Corregir rutas y versiones en `.opencode/skills/**` y `docs/development/PHASE-STATUS.md`~~ — resuelto.
5. ~~Decidir y documentar el flujo Git real~~ — resuelto: nota trunk-based en `PHASE-STATUS.md`.
6. ~~Crear `README.md` y `LICENSE`~~ — resuelto.
7. Pendiente: decidir si `AGENTS.md` se versiona o se mantiene local.
8. ~~Publicar `lang/es` y traducir validación/auth/passwords~~ — resuelto con test.

### P2 — Endurecer CI y mantenibilidad

9. ~~Dependabot para `composer`/`npm`; `npm ci`; Node 24; auditorías en CI~~ — resuelto.
10. ~~Extraer lógica de los controladores más grandes~~ — resuelto: Listing,
    Booking, PlatformAccount y Billing extraídos a queries/presenters.
11. ~~Resolver el script `build:ssr`~~ — resuelto: setup compila SSR, `composer ssr`/`ssr:check` y config env-driven.
12. ~~Garantizar singleton de `platform_settings`~~ — resuelto con `insertOrIgnore` + test.
13. ~~Añadir comando de cobertura PHP~~ — `composer test:coverage` (requiere
    Xdebug/PCOV); job de CI y smoke E2E siguen pendientes.

### P3 — Antes de producción (fase 8)

14. ~~Documentar checklist de producción~~ — `docs/development/PRODUCTION.md`;
    `composer setup` incluye `storage:link`; variables de producción documentadas.
    Pendiente: ejecutar el checklist en staging/producción.
15. Pendiente: revisar exposición de los advisories de Symfony con el proxy real
    de producción y completar backups/observabilidad/HTTPS.

---

## 8. Anexos

### A. Comandos ejecutados y salida resumida

```text
$ php artisan --version
Laravel Framework 13.39.0

$ php artisan test
{"tool":"phpunit","result":"passed","tests":266,"passed":266,"assertions":1462,"duration_ms":7900}

$ vendor/bin/pint --test
{"tool":"pint","result":"passed"}

$ vendor/bin/phpstan analyse --no-progress
{"tool":"phpstan","result":"passed","errors":0}

$ npm run types:check
# vue-tsc --noEmit, sin errores

$ npm run build
✓ built in 5.95s

$ npm run check
pass: All 144 files are correctly formatted
pass: Found no warnings or lint errors in 109 files

$ composer audit
No security vulnerability advisories found.

$ npm audit --omit=dev
# 0 vulnerabilities

$ composer ci:check
# PASS (Pint, PHPStan, types, PHPUnit)

$ php artisan route:list --except-vendor
# 71 routes
```

### B. Inventario de tests

- **Total:** 266 tests, 1,462 aserciones.
- **Por área:** `tests/` 109; `Modules/Billing` 22; `Modules/Bookings` 63; `Modules/Listings` 25; `Modules/Platform` 47.
- **Localización:** `tests/Unit/LocalizationTest.php` verifica mensajes de validación en español.
- **Singleton:** `Modules/Platform/tests/Feature/PlatformSettingTest.php` verifica la fila única de `platform_settings`.
- **Feature tests con `RefreshDatabase`:** 42 archivos.
- **Tests con aserciones Inertia:** 17 archivos.
- **Cobertura temática:** auth/2FA/passkeys, teams/invitaciones, branding, listings/media/public catalog, disponibilidad/inquiries/clientes/depósitos, billing/entitlements/webhooks, moderación/reportes, comandos de superadmin.

### C. Rutas principales

- Públicas: `/`, `/terrazas`, `/terrazas/{slug}`, `/terrazas/{slug}/disponibilidad`, `/terrazas/{slug}/solicitud`, `/terrazas/{slug}/reportar`.
- Cuenta: `/{current_team}/dashboard`, `/listings`, `/bookings`, `/billing`.
- Settings: `/settings/profile`, `/settings/security`, `/settings/teams`.
- Plataforma: `/platform`, `/platform/accounts`, `/platform/listings`, `/platform/reports`, `/platform/subscriptions`, `/platform/branding`.
- Auth/Stripe: rutas de Fortify + `stripe/webhook`, `stripe/payment/{id}`.

### D. Métricas del repositorio

- Archivos versionados: 593.
- Migraciones: 29.
- Módulos de negocio: 4 (`Platform`, `Listings`, `Bookings`, `Billing`).
- LOC backend: ~19,600 (app + módulos, incluyendo tests de módulos).
- LOC frontend compartido: ~28,700 (incluye starter kit/shadcn).
- PHPStan: nivel 7, sin baseline.
- TypeScript: `strict: true`.

---

## 9. Conclusión

El proyecto refleja un trabajo ordenado y orientado a calidad: fases cerradas con evidencia, tests abundantes, aislamiento por cuenta cuidado y una arquitectura modular coherente. El “cómo trabajábamos” fue, en la práctica, **trunk-based con cierre documentado por fase**, aunque la documentación describa ramas que no se usaron.

Antes de seguir, hay que **poner el CI en verde** (formato de 27 archivos) y **actualizar Symfony** (10 advisories). Después conviene cerrar la brecha de documentación/onboarding y los huecos de CI. Con eso resuelto, el proyecto está en buena posición para afrontar la fase 8 (producción) sin arrastrar deuda crítica.

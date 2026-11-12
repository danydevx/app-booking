# Booking MVP RC1 — Auditoría inicial

**Fecha:** 2026-11-12
**Rama:** `main`
**HEAD inicial:** `2f93c3f574876143bc0207421cbaeaf1e8ed3538`
**Estado:** auditoría previa a consolidación

## 1. Git Safety Gate

Comandos ejecutados antes de tocar archivos:

```bash
pwd
git rev-parse --show-toplevel
git branch --show-current
git rev-parse HEAD
git status --short
git diff --stat
git diff --cached --stat
git ls-files --others --exclude-standard
```

Resultado:

- Repositorio: `/desarrollo/apps/laravel/app-booking`
- Rama: `main`
- HEAD: `2f93c3f574876143bc0207421cbaeaf1e8ed3538`
- Cambios staged: **0**
- Archivos rastreados modificados: **76**
- Archivos eliminados: **2** (incluidos en los 76)
- Archivos no rastreados: **93**
- Total de rutas únicas afectadas: **169**

El reporte previo hablaba de 154 cambios; la diferencia se debe a que
`git status` colapsa directorios completos en una sola línea. El conteo real por
archivos es 169.

No se encontraron:

- `.env` reales ni secretos en el diff.
- Claves de Stripe, tokens o credenciales.
- Archivos de build o caché no ignorados.
- Cambios staged listos para commit.

## 2. Inventario por dominio

| Dominio                 | Archivos |
| ----------------------- | -------: |
| Bookings                |       45 |
| Listings                |       62 |
| Platform                |       22 |
| Shared frontend         |       16 |
| Public assets           |        9 |
| Config y raíz           |        8 |
| Seeders                 |        2 |
| Documentación           |        2 |
| Owners                  |        1 |
| Tests de nivel superior |        2 |
| **Total**               |  **169** |

### Bookings

Incluye:

- Contacto público y campos de fecha aproximada/rango de invitados.
- Cotizaciones PDF, conceptos, cálculos, folios, snapshots, PDF y tests.
- Rutas, controladores, modelos, presentadores y notificaciones.

### Listings

Incluye:

- Tipos de evento, características destacadas y catálogo público.
- Logo independiente de la galería.
- Miniaturas WebP y edición de texto alternativo.
- Búsqueda guiada, filtros públicos y páginas públicas.
- Integración con cobertura geográfica.

### Platform

Incluye:

- Catálogo geográfico de México.
- Cobertura por estado/municipio.
- Administración de catálogos y cobertura.
- Permisos de plataforma.
- Datos y seeders geográficos.

### Shared frontend

Incluye:

- Búsqueda pública y filtros.
- Componentes del landing de propietarios.
- Navegación de plataforma.
- Estilos de la home pública.

### Public assets

- Imágenes del hero y pasos del landing de propietarios.
- Placeholders `.gitkeep` para video y capturas de producto.

## 3. Clasificación y dependencias

### Grupo A — Platform + cobertura geográfica

Dependencias:

- `GeographicCoverageService` es consumido por `Listings` para visibilidad,
  validación y búsqueda.
- Las migraciones geográficas deben existir antes de publicar terrazas.
- Los permisos nuevos de plataforma deben convivir con `platform:sync-permissions`.

Riesgo: el catálogo geográfico se siembra desde una migración; debe ser
idempotente y no borrar cobertura configurada. El seeder revisado respeta esa
regla.

### Grupo B — Listings

Dependencias:

- `ListingPublicCatalog` depende de `listing_event_types` y
  `listing_characteristics`.
- `SaveListingRequest`, `ListingFormData` y `PublishListing` dependen de
  `GeographicCoverageService`.
- Las miniaturas dependen de `intervention/image`.
- La búsqueda pública depende de los slugs de tipos de evento y del catálogo de
  características.

### Grupo C — Contacto y solicitudes

Dependencias:

- `PublicListingContactController` usa `CreateBookingInquiry`.
- `BookingInquiry` requiere la migración de `date_mode`, `event_month` y
  `guest_range`.
- `PublicListingController` publica la configuración de contacto y la URL de
  privacidad.
- La vista pública de terraza usa `ListingContactModal`.

### Grupo D — Cotizaciones PDF

Dependencias:

- `dompdf/dompdf` en `composer.json` / `composer.lock`.
- `customer_quote_items`, `customer_quote_status_histories` y
  `quote_sequences`.
- `QuotationBuilderController`, `SaveQuotation`, `IssueQuotation`,
  `QuotationPdfRenderer` y el editor `Builder.vue`.
- La migración de cotizaciones ya fue corregida para MySQL y aplicada localmente.

### Grupo E — Landing de propietarios y marketing

Dependencias:

- `OwnersController` expone planes y video opcional.
- Los assets de `public/images/owners` son usados por `Owners.vue`.
- El video y las capturas de plataforma son opcionales: la UI usa placeholders
  cuando no existen.

### Grupo F — Documentación

- `README.md` y `docs/design/Guidelines.md`.
- ADR de cotizaciones pendiente de renumerar.
- `PHASE-STATUS.md` con métricas desactualizadas.

## 4. Riesgos detectados

### Alto

1. **169 archivos sin commit.** Es el mayor riesgo de pérdida o mezcla de
   trabajo. Deben consolidarse en commits pequeños y revisables.
2. **Dependencia entre Platform, Listings y Bookings.** Separar commits de forma
   artificial puede dejar un commit intermedio no ejecutable. La secuencia debe
   respetar las dependencias descritas arriba.
3. **Migraciones ya aplicadas localmente.** La base local está al día, pero
   staging/producción requerirán `php artisan migrate --force`. No se debe asumir
   que el despliegue las ejecutará solo.

### Medio

4. **ADR duplicado:** existen `0002-data-platform.md` y
   `0002-commercial-quotations.md`.
5. **Documentación desactualizada:** `PHASE-STATUS.md` reporta 430 tests; el
   estado actual reportado es 500.
6. **Assets opcionales del landing:** el video demo y las capturas de producto
   no están incluidos; la UI debe mostrar placeholders.
7. **Permisos de storage locales:** el directorio privado de PDFs puede haber
   sido creado por el usuario web con permisos restrictivos. No afecta a Git,
   pero debe revisarse en el entorno de ejecución.

### Bajo

8. El formulario rápido legado de cotizaciones sigue visible en la ficha del
   cliente, aunque el flujo principal ya es el editor nuevo.
9. `FeatureShowcaseSlider.vue` conserva una animación simplificada; no es un
   cambio funcional.
10. Los seeders de demo incluyen datos ficticios de Jalisco. No deben ejecutarse
    en producción.

## 5. Cambios sospechosos o ajenos

No se identificaron cambios ajenos al producto. Todos los archivos se pueden
asociar a:

- cobertura y catálogos geográficos;
- catálogo de terrazas, logos, media y búsqueda;
- contacto público y solicitudes;
- cotizaciones PDF;
- landing de propietarios y assets;
- documentación y pruebas.

No se encontraron archivos de experimentación, dumps, `.env`, claves ni
artefactos de build.

## 6. Propuesta de commits

Se propone la siguiente secuencia, respetando dependencias:

1. **Platform: cobertura geográfica y catálogos**
    - Módulo Platform completo.
    - Migraciones, datos geográficos, permisos, páginas y tests.
    - Eliminación del catálogo hardcodeado anterior cuando Listings ya use el
      servicio nuevo.

2. **Listings: catálogo, logos, media y búsqueda**
    - Tipos de evento, características, logos, miniaturas y texto alternativo.
    - Búsqueda guiada, filtros y páginas públicas.
    - Integración con cobertura geográfica.

3. **Bookings: contacto público y solicitudes**
    - Formulario de contacto, fecha aproximada, rango de invitados y deduplicación.
    - Notificaciones y presentadores.
    - Migración de campos de solicitud.

4. **Bookings: cotizaciones comerciales en PDF**
    - Editor universal, cálculos, folios, PDF privado, estados e inmutabilidad.
    - Dependencias `dompdf` e `intervention/image`.
    - Tests de cotizaciones.

5. **Owners: landing y assets de marketing**
    - Landing de propietarios, video opcional, hero, pasos y componentes visuales.
    - Assets públicos.

6. **Docs: estado, ADR y checklist RC1**
    - Renumeración del ADR de cotizaciones.
    - Actualización de `PHASE-STATUS.md`.
    - Auditoría, checklist y reporte final.

## 7. Pruebas necesarias

- `composer test` completo (SQLite).
- `npm run check`.
- `npx vue-tsc --noEmit`.
- `npm run build`.
- Smoke test `FullFlowSmokeTest`.
- Pruebas específicas de:
    - cobertura geográfica;
    - catálogo de tipos de evento y características;
    - logo de terraza;
    - media y miniaturas;
    - contacto público;
    - cotizaciones PDF y editor universal;
    - entitlements Pro;
    - aislamiento multi-tenant.
- Verificación manual del PDF generado y del landing de propietarios.
- Migraciones y `migrate:status` en MySQL local.

## 8. Decisión

Se preservan todos los cambios. No se descarta ningún archivo. La consolidación
se hará mediante commits locales selectivos, sin push, sin tags y sin despliegue.

**Estado de la auditoría: COMPLETADA.**

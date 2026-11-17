# Booking MVP RC1 — Reporte final

**Fecha:** 2026-11-12
**Estado general:** **PARTIAL**
**Release Candidate:** **RC1 BLOCKED por advisories de dependencias**
**Producción:** **PRODUCTION: NO-GO**

> **Addendum 2026-11-13:** los advisories de `swiper`, `zod` y
> `@vee-validate/zod` fueron resueltos en
> `docs/BOOKING-RC1-DEPENDENCIES-SECURITY-FIX-REPORT.md`. También se actualizó
> Laravel por un advisory publicado el 2026-11-13. El estado histórico de este
> reporte se conserva; el estado RC1 vigente es **READY FOR STAGING**.
>
> **Addendum 2026-11-17:** apareció un advisory nuevo de `picomatch`
> (GHSA-fc5q-39xc-vjpf / CVE-2026-112366) sin versión corregida. Afecta solo al
> tooling de build; se movieron `@inertiajs/vite` y `laravel-vite-plugin` a
> `devDependencies` para que el gate de producción refleje el runtime real.
> `npm audit --omit=dev` vuelve a PASS con 0 vulnerabilidades de producción.
> El árbol completo conserva 17 alertas altas de build, documentadas como riesgo
> residual. El estado RC1 sigue en **READY FOR STAGING** bajo el gate definido.

## A. Resumen

La consolidación del trabajo local se completó. Los cambios funcionales están
separados en commits lógicos, la suite completa pasa en SQLite y MySQL, el build
frontend pasa y el smoke test principal pasa.

El único gate que impide declarar RC1 listo sin reservas es la auditoría de
dependencias JavaScript:

- `swiper` — severidad **critical**, prototype pollution; requiere actualización
  mayor a 14.4.0.
- `zod` — severidad **high**, agotamiento de memoria con colecciones
  restringidas; no hay versión parcheada compatible con la versión actual de
  `@vee-validate/zod`.
- `@vee-validate/zod` — arrastra la vulnerabilidad de `zod`.

`composer audit` pasa sin vulnerabilidades. `npm audit --omit=dev` reporta
3 vulnerabilidades (1 crítica y 2 altas). No se aplicó una actualización mayor
durante RC1 porque requiere una migración específica y no debe mezclarse con la
consolidación.

## B. Git Safety

- Rama: `main`
- HEAD inicial: `2f93c3f574876143bc0207421cbaeaf1e8ed3538`
- Cambios staged iniciales: 0
- Archivos rastreados modificados inicialmente: 76 (incluye 2 eliminaciones)
- Archivos no rastreados inicialmente: 93
- Total inicial por archivo: 169
- El reporte previo de 154 correspondía a entradas de `git status`, no a
  archivos únicos.

Todos los cambios preexistentes fueron preservados. No se usó `git add .`,
`git reset --hard`, `git clean`, `git stash` ni `git rebase`. No se hizo push.

## C. Commits

| Hash                    | Mensaje                                                           | Alcance                                             | Archivos | Validación             |
| ----------------------- | ----------------------------------------------------------------- | --------------------------------------------------- | -------: | ---------------------- |
| `77276e4`               | `feat(listings): catálogos, logos, media y cobertura geográfica`  | Platform, catálogos, geo, Listings admin/media/logo |       65 | Build y tests finales  |
| `1e0a5ad`               | `feat(bookings): búsqueda guiada, contacto público y solicitudes` | Búsqueda pública, contacto, inquiries               |       47 | Build y tests finales  |
| `cb384bf`               | `feat(bookings): cotizaciones comerciales con PDF`                | Cotizador, cálculos, PDF, folios                    |       32 | Tests de cotizaciones  |
| `f1c416c`               | `feat(public): landing de propietarios y showcase visual`         | Landing, assets y componentes visuales              |       20 | Test de landing        |
| commit de documentación | `docs: consolidar estado RC1 y ADR`                               | Auditoría, checklist, ADR, estado y reporte         |        9 | Documentación revisada |

El commit de documentación es el HEAD final de esta intervención. Su hash se
puede obtener con `git log -1`.

No se creó tag, release remoto ni despliegue.

## D. Calidad

### SQLite

```text
composer ci:check
Pint PASS
PHPStan PASS (0 errores)
Vue TypeScript PASS
PHPUnit PASS: 500 tests / 4,468 assertions
```

### MySQL 8.4

Se creó una base local desechable `app_booking_rc1_test` y se ejecutó la suite
completa:

```text
DB_CONNECTION=mysql ... php artisan test --compact
PASS: 500 tests / 4,468 assertions
```

### Frontend

```text
npm run check        PASS
vue-tsc --noEmit     PASS
npm run build        PASS
```

### Smoke test

```text
php artisan test tests/Feature/FullFlowSmokeTest.php
PASS: 1 test / 94 assertions
```

Flujo cubierto:

Landing → catálogo → ficha → solicitud → CRM → cotización → contrato → portal
del cliente → reserva → opinión → reportes.

### Cotizaciones y logo

- Tests de cotizaciones: PASS.
- Tests de logo de terraza: PASS.
- PDF generado manualmente: 2 páginas.
- Folio: `COT-000001`.
- Total de ejemplo: `$22,811.40 MXN`.
- PDF y captura:
    - `/tmp/opencode/cotizacion-los-olivos.pdf`
    - `/tmp/opencode/rc1-quote-1.png`

### Auditoría de dependencias

```text
composer audit          PASS — sin vulnerabilidades
npm audit --omit=dev    FAIL — 3 vulnerabilidades
```

Detalle:

- `swiper@11.2.10` — critical — prototype pollution — fix disponible en 14.4.0
  (major).
- `zod@3.25.76` — high — memoria con colecciones restringidas — sin fix
  compatible.
- `@vee-validate/zod@4.15.1` — high — depende de `zod` vulnerable.

### Producción preflight

`php artisan app:production-check --json` falla en local, como se espera, por:

- `APP_ENV=local`
- `APP_DEBUG=true`
- `APP_URL` sin HTTPS
- `SESSION_SECURE_COOKIE` sin configurar

No se ejecutó ningún preflight contra staging o producción.

## E. Documentación

Archivos actualizados:

- `docs/BOOKING-MVP-RC1-AUDIT.md`
- `docs/BOOKING-MVP-RC1-RELEASE-CHECKLIST.md`
- `docs/BOOKING-MVP-RC1-REPORT.md`
- `docs/decisions/0005-commercial-quotations.md` (renumerado desde el antiguo
  `0002-commercial-quotations.md`)
- `docs/decisions/README.md`
- `docs/design/Guidelines.md`
- `docs/development/PHASE-STATUS.md`
- `README.md`

Se corrigió la numeración duplicada de ADR. El ADR de cotizaciones ahora es
`0005`.

## F. Riesgos

### Bloqueante

1. **Advisories npm:** `swiper` crítica y `zod` alta. El CI que ejecuta
   `npm audit --omit=dev` fallará. Se necesita una decisión y una migración
   separada de dependencias.

### Altos

2. **Fase 8 incompleta:** no hay staging, dominio, backups, correo real,
   observabilidad ni Stripe live validado.
3. **Sin Playwright/E2E real:** el smoke test actual es de backend/Inertia. Falta
   validación en navegador real, móvil y accesibilidad.
4. **Textos legales pendientes:** términos, privacidad, cookies, reembolsos y
   retención.

### Medios

5. **Assets de marketing opcionales:** el video demo y las capturas de producto
   del landing no están incluidos; la UI usa placeholders.
6. **Storage local de PDFs:** el directorio privado puede tener permisos
   distintos entre CLI y usuario web. Debe revisarse en staging.
7. **Formulario rápido legado de cotizaciones:** sigue visible en la ficha del
   cliente; no bloquea, pero conviene retirarlo en una limpieza posterior.

### Bajos

8. Los seeders de demo de Jalisco contienen datos ficticios y no deben ejecutarse
   en producción.
9. La documentación de fases mantiene métricas históricas por fase; el estado
   global ya refleja 500 tests.

## G. Release Candidate

**RC1 BLOCKED** para declararlo listo sin reservas.

Motivo único de bloqueo: advisories de dependencias JavaScript con severidad
crítica/alta y sin solución no disruptiva aplicada.

Todo lo demás está listo para staging:

- cambios consolidados y preservados;
- suite SQLite y MySQL en verde;
- frontend compilado y tipado;
- smoke test principal en verde;
- cotizador y logo verificados;
- documentación actualizada.

Staging puede prepararse solo con una aceptación explícita del riesgo de
dependencias y un plan de remediación antes de producción.

## H. Producción

**PRODUCTION: NO-GO**

La autorización para producción es independiente de este RC1. Antes de producción
deben cerrarse, como mínimo:

- advisories npm;
- Fase 8 completa;
- Stripe live validado en staging;
- backups y restauración probados;
- textos legales publicados;
- Playwright/E2E y smoke test de staging;
- autorización explícita del responsable.

# Booking RC1 — Corrección de dependencias de seguridad

**Fecha:** 2026-11-13
**Estado:** PASS
**Release Candidate:** RC1 READY FOR STAGING
**Producción:** PRODUCTION: NO-GO

## 1. Baseline

- Rama: `main`
- HEAD inicial: `858a45ad0c1d38d3a24c100ad77bb8377ea0e4a2`
- Árbol inicial: limpio
- Node: `24.22.2`
- npm: `11.19.1`
- PHP: 8.3.6
- Laravel: 13.39.0
- SQLite: 500 tests / 4,468 assertions
- MySQL 8.4: 500 tests / 4,468 assertions

Auditoría inicial:

```text
composer audit          PASS
npm audit --omit=dev    FAIL — 3 vulnerabilidades
```

Advisories JavaScript:

| Paquete             | Versión | Severidad | Advisory                                     |
| ------------------- | ------- | --------- | -------------------------------------------- |
| `swiper`            | 11.2.10 | Critical  | GHSA-hmx5-qpq5-p643 — prototype pollution    |
| `zod`               | 3.25.76 | High      | GHSA-mrhx-c7xc-m3w5 — agotamiento de memoria |
| `@vee-validate/zod` | 4.15.1  | High      | Arrastra `zod`                               |

Durante la intervención apareció además un advisory nuevo de Laravel:

| Paquete             | Versión | Severidad | Advisory                              |
| ------------------- | ------- | --------- | ------------------------------------- |
| `laravel/framework` | 13.39.0 | Low       | GHSA-63hm-g96r-2m2q / CVE-2026-128083 |

## 2. Versiones finales

| Paquete                 | Antes        | Después       | Motivo                                                                                                                          |
| ----------------------- | ------------ | ------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `swiper`                | 11.2.10      | 12.2.0        | 12.1.2 es la primera versión fuera del rango vulnerable; 12.2.0 es la última estable de la serie 12 y evita el salto mayor a 14 |
| `zod`                   | 3.25.76      | 4.6.7         | Primera versión corregida del advisory de memoria                                                                               |
| `@vee-validate/zod`     | 4.15.1       | Eliminado     | Su peer dependency sólo admite Zod 3 y usa APIs eliminadas en Zod 4                                                             |
| `laravel/framework`     | 13.39.0      | 13.42.0       | Advisory de seguridad publicado el 2026-11-13                                                                                   |
| `guzzlehttp/guzzle`     | 8.3.1        | 8.5.0         | Actualización transitiva de Laravel                                                                                             |
| `laravel/prompts`       | 0.3.26       | 0.3.27        | Actualización transitiva de Laravel                                                                                             |
| `doctrine/deprecations` | 1.1.6        | 1.2.0         | Actualización transitiva de Laravel                                                                                             |
| `symfony/*`             | 7.4.22/7.9.1 | 7.4.23/7.10.0 | Actualización transitiva compatible de Laravel                                                                                  |

No se actualizaron dependencias ajenas a los advisories ni se cambiaron paquetes de UI.

## 3. Swiper

Se actualizó de `11.2.10` a `12.2.0`.

- El import `swiper/vue`, `swiper/modules` y `swiper/css` sigue siendo válido.
- No fue necesario reescribir componentes.
- `HeroImageSlider` y `FeatureShowcaseSlider` conservaron módulos, navegación,
  paginación, autoplay, breakpoints y transiciones.
- Se verificó el carrusel del landing de propietarios en navegador real.
- Se verificó la navegación por paginación y el layout móvil.

No se migró a Swiper 14 porque 12.2.0 ya está fuera del rango vulnerable y reduce
el riesgo de una migración mayor innecesaria.

## 4. Zod y vee-validate

`@vee-validate/zod@4.15.1` no es compatible con Zod 4: su peer dependency exige
`zod ^3.24.0` y su implementación importa `ZodFirstPartyTypeKind`, API que Zod 4
eliminó.

Se eliminó el adaptador y se añadió un puente mínimo:

`resources/js/composables/zodTypedSchema.ts`

- Implementa el contrato público `TypedSchema` de vee-validate.
- Ejecuta `safeParseAsync` sobre el esquema Zod existente.
- Agrupa issues por ruta compatible con vee-validate.
- Conserva `cast` para valores iniciales.
- No reemplaza reglas ni crea un sistema de validación nuevo.
- Las validaciones server-side de Laravel permanecen intactas.

Cambios de API de Zod 4 aplicados:

- `z.record(z.string())` → `z.record(z.string(), z.string())`.
- `z.record(schema)` → `z.record(z.string(), schema)`.
- Tipos de `interval` en planes ajustados a `'month' | 'year'`.

Formularios afectados y verificados por TypeScript/build:

- `ListingForm.vue`
- `ListingContactModal.vue`
- `Pricing.vue`
- `Services/Index.vue`
- `Packages/Index.vue`
- `Promotions/Index.vue`
- `InquiryForm.vue`
- `Bookings/Create.vue`
- `Platform/Plans/Index.vue`

## 5. QA en navegador

No hay Playwright instalado. Se utilizó Chrome headless ya disponible mediante
Chrome DevTools Protocol, sin instalar navegadores adicionales.

Resultados:

- Carrusel de propietarios inicializado (`swiper-initialized`).
- 3 slides y paginación presentes.
- Clic en paginación cambió el slide activo de índice 0 a 1.
- Layout móvil a 390 px renderizado correctamente.
- Sin errores de consola.
- Formulario público de solicitud mostró errores de validación para nombre,
  correo, teléfono, fecha y consentimiento al enviar vacío.
- Con datos válidos, la validación frontend pasó y se intentó la petición
  (interceptada en QA para no crear datos).
- La URL no cambió durante la validación inválida.

Evidencia:

- `/tmp/opencode/owners-rc1.png`
- `/tmp/opencode/owners-rc1-mobile.png`
- `/tmp/opencode/owners-dom.html`

## 6. Pruebas y gates

| Comando                | Resultado                                          |
| ---------------------- | -------------------------------------------------- |
| `npm ci`               | PASS                                               |
| `npm ls`               | PASS, sin conflictos                               |
| `npm audit --omit=dev` | PASS — 0 vulnerabilities                           |
| `composer audit`       | PASS — sin advisories                              |
| `npm run check`        | PASS                                               |
| `vue-tsc --noEmit`     | PASS                                               |
| `npm run build`        | PASS                                               |
| `composer ci:check`    | PASS                                               |
| Pint                   | PASS                                               |
| PHPStan                | PASS, 0 errores                                    |
| PHPUnit SQLite         | PASS — 500 tests / 4,468 assertions                |
| PHPUnit MySQL 8.4      | PASS — 500 tests / 4,468 assertions                |
| `FullFlowSmokeTest`    | PASS — 1 test / 94 assertions                      |
| QA visual Chrome/CDP   | PASS en carrusel, móvil y validación de formulario |

No se eliminaron tests ni assertions.

## 7. Riesgos residuales

- No existe Playwright ni suite E2E versionada; la QA visual se hizo con Chrome
  headless disponible en la máquina, por lo que no es reproducible en CI todavía.
- El puente Zod/vee-validate es deliberadamente mínimo; debe mantenerse si se
  actualiza vee-validate o Zod en el futuro.
- Los advisories pueden cambiar con nuevas publicaciones del registro npm. El
  gate `npm audit --omit=dev` debe seguir ejecutándose en CI.
- Staging y producción siguen pendientes de Fase 8: infraestructura, backups,
  correo real, Stripe live, legal y observabilidad.
- `PRODUCTION: NO-GO` se mantiene.

## 8. Commits

| Hash                    | Mensaje                                                             | Alcance                                      |
| ----------------------- | ------------------------------------------------------------------- | -------------------------------------------- |
| `dd4c6b3`               | `fix(security): actualizar swiper y validación a versiones seguras` | Swiper, Zod, adaptador y ajustes de esquemas |
| `80514de`               | `fix(security): actualizar Laravel por advisory de seguridad`       | Laravel y dependencias transitivas           |
| commit de documentación | `docs: registrar cierre de advisories rc1`                          | Reporte y estado RC1                         |

No se hizo push, tag ni despliegue.

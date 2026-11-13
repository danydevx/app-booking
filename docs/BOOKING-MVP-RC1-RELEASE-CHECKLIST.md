# Booking MVP RC1 — Checklist de lanzamiento

**Fecha:** 2026-11-12
**Estado:** RC1 local para staging; **no autoriza producción**
**Alcance:** controles pendientes de Fase 8 y verificación previa a staging

## 1. Infraestructura

- [ ] Elegir proveedor de hosting y dominio definitivo.
- [ ] Provisionar entorno de staging aislado.
- [ ] Configurar HTTPS/TLS.
- [ ] Configurar `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] Configurar `SESSION_SECURE_COOKIE=true` y `SESSION_SAME_SITE`.
- [ ] Crear MySQL 8.4 de staging.
- [ ] Configurar storage persistente u object storage para media y logos.
- [ ] Configurar disco privado persistente para PDFs de cotizaciones.
- [ ] Configurar proveedor SMTP/transaccional con SPF, DKIM y DMARC.
- [ ] Configurar worker de colas supervisado.
- [ ] Configurar scheduler por cron.
- [ ] Configurar logs persistentes y rotación.
- [ ] Configurar alertas de errores, cola y caídas.
- [ ] Configurar uptime monitoring sobre `/up`.
- [ ] Definir y probar backups de MySQL.
- [ ] Definir y probar backups de storage/media/PDFs.
- [ ] Ejecutar una restauración real en entorno descartable.
- [ ] Documentar procedimiento de rollback y release anterior.
- [ ] Ejecutar `composer production:check` en el entorno objetivo.
- [ ] Ejecutar `php artisan migrate --force` de forma controlada.
- [ ] Ejecutar `php artisan platform:sync-permissions`.
- [ ] Crear el primer superadmin verificado.
- [ ] Verificar `storage:link`.
- [ ] Reiniciar workers tras el despliegue.
- [ ] Ejecutar smoke test en staging.

## 2. Stripe

- [ ] Confirmar precio Pro y moneda MXN.
- [ ] Configurar claves de test en staging.
- [ ] Configurar webhook accesible con firma válida.
- [ ] Verificar checkout exitoso.
- [ ] Verificar pago fallido y grace period.
- [ ] Verificar cancelación y reactivación.
- [ ] Verificar Customer Portal y URLs de retorno.
- [ ] Verificar entitlements después de cancelar/expirar.
- [ ] Verificar que perder Pro no elimina cotizaciones ni PDFs.
- [ ] Revisar tratamiento de impuestos de la suscripción.
- [ ] Repetir el flujo con claves live solo tras aprobación explícita.

## 3. Legal

- [ ] Redactar términos y condiciones.
- [ ] Redactar aviso de privacidad.
- [ ] Definir política de cookies, si aplica.
- [ ] Definir política de cancelaciones.
- [ ] Definir política de reembolsos.
- [ ] Definir retención y eliminación de datos.
- [ ] Publicar las URLs legales en branding de plataforma.
- [ ] Revisar consentimiento de solicitudes y correo.

## 4. QA

- [ ] Instalar Playwright o equivalente para E2E.
- [ ] Smoke E2E: landing → búsqueda → ficha → contacto/solicitud.
- [ ] Smoke E2E: CRM → cotización → PDF → contrato → portal.
- [ ] Probar en Chrome, Firefox y Safari.
- [ ] Probar en móvil y tablet.
- [ ] Revisar accesibilidad básica y navegación por teclado.
- [ ] Verificar PDF real con logo, conceptos, totales y multipágina.
- [ ] Verificar correo real y entregabilidad.
- [ ] Verificar enlaces del landing de propietarios y video opcional.
- [ ] Prueba de carga básica del catálogo y búsqueda.
- [ ] Probar backup y restore.
- [ ] Revisar logs de errores después del smoke test.

## 5. Seguridad

- [ ] Verificar aislamiento multi-tenant con dos cuentas.
- [ ] Verificar descarga de PDF entre cuentas.
- [ ] Verificar que un Free no accede a CRM/cotizaciones.
- [ ] Verificar formularios públicos con rate limiting.
- [ ] Verificar CSRF en formularios internos.
- [ ] Verificar webhook Stripe con firma.
- [ ] Verificar uploads por MIME, tamaño y dimensiones.
- [ ] Verificar que storage privado no está expuesto públicamente.
- [ ] Verificar que `.env`, logs, backups y dumps no son públicos.
- [ ] Ejecutar `composer audit` y `npm audit --omit=dev`.
    - Resultado local (2026-11-13): `composer audit` PASS y `npm audit` PASS con
      0 vulnerabilidades. Ver
      `docs/BOOKING-RC1-DEPENDENCIES-SECURITY-FIX-REPORT.md`.
- [ ] Revisar privilegios mínimos de la base de datos.

## 6. Criterio de cierre

La Fase 8 no se considera cerrada hasta que:

- todos los controles de infraestructura estén verificados;
- Stripe test haya pasado el flujo completo;
- exista backup/restore probado;
- los textos legales estén publicados;
- el smoke test de staging pase;
- exista autorización explícita para producción.

**PRODUCTION: NO-GO** hasta completar este checklist y recibir autorización.

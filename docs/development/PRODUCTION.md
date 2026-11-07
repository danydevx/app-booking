# Producción: staging, despliegue y operación

## Estado

- **Fase 8: PARTIAL.** Checklist y requisitos documentados; **no se ha desplegado**
  a staging ni producción.
- El despliegue requiere un entorno objetivo y autorización explícita.
- CI actual: SQLite + MySQL 8.4, Pint, PHPStan, tipos, build y auditorías.
- Cobertura: `composer test:coverage`; requiere Xdebug/PCOV (no instalado
  localmente). CI no ejecuta cobertura por defecto.
- E2E: Playwright aún no instalado.

## Entornos

| Entorno           | Uso                             | Datos                                                    |
| ----------------- | ------------------------------- | -------------------------------------------------------- |
| Local             | Desarrollo                      | MySQL local (Docker) o SQLite legacy; Stripe/mail falsos |
| Staging / Sandbox | Integración y release candidate | Base y storage aislados; Stripe test mode                |
| Producción        | Clientes reales                 | Base, storage, mail y Stripe live dedicados              |

Reglas:

- Nunca apuntar staging a datos de producción.
- Nunca copiar `.env`, uploads o dumps de producción a Git.
- Secretos de producción solo en el proveedor/GitHub Environments.
- Desplegar el mismo commit que pasó staging.

## Requisitos del servidor

- PHP 8.3 con extensiones de Laravel (`pdo_mysql`, `mbstring`, `openssl`,
  `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`; `intl` recomendado
  para `php artisan db:show`).
- MySQL 8+ (el proyecto se validó con MySQL 8.4).
- Document root apuntando a `public/`.
- HTTPS/TLS, `APP_ENV=production`, `APP_DEBUG=false`.
- `SESSION_SECURE_COOKIE=true` y `SESSION_SAME_SITE=lax` (o la política que
  corresponda).
- `storage/` y `bootstrap/cache/` escribibles con mínimo privilegio.
- Storage persistente u object storage para media y logos.
- Queue worker gestionado por Supervisor/systemd.
- Scheduler por cron.
- Mail provider con SPF, DKIM y DMARC.
- Backups y prueba de restauración.
- Rotación de logs y alertas de errores.

## Variables de entorno

Además de las de `docs/development/DATABASE.md`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
DB_TIMEZONE=+00:00

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

STRIPE_KEY=...
STRIPE_SECRET=...
STRIPE_WEBHOOK_SECRET=...
STRIPE_PRICE_PRO=...
CASHIER_CURRENCY=mxn
CASHIER_CURRENCY_LOCALE=es_MX
```

No commitear valores reales.

## Despliegue (SSH release, referencia)

1. CI verde en el commit a desplegar.
2. Crear directorio de release en el servidor.
3. Desplegar el mismo commit/artefacto; no editar archivos en el servidor.
4. `composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader`
5. `npm ci && npm run build:ssr` o desplegar el artefacto ya construido.
6. Enlazar `.env` y storage persistente.
7. `php artisan migrate --force` como paso controlado.
8. `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
9. `php artisan storage:link --force`.
10. Reiniciar workers de cola.
11. Cambiar el symlink `current` de forma atómica.
12. Health check y smoke test.

No ejecutar `composer update` en producción. No ejecutar migraciones destructivas.

## Colas y scheduler

```bash
# Worker (Supervisor/systemd)
php artisan queue:work --tries=3 --timeout=90

# Cron cada minuto
* * * * * cd /ruta/al/app && php artisan schedule:run >> /dev/null 2>&1
```

Verificar `/up` y `php artisan queue:monitor` si se añade el paquete de monitorización.

## Backups y restauración

- Dump MySQL programado (`mysqldump` o el backup del proveedor).
- Backup del storage de media/logos.
- Cifrado y control de acceso.
- Probar restaurar en un entorno descartable antes del lanzamiento.
- Documentar el procedimiento de rollback y el commit anterior.

## Stripe live

No activar live hasta comprobar en staging:

- Productos/precios alineados con `config/billing.php`.
- Webhook accesible y con firma válida.
- Pago correcto, pago fallido, cancelación y reactivación.
- Grace period (`past_due_grace_days`).
- Customer Portal y URLs de retorno.
- La app falla cerrada si Stripe no está disponible.

## Smoke test de lanzamiento

1. Registro y verificación de email.
2. Crear y publicar una terraza.
3. Catálogo público, búsqueda por radio y detalle.
4. Enviar inquiry y revisarla en la bandeja del owner.
5. Aceptar reserva y comprobar fechas bloqueadas.
6. Registrar depósito.
7. Checkout/portal en Stripe test mode.
8. Panel de plataforma y moderación.
9. Revisar logs, colas, scheduler, correo y disco.

## Rollback

- Mantener el release anterior y el commit documentado.
- Revertir symlink `current` al release anterior.
- Restaurar base/storage solo si la migración o los datos lo requieren.
- Revisar logs y health checks después del rollback.

## Checklist de seguridad

- Rutas tenant con auth, verified y policies.
- Formularios públicos con rate limit.
- Uploads validados por MIME, extensión y tamaño.
- `.env`, storage, logs y backups no públicos.
- Webhook Stripe con firma válida.
- Cookies de sesión seguras.
- Superadmin no registrable públicamente.
- Cuentas/listings suspendidos fuera de lo público.
- Dependencias auditadas (`composer audit`, `npm audit`).
- Usuario de base de datos con privilegios mínimos.
- Sin Bootstrap/jQuery.

## Autorización

Este documento no autoriza un despliegue. Antes de ejecutar el release se necesita:

- entorno objetivo (host, dominio, base, storage, correo),
- secretos de producción,
- ventana de mantenimiento si aplica,
- aprobación explícita del responsable.

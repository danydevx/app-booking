# App Booking

SaaS multi-cuenta para que propietarios publiquen terrazas, gestionen
disponibilidad, prospectos, clientes y depósitos, y reciban solicitudes de renta.
Los visitantes buscan terrazas publicadas, consultan disponibilidad y envían una
solicitud. Los propietarios pagan una suscripción mensual con Stripe para
desbloquear límites y capacidades. Un superadmin de plataforma revisa cuentas,
suscripciones y contenido publicado.

## Stack

- Laravel 13 + PHP 8.3 + PHPUnit
- Inertia 3 + Vue 3 + TypeScript + Tailwind 4 + shadcn-vue
- MySQL 8.4 como base principal; SQLite queda como legacy/test
- Stripe con Laravel Cashier
- Spatie Permission y Activitylog
- Leaflet + OpenStreetMap

## Requisitos

- PHP 8.3 con `pdo_mysql` y `pdo_sqlite`
- Composer 2
- Node 24 (ver `.nvmrc`)
- MySQL 8.4 local (Docker) o una instancia MySQL/MariaDB accesible

## Inicio rápido con MySQL

```bash
docker run -d --name app-booking-mysql \
  --restart unless-stopped \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_ROOT_HOST='%' \
  -e MYSQL_DATABASE=app_booking \
  -e MYSQL_USER=app_booking \
  -e MYSQL_PASSWORD=secret \
  -p 3307:3306 \
  -v app-booking-mysql-data:/var/lib/mysql \
  mysql:8.4

docker exec app-booking-mysql mysql -uroot -proot -e "
  CREATE DATABASE IF NOT EXISTS app_booking_test
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  GRANT ALL PRIVILEGES ON app_booking_test.* TO 'app_booking'@'%';
  FLUSH PRIVILEGES;
"

cp .env.example .env
composer setup
composer dev
```

`composer setup` instala dependencias PHP/JS, genera la clave, migra la base y
compila assets cliente + SSR. `composer dev` levanta servidor, queue, logs y Vite.

Las credenciales de `.env.example` son solo para desarrollo local.

## Comandos

```bash
composer dev        # servidor + queue + pail + Vite
composer test       # Pint + PHPStan + PHPUnit (SQLite en memoria)
composer test:coverage # PHPUnit con cobertura (requiere Xdebug/PCOV)
composer ci:check   # npm run check + vue-tsc + composer test
composer production:check # preflight de configuración de producción
composer ssr        # servidor Inertia SSR (requiere build:ssr)
composer ssr:check  # health check del SSR

npm run build       # build cliente
npm run build:ssr   # build cliente + SSR
npm run check       # formato + lint + type-aware checks
npm run check:fix   # corrige formato/lint
```

Tests contra MySQL:

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 \
DB_DATABASE=app_booking_test DB_USERNAME=app_booking DB_PASSWORD=secret \
php artisan test
```

## Base de datos

- **MySQL 8.4** es el motor por defecto en local, staging y producción.
- **SQLite** queda como legacy y para la suite rápida de tests.
- El respaldo del SQLite original está en `database/legacy/database.sqlite`.
- Detalle de conexión, Docker, migración y caveats:
  `docs/development/DATABASE.md`.

## Arquitectura

Monolito modular: el código compartido del starter kit vive en `app/` y
`resources/js/`; las capacidades de negocio viven en `Modules/{Name}/`
(`Platform`, `Listings`, `Bookings`, `Billing`). Cada módulo posee su backend,
rutas, migraciones, páginas Vue y tests.

- Estructura de módulos: `docs/development/MODULES.md`
- Decisiones de arquitectura y producto: `docs/decisions/README.md`
- Estado de fases: `docs/development/PHASE-STATUS.md`
- Producción: `docs/development/PRODUCTION.md`
- Seguridad: `SECURITY.md`
- Guía para agentes: `.opencode/skills/app-booking-development/SKILL.md`

## Operación de plataforma

- Crear un superadmin verificado:
    ```bash
    php artisan app:make-platform-superadmin correo@ejemplo.com
    ```
- Revocar acceso:
    ```bash
    php artisan app:revoke-platform-superadmin correo@ejemplo.com
    ```
- Sincronizar el rol y permisos de plataforma tras un cambio:
    ```bash
    php artisan platform:sync-permissions
    ```
- Panel `/platform`: resumen, cuentas, usuarios registrados, terrazas,
  suscripciones, reportes y marca.
- El superadmin puede suspender/restaurar cuentas, terrazas y usuarios, y
  otorgar/revocar el rol de superadmin desde la ficha del usuario; las acciones
  quedan en el activity log y notifican por correo cuando aplica.

## Licencia

MIT. Ver `LICENSE`.

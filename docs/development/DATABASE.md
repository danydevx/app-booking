# Base de datos: MySQL principal, SQLite legacy

## Estado

- **MySQL 8.4** es el motor por defecto en local, staging y producción.
- **SQLite** queda como legacy: respaldo del histórico y suite rápida de tests.
- **Fase 2 aplicada (2026-11-06):** esquema y datos migrados a MySQL.
- Respaldo del SQLite original: `database/legacy/database.sqlite` (ignorado por Git).

## MySQL local con Docker

El servidor local del sistema puede ser MariaDB. Para usar MySQL 8.4 sin
conflictos, se levanta un contenedor en el puerto `3307`:

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

docker exec app-booking-mysql mysqladmin ping -h 127.0.0.1 -uroot -proot
```

Crear la base de tests (la de desarrollo se crea con `MYSQL_DATABASE`):

```bash
docker exec app-booking-mysql mysql -uroot -proot -e "
  CREATE DATABASE IF NOT EXISTS app_booking_test
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  GRANT ALL PRIVILEGES ON app_booking_test.* TO 'app_booking'@'%';
  FLUSH PRIVILEGES;
"
```

Credenciales locales (solo desarrollo):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=app_booking
DB_USERNAME=app_booking
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_TIMEZONE=+00:00
```

Comandos útiles:

```bash
docker start app-booking-mysql
docker stop app-booking-mysql
docker logs app-booking-mysql
```

## SQLite legacy

El archivo original se conserva en `database/legacy/database.sqlite` y se puede
consultar con la conexión `legacy_sqlite` (configurada en `config/database.php`):

```bash
php artisan db:show --database=legacy_sqlite
```

Para volver a usar SQLite en local (solo legacy/desarrollo):

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/ruta/absoluta/a/database/database.sqlite
```

No borrar el respaldo hasta validar MySQL en staging/producción.

## Tests

- La suite por defecto sigue usando SQLite en memoria (`phpunit.xml`): rápida y aislada.
- El job de CI `MySQL 8.4` ejecuta la misma suite contra MySQL real.

Para ejecutar la suite contra MySQL manualmente:

```bash
DB_CONNECTION=mysql \
DB_HOST=127.0.0.1 \
DB_PORT=3307 \
DB_DATABASE=app_booking_test \
DB_USERNAME=root \
DB_PASSWORD=root \
DB_TIMEZONE=+00:00 \
php artisan test
```

Las variables de entorno del proceso tienen prioridad sobre `phpunit.xml`
(PHPUnit no las sobrescribe salvo `force="true"`).

## Migración de datos (referencia)

La migración se hizo con este procedimiento:

1. Copiar `database/database.sqlite` a `database/legacy/database.sqlite`.
2. `php artisan migrate:fresh --force` sobre la base MySQL vacía.
3. Copiar tablas de dominio desde `legacy_sqlite` a MySQL con
   `SET FOREIGN_KEY_CHECKS=0`.
4. Saltar tablas efímeras: `migrations`, `cache`, `cache_locks`, `sessions`,
   `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`.
5. Verificar conteos tabla por tabla y ejecutar smoke tests públicos.

Tablas migradas: `users`, `teams`, `team_members`, `team_invitations`,
`passkeys`, `roles`, `permissions`, pivotes de permisos, `subscriptions`,
`subscription_items`, `customers`, `listings`, `listing_media`,
`booking_inquiries`, `listing_unavailability`, `booking_deposits`,
`listing_reports`, `platform_settings` y `activity_log`.

## CI

`.github/workflows/tests.yml` tiene dos jobs:

- **SQLite**: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.
- **MySQL 8.4**: servicio MySQL en `3307`, base `app_booking_test`, migraciones y
  `composer test`.

## Puntos a vigilar

- **MariaDB vs MySQL**: el servidor local puede ser MariaDB. Para paridad real,
  CI y staging deben usar MySQL 8.4.
- **Colaciones**: `utf8mb4_unicode_ci` puede cambiar búsquedas con acentos
  respecto a SQLite; probar catálogo y ordenamiento de teams.
- **JSON**: `whereJsonContains('services', ...)` requiere MySQL 8 / MariaDB 10.2+.
- **`ONLY_FULL_GROUP_BY` y `strict`**: MySQL es más estricto; la suite lo detecta.
- **Zona horaria**: `DB_TIMEZONE=+00:00` mantiene `timestamp` en UTC.
- **Datos**: `migrate:fresh` destruye la base destino. Hacer backup y usar una
  base de test separada.

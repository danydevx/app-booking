# 0002 — MySQL 8.4 as primary database, SQLite as legacy

**Date:** 2026-11-06
**Status:** Accepted

## Context

The MVP started on SQLite for speed. Staging and production need a database with
concurrent writes, JSON support, strict modes and a standard operational model.
The schema and application queries were reviewed for portability before the
cutover.

## Decision

- **MySQL 8.4** is the primary database for local development, staging and
  production.
- **SQLite** remains supported for the fast test suite and as a read-only legacy
  backup of the pre-migration data.
- The MySQL connection uses `utf8mb4` and `utf8mb4_unicode_ci`.
- The MySQL session timezone is fixed to `+00:00` (`DB_TIMEZONE`); application
  timestamps remain UTC and display uses the team/listing timezone.
- CI runs the backend suite twice: SQLite in memory (fast) and MySQL 8.4
  (dialect/parity).
- Schema changes run through normal Laravel migrations; no production SQLite is
  written after the cutover.
- The pre-migration SQLite file is kept at
  `database/legacy/database.sqlite` (ignored by Git) and exposed through the
  `legacy_sqlite` connection for read-only queries.

## Consequences

- `composer setup` requires a reachable MySQL instance; local setup is documented
  in `docs/development/DATABASE.md`.
- `phpunit.xml` keeps SQLite in memory by default; CI overrides it for MySQL.
- JSON searches (`whereJsonContains`), strict mode and `ONLY_FULL_GROUP_BY` are
  covered by the MySQL CI job.
- Backups must target MySQL plus the media storage, not the old SQLite file.
- The SQLite file is a migration backup, not a second live database.

## Alternatives considered

- **Stay on SQLite:** rejected for production concurrency and operational
  constraints.
- **MariaDB locally:** acceptable for development with `DB_CONNECTION=mariadb`,
  but CI uses MySQL 8.4 for parity.
- **PostgreSQL:** viable technically, but not the target chosen for this product.

# 0006 — Reclamación de terrazas del Directorio

**Date:** 2026-11-17
**Status:** Accepted

## Context

El producto necesita lanzarse con terrazas reales antes de tener propietarios
registrados. Esas terrazas se cargan desde fuentes públicas o acuerdos directos,
pero el control del anuncio debe quedar en manos de su dueño cuando lo solicite.
Compartir credenciales de una cuenta o transferir registros a mano no es
auditable y rompe la separación por cuenta.

## Decision

1. Las terrazas importadas pertenecen a una **cuenta Directorio** sin miembros,
   marcada con `teams.is_directory`.
2. Una terraza es **reclamable** cuando pertenece al Directorio y
   `listings.claimed_at` es nulo.
3. El dueño envía una **reclamación** desde `/terrazas/{slug}/reclamar` con
   cuenta verificada. La solicitud guarda identidad, rol, teléfono, cuenta
   destino y evidencia opcional en `listing_claims`.
4. La reclamación se revisa **manualmente** en `/app/platform/claims`. Aprobar
   transfiere `listings.team_id` a la cuenta del propietario, marca
   `claimed_at`/`claimed_by_user_id` y conserva la URL pública.
5. Las terrazas sin reclamar **no exponen** el contacto importado en la página
   pública. Las solicitudes de visitantes se avisan al correo de soporte y se
   transfieren junto con la terraza al aprobar.
6. Reclamar **no se bloquea** por el límite del plan. El límite sigue aplicando
   solo a la creación de terrazas nuevas.
7. La aprobación y la devolución al Directorio quedan en el log
   `moderation`; un superadmin puede devolver una terraza reclamada para
   corregir errores.

## Implementation

- `Modules/Platform`: modelo `ListingClaim`, cola de revisión, permisos
  `platform.claims.view` y `platform.claims.manage`.
- `Modules/Listings`: columnas de reclamo, `app:directory-team`,
  `listings:import`, `TransferListingOwnership`,
  `ReturnListingToDirectory` y el evento `ListingOwnershipTransferred`.
- `Modules/Bookings`: listener que mueve solicitudes, clientes y registros
  relacionados a la cuenta destino.

## Rejected alternatives

- **`listings.team_id` nullable:** obligaría a cambiar visibilidad pública,
  políticas, reservas, CRM y facturación en todos los módulos.
- **Una cuenta placeholder por terraza:** multiplica cuentas sin usuarios y
  complica la operación.
- **Transferencia manual de filas:** no deja evidencia ni historial de quién
  reclamó y cuándo.

## Consequences

- El Directorio no tiene miembros; su operación se realiza desde el panel de
  plataforma y el correo de soporte.
- Los leads previos al reclamo cambian de `team_id` al aprobar y conservan su
  historial de cliente.
- Los permisos nuevos requieren `php artisan platform:sync-permissions` en cada
  entorno.
- La aprobación es manual; la verificación automática por correo del anuncio
  queda fuera del alcance hasta que existan datos de contacto confiables.

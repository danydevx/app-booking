# Administración de terraza

Cada terraza tiene un centro operativo propio. No reemplaza el formulario de
edición: agrupa el trabajo diario del dueño.

## Acceso

`/{current_team}/listings/{listing}`

También se llega desde **Terrazas → Administrar**.

El menú de gestión aparece como un submenú fijo debajo del breadcrumb y se mantiene visible al navegar entre resumen, reservaciones, clientes, disponibilidad y edición.

Las vistas de administración usan todo el ancho disponible; la distribución se resuelve con columnas y paneles laterales en lugar de limitar el contenedor principal.

## Contenido

- Cabecera con portada, estado, ubicación y medios.
- Acciones rápidas para editar datos, ver la página pública o volver.
- Métricas de la terraza:
    - Solicitudes.
    - Solicitudes nuevas.
    - Reservas aceptadas.
    - Próximas reservas.
    - Clientes vinculados.
    - Fechas bloqueadas en los próximos 30 días.
- Próximas reservas.
- Solicitudes recientes.
- Clientes vinculados a solicitudes de esa terraza.
- Próximas fechas no disponibles.
- Enlaces a:
    - Solicitudes/reservaciones de la terraza en `/{current_team}/listings/{listing}/bookings`.
    - Clientes filtrados por terraza.
    - Disponibilidad.
    - Edición de datos y fotos.

El filtro de clientes por terraza usa las solicitudes asociadas a esa terraza y
respeta el aislamiento por cuenta.

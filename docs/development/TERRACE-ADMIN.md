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
- Clientes vinculados a la terraza por sus solicitudes o porque se registraron/importaron desde esta administración.
- Cotizaciones de la terraza desde la ficha del cliente, con link público y conversión a reserva.
- Correo de seguimiento con plantillas y baja voluntaria.
- Próximas fechas no disponibles.
- Enlaces a:
    - Solicitudes/reservaciones de la terraza en `/{current_team}/listings/{listing}/bookings`.
    - Nueva reserva dentro de la terraza en `/{current_team}/listings/{listing}/bookings/create`.
    - Clientes de la terraza en `/{current_team}/listings/{listing}/customers`.
    - Ficha de cliente dentro de la terraza en `/{current_team}/listings/{listing}/customers/{customer}`.
    - Importación CSV con `?listing={slug}`.
    - Disponibilidad.
    - Edición de datos y fotos.

El listado de clientes por terraza incluye las solicitudes de esa terraza y los
clientes creados o importados directamente desde ella (`customers.listing_id`),
respeta el aislamiento por cuenta y oculta el filtro global de terraza. La
exportación CSV acepta el filtro `listing`.

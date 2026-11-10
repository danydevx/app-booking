# Disponibilidad de terrazas

La disponibilidad es independiente de las reservas. El dueño puede bloquear
fechas por limpieza, mantenimiento o uso personal sin crear una solicitud.

## Acceso

`/app/{current_team}/bookings/availability/{listing}`

Desde **Bookings → Disponibilidad** o desde la tarjeta de cada terraza.

## Bloqueo manual

- Selecciona uno o varios días en el calendario.
- El rango se carga en el formulario lateral.
- El motivo es opcional; se recomienda usar Limpieza, Mantenimiento o Uso
  personal.
- Las fechas pasadas no se pueden bloquear.
- Las fechas reservadas o apartadas no se sobrescriben.
- Liberar un bloqueo manual no afecta reservas.

## Series recurrentes

Soporta:

- Semanal.
- Quincenal.
- Mensual.

Para semanal y quincenal se eligen los días de la semana. Para mensual se usa el
día de inicio como referencia. El horizonte inicial es de 366 días.

Las series generan filas normales en `listing_unavailability`, por lo que el
catálogo público y el buscador no necesitan lógica adicional. Si una fecha ya
está reservada o apartada, se omite y se reporta.

Eliminar una serie libera solo las fechas futuras generadas por esa serie. Las
fechas pasadas se conservan. Liberar una fecha individual genera una excepción
para que no vuelva a crearse al administrar la serie.

## Protecciones

- Una fecha reservada o apartada nunca se modifica desde disponibilidad.
- Las series respetan el `team_id` y no cruzan cuentas.
- Los cambios de series quedan en el activity log con el actor y el resultado.
- El calendario público sigue mostrando únicamente los estados permitidos.

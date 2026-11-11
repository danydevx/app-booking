# Salsa Terrazas — Funcionalidad del MVP

**Última actualización:** 2026-11-10  
**Proyecto interno:** `app-booking`  
**Estado:** MVP funcional en local; lanzamiento a producción pendiente (fase 8 parcial).  
**Stack:** Laravel 13, Inertia 3, Vue 3 + TypeScript, Tailwind 4, shadcn-vue, MySQL 8.4, Stripe Cashier.

---

## 1. Resumen ejecutivo

Salsa Terrazas es un SaaS multi-cuenta para publicar, administrar y rentar terrazas para eventos. Cada cuenta administra sus terrazas, disponibilidad, solicitudes, reservas, clientes, cotizaciones, contratos y comunicación con el cliente.

El MVP cubre el ciclo completo:

1. Un visitante encuentra una terraza y envía una solicitud.
2. El propietario da seguimiento, cotiza y confirma disponibilidad.
3. El cliente acepta la cotización y el contrato desde un portal público.
4. El propietario registra la reserva y el anticipo.
5. El cliente confirma sus datos, agrega el evento al calendario y deja opinión.
6. El propietario consulta embudo, conversión, tiempos de respuesta y opiniones.

La landing pública y la administración están separadas por URL y layout:

- Sitio público: `/`, `/terrazas`, `/reserva/{token}`, `/cotizaciones/{token}`.
- Administración de cuenta: `/app/{team}/...`.
- Administración de plataforma: `/app/platform/...`.
- Ajustes: `/app/settings/...`.

---

## 2. Cuentas, usuarios y permisos

- **Cuenta = Team.** Cada terraza, solicitud, cliente, cotización, contrato y suscripción pertenece a una cuenta.
- **Roles:** Owner, Admin y Member.
- **Invitaciones:** alta de miembros por invitación con expiración.
- **Autenticación:** registro, login, verificación de correo, recuperación de contraseña, 2FA y passkeys mediante Laravel Fortify.
- **Aislamiento multi-cuenta:** las consultas se filtran por `team_id`; políticas y route model binding impiden acceder a datos de otra cuenta.
- **Superadmin de plataforma:** rol separado (`platform_superadmin`), sin bypass global de tenants.
- **Permisos granulares por miembro:** fuera del MVP; se mantienen los tres roles base.

---

## 3. Planes y facturación

| Plan | Límites y capacidades                             |
| ---- | ------------------------------------------------- |
| Free | 1 terraza, 5 fotos, 0 videos, sin CRM de clientes |
| Pro  | 5 terrazas, 20 fotos, 3 videos, CRM de clientes   |

- Stripe Checkout, Customer Portal y webhooks como fuente de verdad de la suscripción.
- `Entitlements` valida límites y funciones en el backend; ocultar botones no es autorización.
- Moneda: MXN.
- Los pagos de renta y anticipos **no** se procesan en línea: el propietario los registra manualmente.
- El precio y los IDs de Stripe se configuran por entorno.
- Webhooks idempotentes, cancelación, impago, periodo de gracia y reactivación.

---

## 4. Sitio público

### 4.1 Landing

- Página principal con propuesta de valor, búsqueda y acceso a publicación.
- Header y footer propios (`PublicShell`), separados del layout administrativo.
- Enlaces a login/registro y al panel del equipo si el usuario ya tiene sesión.

### 4.2 Catálogo de terrazas

- `/terrazas`: listado de terrazas publicadas y activas.
- Búsqueda por estado, municipio, capacidad mínima y características.
- Búsqueda por radio usando latitud/longitud y mapa Leaflet + OpenStreetMap.
- Solo se muestran terrazas publicadas y cuentas no suspendidas.
- Tarjetas con foto, ubicación, capacidad, características y distancia.

### 4.3 Ficha pública

- Galería de fotos y videos.
- Descripción, servicios/características agrupadas por categoría, capacidad y reglas.
- Ubicación y mapa.
- Fechas no disponibles próximas.
- Acceso al calendario completo de disponibilidad y al formulario de solicitud.
- Enlace para reportar una publicación.

### 4.4 Solicitud pública

- Formulario sin necesidad de crear cuenta.
- Datos: nombre, correo, teléfono, WhatsApp, fechas, tipo de evento, invitados y mensaje.
- Consentimiento obligatorio y honeypot anti-spam.
- La solicitud **no bloquea fechas** hasta que el propietario la acepta.
- Se crea o reutiliza el cliente en el CRM.
- Se capturan origen, UTM, referrer y `source_detail`.
- Se envían notificaciones al visitante y al propietario.

### 4.5 Portal del cliente

- `/reserva/{token}`: enlace seguro por reserva.
- Consulta de fechas, terraza, invitados, evento y estado.
- Confirmación o actualización de datos de contacto e invitados.
- Descarga de calendario `.ics`.
- Consulta y aceptación del contrato.
- Opinión post-evento de 1 a 5 con comentario.
- No expone notas internas ni información administrativa.

---

## 5. Administración de cuenta (`/app/{team}`)

### 5.1 Dashboard

- Terrazas publicadas, solicitudes nuevas, reservas próximas y clientes.
- Tareas abiertas/vencidas.
- Ocupación de los próximos 30 días.
- Conversión reciente.
- Anticipos recibidos.
- Accesos rápidos a terrazas, solicitudes y clientes.

### 5.2 Terrazas

- Alta, edición, publicación y despublicación.
- Datos: nombre, descripción, ubicación, coordenadas, capacidad, reglas y contacto.
- Detalles de espacio: tipo, acceso, estacionamiento y vista.
- Características/amenidades agrupadas por categoría.
- Precios informativos: desde, tarifa por día (lunes a domingo) con horas, entrada, salida y precio, cargos adicionales y anticipo requerido.
- Tarifas por temporada con rango de fechas y precio por día (lunes a domingo) con horas, entrada, salida y precio; capacidad, cargos adicionales y condiciones.
- Paquetes de renta con precio, duración, capacidad, servicios y condiciones.
- Servicios comerciales reutilizando el catálogo de características: incluidos o con costo adicional, precio opcional y unidad de cobro.
- Promociones informativas con vigencia, días aplicables y condiciones.
- Fotos y videos con límites por plan.
- Vista pública y administración por terraza.

### 5.3 Centro de administración de terraza

- `/app/{team}/listings/{listing}`.
- Métricas de la terraza.
- Reservas y solicitudes scoped.
- Clientes de esa terraza.
- Disponibilidad.
- Fotos y datos.
- Nueva reserva dentro de la terraza.

### 5.4 Disponibilidad

- Calendario interactivo por terraza.
- Bloqueos manuales por fecha y rango.
- Series recurrentes semanales, quincenales y mensuales.
- Estados explícitos de fecha: disponible, bloqueada o reservada.
- Las reservas aceptadas bloquean las fechas.

### 5.5 Solicitudes y reservas

- Bandeja global y bandeja scoped por terraza.
- Estados: nueva, contactada, aceptada, rechazada, cancelada y expirada.
- Expiración automática de solicitudes nuevas/contactadas cuando pasan las fechas.
- Aceptar bloquea fechas; rechazar/cancelar libera fechas cuando aplica.
- Reprogramación de reservas con validación de disponibilidad.
- Registro manual de anticipos y su estado.
- Historial de actividad de la reserva.
- Nueva reserva manual.
- Búsqueda y filtros por estado, terraza y texto.

### 5.6 CRM de clientes

- Alta manual, importación CSV y exportación CSV con protección contra fórmulas.
- Ficha de cliente con secciones y submenú:
    - Resumen
    - Tareas
    - Timeline
    - Correo
    - Cotizaciones
    - Solicitudes
    - Datos
    - Etiquetas
    - Ciclo de vida
- Timeline de llamadas, correos, WhatsApp, reuniones, notas y eventos del sistema.
- Tareas con responsable, vencimiento, prioridad y estado.
- Etiquetas por cuenta.
- Métricas por cliente.
- Pipeline: prospecto, contactado, calificado, propuesta, ganado y perdido.
- Responsable del cliente y filtro “Mis clientes”.
- Detección de posibles duplicados por teléfono/WhatsApp.
- Fusión de clientes, archivado, restauración y anonimización.
- Bandeja “Hoy” con tareas vencidas, de hoy y próximas.
- Digest diario de tareas por responsable.
- Registro de correos y comunicaciones en el timeline.

### 5.7 Cotizaciones

- `/app/{team}/bookings/quotes`.
- Cotización con terraza, fechas, invitados, total, anticipo, notas y términos.
- Estados: borrador, enviada, aceptada, rechazada y vencida.
- Link público para aceptar o rechazar sin cuenta.
- Envío por correo y aviso al propietario al aceptar.
- Conversión de una cotización aceptada en reserva con bloqueo de fechas.
- Sección de cotizaciones dentro de la ficha del cliente.

### 5.8 Contratos

- Un contrato por reserva.
- Título y términos editables.
- Estados: borrador, publicado y aceptado.
- Publicación en el portal del cliente.
- Aceptación con nombre, fecha e IP hash.
- Vista imprimible para guardar como PDF desde el navegador.
- Bloqueo de edición después de la aceptación.
- Notificación y registro en el timeline al aceptar.

### 5.9 Correo y plantillas

- Plantillas por cuenta con variables:
    - `{{nombre}}`
    - `{{correo}}`
    - `{{terraza}}`
    - `{{fechas}}`
    - `{{empresa}}`
- Composer de correo desde la ficha del cliente.
- Envío en cola y registro automático en el timeline.
- Baja con enlace firmado y bloqueo de envíos posteriores.
- Reactivación manual del correo.
- WhatsApp sigue siendo un enlace manual `wa.me`, sin envío automatizado.

### 5.10 Reportes

- `/app/{team}/bookings/reports`.
- Embudo actual por etapa del pipeline.
- Solicitudes, aceptadas, rechazadas, vencidas y conversión.
- Tiempo promedio de primera respuesta y de cierre.
- Origen de leads por UTM, referrer o canal.
- Rendimiento por terraza.
- Cotizaciones creadas vs aceptadas.
- Promedio y cantidad de opiniones.
- Tendencia de los últimos 6 meses.
- Filtro por rango de fechas.

### 5.11 Notificaciones internas

- Campana en el header con contador de no leídas.
- Lista de últimas notificaciones.
- Marcado individual o masivo como leídas.
- Notificaciones de base de datos para:
    - Nuevas solicitudes.
    - Tareas pendientes.
    - Cotizaciones aceptadas.
    - Reservas próximas.
    - Confirmación del cliente.
    - Contrato aceptado.
    - Opinión recibida.
- Preferencia de correo para avisos al propietario.

### 5.12 Facturación y ajustes

- Plan actual, uso y límites.
- Checkout y Customer Portal de Stripe.
- Facturas disponibles.
- Perfil, seguridad, 2FA, passkeys, apariencia.
- Cuentas, miembros, invitaciones, cambio de cuenta y branding de cuenta.

---

## 6. Características de las terrazas

Catálogo actual: **50 características en 8 categorías**.

| Categoría                 | Ejemplos                                                                                                                         |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Instalaciones y espacios  | Wi-Fi, baños, estacionamiento, accesible, terraza techada, áreas verdes, jardín, barra, comedor, sala de estar, rooftop, pérgola |
| Albercas y descanso       | Alberca, alberca climatizada, chapoteadero, jacuzzi, camastros, regaderas                                                        |
| Cocina y alimentos        | Cocina, cocina equipada, asador, refrigerador, estufa, plancha, hielera                                                          |
| Mobiliario y equipamiento | Mesas y sillas, mobiliario infantil, mantelería, sonido, iluminación, Smart TV, calefacción, aire acondicionado                  |
| Entretenimiento           | Brincolín, futbolito, cancha de fútbol, piñatero, juegos infantiles, cajón ranita, fogatero                                      |
| Hospedaje                 | Hospedaje, habitaciones                                                                                                          |
| Servicios adicionales     | Banquetes, taquizas, decoración, candy bar, DJ, meseros, hielo y refrescos                                                       |
| Seguridad                 | Cámaras de seguridad                                                                                                             |

- Administración con checkboxes agrupados por categoría.
- Se conservan las selecciones existentes.
- Se eliminan duplicados al guardar.
- La ficha pública agrupa las características y oculta categorías vacías.
- Los filtros priorizan 13 características destacadas y el resto aparece en “Más características”.
- Los servicios adicionales solo se muestran como disponibles; no incluyen precio, paquete ni reserva separada.

---

## 7. Comunicaciones

- Correo transaccional para solicitudes, aceptación, rechazo, reprogramación, recordatorios y contratos.
- Correo de seguimiento desde el CRM con plantillas.
- Baja voluntaria con enlace firmado.
- Notificaciones internas en la app.
- Recordatorio de reservas próximas.
- Digest diario de tareas.
- WhatsApp V1: enlace manual `wa.me`; no hay API automatizada.

---

## 8. Plataforma y moderación

- `/app/platform`.
- Resumen operativo.
- Cuentas, usuarios, terrazas y suscripciones.
- Suspensión/restauración de cuentas, listings y usuarios con motivo.
- Reportes de terrazas y revisión manual.
- Auditoría con `spatie/laravel-activitylog`.
- Branding global.
- Estado del sistema: cola, scheduler, trabajos fallidos.
- Acceso protegido por permiso de plataforma y rol de superadmin.

---

## 9. Seguridad y aislamiento

- Todas las filas de negocio llevan `team_id`.
- Políticas + route model binding por cuenta.
- Los IDs de otra cuenta devuelven 404.
- URLs públicas con token para reservas, cotizaciones y baja de correo.
- Enlaces de baja firmados.
- Rate limiting en solicitudes, cotizaciones, contratos, feedback y reportes.
- Honeypot en formularios públicos.
- Hash de IP para trazabilidad sin almacenar la IP completa.
- Consentimiento y opt-out de correo.
- Límites de plan validados en backend.

---

## 10. Rutas principales

### Público

- `/`
- `/terrazas`
- `/terrazas/{slug}`
- `/terrazas/{slug}/disponibilidad`
- `/reserva/{token}`
- `/reserva/{token}/contrato`
- `/reserva/{token}/calendario.ics`
- `/cotizaciones/{token}`
- `/baja/{customer}`

### Administración

- `/app/{team}/dashboard`
- `/app/{team}/listings`
- `/app/{team}/listings/{listing}`
- `/app/{team}/listings/{listing}/pricing`
- `/app/{team}/listings/{listing}/packages`
- `/app/{team}/listings/{listing}/services`
- `/app/{team}/listings/{listing}/promotions`
- `/app/{team}/bookings`
- `/app/{team}/bookings/today`
- `/app/{team}/bookings/quotes`
- `/app/{team}/bookings/templates`
- `/app/{team}/bookings/reports`
- `/app/{team}/bookings/customers`
- `/app/{team}/bookings/customers/{customer}`
- `/app/{team}/billing`
- `/app/settings/...`
- `/app/platform/...`

---

## 11. Verificación y calidad

- **394 tests / 3,022 aserciones** en SQLite y MySQL 8.4.
- Smoke test de flujo completo:
  landing → solicitud → CRM → cotización → contrato → portal → reserva → opinión → reportes.
- Pint, PHPStan, formato/lint frontend (`npm run check`), `vue-tsc`, build y `composer ci:check` en verde.
- Seeders de demo idempotentes.
- Base local: MySQL 8.4.
- Documentación de referencia:
    - `docs/development/PHASE-STATUS.md`
    - `docs/development/CRM-PLAN.md`
    - `docs/development/TERRACE-ADMIN.md`
    - `docs/development/PRODUCTION.md`
    - `docs/decisions/0001-mvp.md`

---

## 12. Pendientes antes de producción

- Definir dominio y entorno objetivo.
- Configurar secretos y variables de producción.
- Correo transaccional real.
- Worker de colas y scheduler.
- Stripe en modo de producción y reconciliación de webhooks.
- Storage de medios y respaldos.
- SSL, monitoreo y rollback.
- Ejecutar `composer production:check`.
- Definir si se hará push/PR de los commits locales; `main` está 94 commits adelante de `origin/main`.

---

## 13. Fuera de alcance del MVP

- WhatsApp Business API automatizado.
- Cobro de la renta o anticipos en línea al visitante.
- Paquetes comerciales, tarifas por servicio o reservas independientes por servicio.
- Atributos jerárquicos entre características.
- Constructor dinámico de campos.
- IA para contenido o scoring.
- Multi-vertical genérico.
- Sincronización bidireccional con un CRM externo.
- App móvil nativa.

---

## 14. Conclusión

El MVP ya permite operar el ciclo completo de una terraza: publicar, recibir solicitudes, dar seguimiento al cliente, cotizar, formalizar el contrato, confirmar la reserva, registrar anticipos, comunicarse por correo, recibir opiniones y medir resultados.

La brecha principal ya no es funcional, sino operativa: **preparar y autorizar el lanzamiento a producción**.

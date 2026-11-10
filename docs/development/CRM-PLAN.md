# Plan de evolución del CRM

**Estado:** Paso 1 implementado; pasos 2–7 pendientes
**Módulo actual:** `Modules/Bookings`
**Plan comercial:** Pro (`features.customers = true`)
**Última actualización:** 2026-11-10

## Estado de implementación

| Paso                                | Estado                   |
| ----------------------------------- | ------------------------ |
| 0. CRUD base                        | Completado               |
| 1. Timeline y notas                 | Completado el 2026-11-10 |
| 2. Tareas                           | Completado el 2026-11-10 |
| 3. Etiquetas                        | Completado el 2026-11-10 |
| 4. Métricas                         | Completado el 2026-11-10 |
| 5. Pipeline                         | Pendiente                |
| 6. Importación/fusión/ciclo de vida | Pendiente                |
| 7. Comunicaciones                   | Pendiente                |

## 1. Punto de partida

El CRM actual es un directorio de clientes ligado a solicitudes y reservas. Ya
existe:

- Alta manual de clientes.
- Búsqueda por nombre, correo y teléfono.
- Edición de datos de contacto y notas.
- Ficha con solicitudes/reservas del cliente.
- Creación automática del cliente desde solicitudes públicas y reservas manuales.
- Exportación CSV con filtros y protección contra fórmulas.
- Aislamiento por `team_id` y acceso limitado al plan Pro.

Todavía no existe:

- Historial de interacciones.
- Tareas o seguimiento comercial.
- Etiquetas o segmentos.
- Métricas por cliente.
- Pipeline de ventas.
- Importación, fusión o anonimización.
- Envío de correo/WhatsApp desde el CRM.

### Archivos de referencia

- `Modules/Bookings/src/Http/Controllers/CustomerController.php`
- `Modules/Bookings/src/Models/Customer.php`
- `Modules/Bookings/src/Policies/CustomerPolicy.php`
- `Modules/Bookings/resources/js/pages/Customers/Index.vue`
- `Modules/Bookings/resources/js/pages/Customers/Show.vue`
- `Modules/Bookings/src/Support/CustomerCsvExport.php`
- `Modules/Billing/src/Support/Entitlements.php`

## 2. Principios de diseño

1. **No crear un módulo nuevo todavía.** El CRM vive en `Modules/Bookings`
   mientras su alcance sea clientes, solicitudes y reservas. Si crece hasta
   pipeline, tareas y comunicaciones complejas, evaluar `Modules/Crm` con un ADR.
2. **Toda fila lleva `team_id`.** No aceptar `team_id` desde el request; resolverlo
   con `ResolvesCurrentTeam`, políticas y route model binding.
3. **Pro desde el backend.** Cada ruta y acción debe comprobar
   `Entitlements::allows($team, 'customers')`; ocultar botones no es autorización.
4. **No duplicar el historial de reservas.** El timeline del cliente enlaza
   solicitudes y reservas; sus cambios internos siguen en el historial de la
   reserva.
5. **Fechas en UTC, presentación en la zona de la cuenta.** Las tareas y
   actividad se muestran en `America/Mexico_City` salvo que la cuenta defina otra.
6. **Acciones destructivas con auditoría.** Importar, fusionar, archivar o
   anonimizar requiere confirmación, motivo y registro.
7. **Primero registrar, después automatizar.** El envío automático de correo o
   WhatsApp queda fuera hasta que se decida proveedor, consentimiento y costos.

## 3. Decisiones que deben cerrarse antes de cada paso

| Decisión                                       | Necesaria para | Propuesta inicial                                                                             |
| ---------------------------------------------- | -------------- | --------------------------------------------------------------------------------------------- |
| ¿Quién puede editar, etiquetar y crear tareas? | Permisos       | Owner/Admin gestionan; Member puede consultar y agregar notas si se mantiene el modelo actual |
| ¿Qué estados tendrá el pipeline?               | Paso 5         | `lead`, `contacted`, `qualified`, `proposal`, `won`, `lost`                                   |
| ¿Las tareas envían recordatorio?               | Paso 2         | Sí, recordatorio diario al responsable; sin correo al cliente                                 |
| ¿Qué pasa al fusionar clientes?                | Paso 6         | Mover reservas, actividades, etiquetas y tareas al cliente destino                            |
| ¿Qué significa borrar un cliente?              | Paso 6         | Archivar primero; anonimizar solo con motivo y política de retención                          |
| ¿Se enviarán correos/WhatsApp desde el CRM?    | Futuro         | No en esta fase; solo registrar comunicaciones manuales                                       |

## 4. Roadmap por pasos

### Paso 0 — Cierre del CRUD base y UX

**Objetivo:** dejar el CRM actual consistente antes de agregar entidades nuevas.

**Alcance:**

- Revisar la ficha del cliente en desktop y móvil.
- Mostrar en la ficha un resumen claro: contacto, notas, solicitudes y reservas.
- Enlazar desde cada solicitud al detalle de reserva.
- Añadir un estado vacío útil y un acceso visible desde `Bookings`.
- Confirmar que el plan Free redirige a billing y Pro abre el CRM.

**Tests:**

- Free no accede; Pro accede.
- Miembro de otra cuenta no accede por ID.
- La ficha carga solo clientes de la cuenta actual.

**Criterio de aceptación:** no hay regresiones de autorización y la ficha se
entiende sin conocer la base de datos.

**Esfuerzo:** S.

---

### Paso 1 — Timeline y notas de interacción

**Objetivo:** que el propietario pueda registrar qué pasó con el cliente y cuándo.

**Modelo de datos:**

Tabla `customer_activities`:

| Columna       | Tipo              | Notas                                                    |
| ------------- | ----------------- | -------------------------------------------------------- |
| `id`          | bigint            | PK                                                       |
| `team_id`     | FK teams          | obligatorio                                              |
| `customer_id` | FK customers      | obligatorio                                              |
| `user_id`     | FK users nullable | autor; nullable para eventos del sistema                 |
| `type`        | string            | `note`, `call`, `email`, `whatsapp`, `meeting`, `system` |
| `body`        | text              | contenido de la nota                                     |
| `occurred_at` | timestamp         | cuándo ocurrió                                           |
| `metadata`    | json nullable     | datos auxiliares                                         |
| `timestamps`  |                   |                                                          |

Índices: `(team_id, customer_id, occurred_at)`, `(team_id, type)`.

**Backend:**

- Modelo `CustomerActivity` con relación a `Customer` y autor.
- Acción `RecordCustomerActivity` para eventos manuales y automáticos.
- Eventos automáticos iniciales:
    - Cliente creado.
    - Cliente actualizado.
    - Cliente vinculado a una solicitud.
- Rutas:
    - `POST /{team}/bookings/customers/{customer}/activities`
    - `DELETE /{team}/bookings/customers/{customer}/activities/{activity}` solo autor
      o Admin.
- Validación de `body`, `type` y `occurred_at`.
- Política: miembros con acceso al CRM pueden crear notas; borrar queda limitado
  a Admin o al autor.

**Frontend:**

- Composer de nota en la ficha: tipo, texto y “Registrar”.
- Timeline con icono, tipo, autor, fecha y texto.
- Filtro simple por tipo.
- Paginación o “cargar más”.

**Tests:**

- Crear nota en un cliente propio.
- No crear nota en cliente de otra cuenta.
- Borrar nota propia; no borrar nota ajena si no es Admin.
- El timeline no expone actividades de otra cuenta.
- Free sigue bloqueado.

**Criterio de aceptación:** cada cliente puede contar su historia sin salir de la
ficha.

**Esfuerzo:** M.

---

### Paso 2 — Tareas y seguimiento

**Objetivo:** convertir el CRM en una herramienta de acción, no solo de consulta.

**Modelo de datos:**

Tabla `customer_tasks`:

| Columna        | Tipo               | Notas                       |
| -------------- | ------------------ | --------------------------- |
| `id`           | bigint             | PK                          |
| `team_id`      | FK teams           | obligatorio                 |
| `customer_id`  | FK customers       | obligatorio                 |
| `assigned_to`  | FK users nullable  | miembro de la cuenta        |
| `created_by`   | FK users nullable  | autor                       |
| `title`        | string 160         | obligatorio                 |
| `notes`        | text nullable      | detalle                     |
| `due_at`       | timestamp nullable | vencimiento                 |
| `priority`     | string             | `low`, `normal`, `high`     |
| `status`       | string             | `open`, `done`, `cancelled` |
| `completed_at` | timestamp nullable |                             |
| `timestamps`   |                    |                             |

Índices: `(team_id, status, due_at)`, `(team_id, customer_id, status)`.

**Backend:**

- CRUD de tareas y acción `CompleteCustomerTask`.
- Validar que `assigned_to` pertenece a la cuenta.
- Rutas anidadas bajo cliente.
- Dashboard: contador de tareas vencidas y de hoy.
- Comando diario opcional `bookings:send-task-reminders` para avisar al
  responsable; no enviar nada al cliente.

**Frontend:**

- Sección “Tareas” en la ficha con añadir, completar, cancelar y editar.
- Mostrar próxima tarea en la tarjeta del cliente.
- Filtro en índice: “Con tareas pendientes”.
- Widget “Seguimiento” en el dashboard de la cuenta.

**Tests:**

- Crear tarea asignada a un miembro de la cuenta.
- Rechazar asignación a usuario externo.
- Completar tarea registra fecha y actividad.
- Filtro de tareas vencidas.
- Aislamiento por team y bloqueo Free.

**Criterio de aceptación:** el propietario sabe a quién contactar hoy y qué
tareas están vencidas.

**Esfuerzo:** M.

---

### Paso 3 — Etiquetas y segmentos

**Objetivo:** clasificar clientes sin crear campos arbitrarios.

**Modelo de datos:**

- `customer_tags`: `id`, `team_id`, `name`, `slug`, `color`, `timestamps`.
  Único: `(team_id, slug)`.
- Pivot `customer_customer_tag` o `customer_tag_assignments`:
  `customer_id`, `customer_tag_id`, `timestamps`. Único por pareja.

**Backend:**

- CRUD mínimo de etiquetas por cuenta.
- Sincronizar etiquetas de un cliente.
- Filtro `tag` en el índice.
- Límite inicial: 20 etiquetas por cuenta para evitar ruido.
- No crear etiquetas globales de plataforma.

**Frontend:**

- Chips de etiquetas en tarjetas y ficha.
- Selector múltiple con creación rápida.
- Filtro por etiqueta en el índice.
- Administración simple de etiquetas de la cuenta.

**Tests:**

- Crear etiqueta y asignarla a cliente propio.
- No asignar etiqueta de otra cuenta.
- Filtro por etiqueta.
- Límite de etiquetas.
- Eliminar etiqueta no borra clientes.

**Criterio de aceptación:** se pueden separar clientes recurrentes, corporativos,
eventos o cualquier criterio operativo sin cambiar el esquema.

**Esfuerzo:** M.

---

### Paso 4 — Métricas y resumen del cliente

**Objetivo:** dar contexto comercial inmediato sin construir un BI completo.

**Datos calculados (sin tabla nueva inicialmente):**

- Total de solicitudes.
- Solicitudes aceptadas.
- Reservas próximas.
- Anticipos recibidos.
- Última actividad.
- Próxima tarea.
- Ingreso confirmado por anticipos.
- Etiquetas.
- Etapa del pipeline (cuando exista).

**Backend:**

- Servicio `CustomerMetrics` para centralizar consultas.
- Evitar N+1 con `withCount`, `withSum` y consultas agregadas.
- Exponer métricas en índice y ficha.
- Widget de dashboard: “Clientes con seguimiento pendiente”, “Nuevos clientes
  del mes” y “Anticipos confirmados”.

**Frontend:**

- Tarjetas de resumen en la ficha.
- Columnas o badges en el índice.
- Estado vacío con CTA para registrar primera interacción.

**Tests:**

- Cálculo con 0, 1 y varias reservas.
- Anticipos `received` cuentan; `pending` no.
- Aislamiento por team.
- Rendimiento: el índice no dispara consultas por cliente.

**Criterio de aceptación:** al abrir un cliente se entiende su valor y estado sin
revisar cada reserva manualmente.

**Esfuerzo:** S/M.

---

### Paso 5 — Pipeline comercial

**Objetivo:** dar seguimiento al estado comercial del cliente.

**Modelo de datos:**

- Añadir `pipeline_stage` a `customers` con default `lead`.
- Valores iniciales: `lead`, `contacted`, `qualified`, `proposal`, `won`, `lost`.
- Índice `(team_id, pipeline_stage)`.

**Backend:**

- Acción `ChangeCustomerStage`.
- Registro automático de actividad al cambiar etapa.
- Filtro por etapa en índice.
- Etapa visible en ficha y tarjetas.
- No automatizar etapas por reservas en la primera versión.

**Frontend:**

- Select de etapa en ficha.
- Badge y filtro en índice.
- Contadores por etapa en un tablero simple, sin drag-and-drop todavía.

**Tests:**

- Cambio de etapa propio.
- No cambiar cliente ajeno.
- El cambio registra actividad.
- Filtro por etapa.
- Migración con datos existentes en `lead`.

**Criterio de aceptación:** se puede ver un embudo básico y mover clientes entre
etapas sin cambiar de herramienta.

**Esfuerzo:** M.

---

### Paso 6 — Importación, fusión y ciclo de vida

**Objetivo:** operar con datos reales sin depender de carga manual cliente por
cliente.

**6A. Importación CSV:**

- Subir CSV, previsualizar columnas y mapear a nombre, correo, teléfono,
  WhatsApp y notas.
- Detectar duplicados por correo o teléfono dentro de la cuenta.
- Confirmar antes de insertar.
- Reporte de creados, omitidos y errores.
- No importar etiquetas ni tareas en la primera versión.

**6B. Fusión:**

- Seleccionar cliente destino.
- Mover solicitudes, actividades, etiquetas y tareas.
- Resolver conflictos de correo/teléfono.
- Marcar el origen como fusionado o archivado.
- Auditoría con actor y motivo.

**6C. Archivar y anonimizar:**

- `archived_at` en `customers` para ocultar sin borrar.
- La anonimización reemplaza datos personales y conserva métricas agregadas.
- Requiere motivo y confirmación.
- No borrar solicitudes/reservas asociadas sin decisión explícita de retención.

**Tests:**

- Importación con duplicados y filas inválidas.
- Fusión mueve relaciones y conserva historial.
- Un usuario no puede importar/fusionar clientes de otra cuenta.
- Archivado oculta del índice pero conserva datos.
- Anonimización no expone datos personales.

**Criterio de aceptación:** se puede migrar una cartera real y corregir duplicados
sin perder reservas ni historial.

**Esfuerzo:** L, dividir en tres entregas.

---

### Paso 7 — Comunicación desde el CRM

**Objetivo:** dejar preparado el registro de comunicaciones y decidir si se
envían desde la plataforma.

**Primera entrega, sin proveedor nuevo:**

- Registrar llamadas, correos enviados manualmente y mensajes de WhatsApp
  mediante el timeline (Paso 1).
- Acción “Abrir WhatsApp” reutilizando el enlace `wa.me` existente.
- No afirmar que el mensaje fue enviado por la plataforma.

**Segunda entrega, solo con decisión explícita:**

- Correo transaccional desde el CRM.
- Plantillas.
- WhatsApp Business API con consentimiento, plantillas y costos.
- Registrar entrega/fallo y actividad.

**Criterio de aceptación de la primera entrega:** el equipo registra la
comunicación sin que la aplicación envíe mensajes no autorizados.

**Esfuerzo:** S para registro manual; L para envío automatizado.

---

## 5. Orden recomendado de implementación

1. Paso 0: cierre del CRUD base.
2. Paso 1: timeline y notas.
3. Paso 2: tareas.
4. Paso 4: métricas.
5. Paso 3: etiquetas.
6. Paso 5: pipeline.
7. Paso 6A: importación CSV.
8. Paso 6B y 6C: fusión y anonimización.
9. Paso 7: comunicaciones, solo si hay decisión de proveedor.

El orden pone primero el valor diario (historial y tareas), luego la clasificación
y el reporting, y deja para el final las operaciones destructivas y las
integraciones externas.

## 6. Reglas transversales de implementación

- Cada nueva tabla lleva `team_id` y índice compuesto por cuenta.
- Cada controlador usa `ResolvesCurrentTeam`, `Gate` y route model binding.
- Cada ruta del CRM vuelve a comprobar el plan Pro.
- Cada acción destructiva pide confirmación y escribe auditoría.
- Cada nueva página necesita pruebas de aislamiento entre cuentas.
- Cada cambio de esquema va en `Modules/Bookings/database/migrations`.
- No introducir Bootstrap, jQuery ni otra librería de componentes.
- No crear campos dinámicos ni un constructor de CRM genérico.
- No enviar correo/WhatsApp al cliente sin consentimiento y proveedor definido.

## 7. Fuera de alcance de esta evolución

- Constructor dinámico de campos.
- Automatizaciones complejas o scoring con IA.
- App móvil nativa.
- Permisos granulares por miembro (se mantiene Owner/Admin/Member).
- Sincronización bidireccional con un CRM externo.
- Envío masivo de campañas.
- Facturación o cobro de la renta al visitante.

## 8. Primera entrega propuesta

La primera entrega debe ser el **Paso 1: timeline y notas**, porque:

- No requiere cambios de plan ni de facturación.
- Aprovecha la ficha actual.
- Da valor inmediato al propietario.
- Sienta la base para tareas, etiquetas y pipeline.
- Se puede probar con datos reales sin tocar Stripe.

Criterio de cierre de esa entrega:

- Se puede agregar una nota o llamada desde la ficha.
- Se ve quién la registró y cuándo.
- La actividad está aislada por cuenta.
- El plan Free no accede.
- Tests SQLite y MySQL en verde.
- `npm run check`, `vue-tsc` y build en verde.

## 9. Estimación relativa

| Paso                            | Esfuerzo | Riesgo principal                 |
| ------------------------------- | -------- | -------------------------------- |
| 0. CRUD base                    | S        | Bajo                             |
| 1. Timeline y notas             | M        | Bajo                             |
| 2. Tareas                       | M        | Medio: recordatorios             |
| 3. Etiquetas                    | M        | Bajo                             |
| 4. Métricas                     | S/M      | Medio: rendimiento               |
| 5. Pipeline                     | M        | Medio: semántica comercial       |
| 6. Importar/fusionar/anonimizar | L        | Alto: datos y privacidad         |
| 7. Comunicaciones               | S/L      | Alto: proveedor y consentimiento |

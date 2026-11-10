# Plan: SaaS de reservaciones para terrazas

## Visión

Crear y lanzar una aplicación SaaS Laravel 13 para que propietarios publiquen terrazas, administren disponibilidad y prospectos, y reciban solicitudes de renta. Los propietarios pagan una suscripción mensual con Stripe para desbloquear capacidades. La base debe poder reutilizarse después en otros giros (decoradores con globos, stands, etc.) sin copiar y pegar toda la aplicación.

## Principios para no sobredimensionar

- Empezar desde un Laravel 13 Starter Kit oficial y su stack visual; no integrar Bootstrap.
- Lanzar primero una aplicación funcional para terrazas. No construir una fábrica de SaaS antes del primer producto.
- Modularidad interna por dominios; no tienda pública de plugins ni instalador runtime de ZIPs.
- El código de módulos viaja con el release de la aplicación. Los planes activan capacidades y límites; no instalan PHP por usuario.
- Mantener un solo repositorio y un flujo claro Dev → Sandbox/Staging → Production al inicio.
- Extraer o generalizar una capacidad cuando una segunda vertical realmente la necesite.
- No tocar ni mezclar las instalaciones existentes de AIDG; usar su flujo Dev/Sandbox/Production como referencia operativa.

## Producto inicial

### Propietarios / cuentas SaaS

- Registro, login, verificación y recuperación de cuenta.
- Una Account representa al negocio propietario; soporte futuro para varios miembros y roles.
- Ficha de negocio/terraza: nombre, descripción, ubicación, coordenadas, servicios, capacidad, reglas, fotos y videos.
- Agenda por terraza: disponible, no renta/no disponible y solicitud/fecha ocupada; zona horaria definida.
- Bandeja de leads/solicitudes y registro sencillo de clientes.
- Seguimiento manual de anticipo y estado de la reserva; el SaaS no procesa pagos de renta al inicio.

### Visitantes

- Catálogo público de terrazas publicadas.
- Búsqueda por ubicación y radio, mapa y filtros básicos.
- Ficha pública con imágenes, servicios y disponibilidad consultable.
- Formulario “me interesa rentarla” que crea un lead ligado a fechas y terraza.
- Aviso al propietario por correo y confirmación al visitante.

### Suscripciones / planes

- Plan Free: límites sencillos (por definir), por ejemplo una terraza y cupo básico de imágenes.
- Plan de pago mensual Stripe: límites/capacidades superiores.
- Stripe Checkout/Customer Portal y webhooks como fuente de verdad para estado de suscripción.
- Entitlements en el backend: el servidor valida límites y funciones; ocultar un botón en UI no cuenta como autorización.
- Separar la suscripción del propietario al SaaS del anticipo/renta que el cliente paga al propietario.

### Operación de plataforma

- Superusuario de plataforma separado de las cuentas de propietarios.
- Consultar cuentas, estado de suscripción Stripe y actividad/contenido publicado.
- Suspender cuenta o publicación por infracción/fraude, con motivo, auditoría y posibilidad de revisión.
- Controles manuales básicos antes de automatizar detección de fraude.

## Arquitectura mínima propuesta

- Laravel 13 + Starter Kit oficial, autenticación y frontend del kit.
- Un repositorio de aplicación con dominios organizados dentro del proyecto; no extraer paquetes Composer privados ahora.
- Separar responsabilidades por código: Accounts/Access, Listings, Availability, Leads/Customers, Billing/Stripe, Media y Platform Moderation.
- Los datos de negocio se scopean siempre por Account; los leads públicos se validan y relacionan con una terraza publicada.
- Diseñar una ficha común de negocio/listing y dejar campos propios de vertical en una extensión pequeña y explícita. No crear un constructor genérico de esquemas/campos en V1.
- Configuración por entorno y bases separadas para local, staging y producción.
- Imágenes/videos en storage administrado; no guardar archivos ejecutables subidos por usuarios.

## Fases

### Fase 0 — Decisiones de MVP

Cerrar únicamente las decisiones que bloquean el modelo: si una solicitud retiene fechas o solo crea un lead; límites Free/pago; proveedor de mapas/geocoding; política de publicación/moderación; canal de WhatsApp.

### Fase 1 — Esqueleto desplegado

- Crear una nueva aplicación desde Laravel 13 Starter Kit, sin Bootstrap.
- Configurar repo, CI básica, entorno local y staging.
- Login/registro y Account del propietario.
- Primera app accesible en staging pronto, antes de añadir funciones extensas.

### Fase 2 — Terrazas y publicación

- CRUD de ficha, ubicación, servicios y medios.
- Borrador/publicada/suspendida.
- Catálogo público y búsqueda inicial por radio.
- Pruebas de autorización y aislamiento Account.

### Fase 3 — Agenda y solicitudes

- Disponibilidad por fecha con estados explícitos y prevención de solicitudes duplicadas/conflictos.
- Formulario público de interés.
- Lead, notificaciones por correo y flujo del propietario para aceptar/rechazar/marcar fecha ocupada.
- Seguimiento de cliente y anticipo manual.

### Fase 4 — Stripe y planes

- Productos/precios recurrentes, Checkout, Portal y webhooks probados en Stripe test mode.
- Manejo idempotente de eventos, cancelación, impago, periodo de gracia y reactivación.
- Aplicar límites Free/Paid en backend y UI.
- No activar cobro real hasta completar reconciliación y pruebas de webhooks.

### Fase 5 — Superusuario y confianza

- Vista operativa de cuentas, pagos y publicaciones.
- Suspensión/restauración con motivo y audit log.
- Reportar una ficha y revisión manual; políticas básicas contra fraude.

### Fase 6 — Lanzamiento y extensiones

- Producción con backups, observabilidad, política de soporte y rollback.
- WhatsApp automático solo si se define proveedor/API, consentimiento, plantillas y costos; para V1 considerar enlace click-to-chat.
- IA para posts, más video y CRM avanzado después de validar uso y disposición
  de pago. Plan detallado del CRM: `docs/development/CRM-PLAN.md`.
- Probar una segunda vertical. Reutilizar lo común y añadir su ficha propia sin alterar el producto de terrazas.

## Decisiones todavía abiertas

1. ¿Una solicitud de booking bloquea temporalmente la fecha o solo avisa al propietario?
2. ¿El visitante necesita cuenta, o el booking es guest checkout con verificación por correo/teléfono?
3. Límites exactos de Free y primer plan mensual.
4. ¿WhatsApp V1 será enlace `wa.me` o mensaje automatizado mediante WhatsApp Business API?
5. Proveedor de mapas/geocoding y presupuesto/cobertura en México.
6. ¿Fotos/videos requieren aprobación antes de publicarse? ¿Qué datos se solicitan para responder a fraude?
7. ¿Una Account puede tener varias terrazas y varios miembros desde V1?

## Criterio de primer lanzamiento

Un propietario puede registrarse, crear y publicar una terraza, marcar disponibilidad, recibir una solicitud real por correo, llevar el lead/cliente y registrar anticipo; un visitante puede encontrar terrazas cercanas y enviar una solicitud; el dueño puede suscribirse en Stripe test mode y el backend aplica correctamente los límites del plan. Superusuario puede revisar y suspender cuentas/publicaciones. Todo probado en staging con aislamiento Account y sin Bootstrap.

## Fuera de alcance inicial

- Marketplace público de módulos, instalación de módulos ZIP en runtime o Composer privado.
- Cobrar la renta al visitante y repartir fondos a propietarios.
- Automatización de fraude basada en IA/KYC, chat completo, app móvil y constructor dinámico de campos.
- Multi-vertical genérico antes de validar terrazas.
- Módulos de academia/AIDG o cambios a sus repositorios.

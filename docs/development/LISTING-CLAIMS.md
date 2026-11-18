# Terrazas del Directorio y reclamación de propiedad

Este documento explica cómo cargar terrazas reales antes de tener propietarios
registrados y cómo entregar el control del anuncio a su dueño.

## Modelo

- **Cuenta Directorio:** cuenta de plataforma sin miembros
  (`teams.is_directory = true`) que posee las terrazas importadas.
- **Terraza reclamable:** pertenece al Directorio y `listings.claimed_at` es
  nulo.
- **Reclamación:** registro en `listing_claims` con la identidad del solicitante,
  la cuenta destino, el rol declarado y evidencia opcional.
- **Aprobación:** transfiere `listings.team_id` a la cuenta del propietario y
  conserva el slug y la URL pública.

Una terraza creada por su dueño desde el panel nunca es reclamable: al crearse se
marca `claimed_at` automáticamente.

## Puesta en marcha

```bash
php artisan migrate
php artisan app:directory-team
php artisan platform:sync-permissions
```

`app:directory-team` crea la cuenta si no existe y muestra su nombre, slug e id.
El nombre se puede personalizar:

```bash
php artisan app:directory-team --name="Directorio de Terrazas"
```

Requisitos operativos:

1. Configura el **correo de soporte** en `/app/platform/branding`. Ahí llegarán
   las reclamaciones y las solicitudes de terrazas sin reclamar.
2. Habilita los estados y municipios de las terrazas en
   `/app/platform/coverage`; si la ubicación no está habilitada, la terraza no
   aparece en el catálogo público.
3. Cada terraza necesita al menos una imagen, dirección y coordenadas para
   publicarse.

## Importación

### Formato JSON

```json
{
    "listings": [
        {
            "external_ref": "terraza-jardin-encinos",
            "name": "Jardín Los Encinos",
            "description": "Terraza para eventos sociales y corporativos.",
            "address": "Av. Vallarta 1234",
            "city": "Guadalajara",
            "state": "Jalisco",
            "country": "México",
            "postal_code": "44100",
            "latitude": 20.6736,
            "longitude": -103.4055,
            "capacity": 150,
            "services": ["parking", "wifi"],
            "event_types": ["bodas", "eventos-corporativos"],
            "details": { "space_type": "jardin" },
            "rules": "No se permiten mascotas.",
            "contact_name": "Contacto del anuncio",
            "contact_email": "contacto@ejemplo.test",
            "contact_phone": "3312345678",
            "price_from_cents": 1500000,
            "images": ["jardin-01.jpg", "jardin-02.jpg"]
        }
    ]
}
```

`external_ref` es opcional pero recomendado: permite volver a ejecutar la
importación sin duplicar terrazas. Si la terraza ya fue reclamada, la importación
no la modifica y termina con error.

### Formato CSV

La primera fila debe contener los encabezados. Los campos múltiples se separan
con `|`.

```csv
external_ref,name,address,city,state,latitude,longitude,capacity,services,event_types,images,contact_email
terraza-encinos,Jardín Los Encinos,Av. Vallarta 1234,Guadalajara,Jalisco,20.6736,-103.4055,150,parking|wifi,bodas|eventos-corporativos,jardin-01.jpg|jardin-02.jpg,contacto@ejemplo.test
```

Para `details`, precios por día, paquetes o estructuras anidadas usa JSON.

Los tipos de evento y las características se pueden escribir como **clave**
(`bodas`, `parking`) o como **nombre** (`Bodas`, `Estacionamiento`). Los valores
desconocidos se reportan como advertencia y se omiten; la terraza se importa con
los que sí existan en los catálogos.

### Ejecutar

```bash
php artisan listings:import storage/app/imports/terrazas.json
php artisan listings:import storage/app/imports/terrazas.json --images=storage/app/imports/fotos --publish
php artisan listings:import storage/app/imports/terrazas.csv --publish
```

- Sin `--publish`, las terrazas quedan en borrador para revisión.
- Con `--publish`, se publican solo si cumplen las reglas actuales; cualquier
  error se muestra en la salida y la terraza queda en borrador.
- Las imágenes se buscan en la ruta indicada o en `--images`. También se aceptan
  URLs `http(s)`.
- La importación es idempotente por `external_ref`.

### Importar desde la plataforma

`/app/platform/listings/import` permite cargar terrazas sin usar la consola. Es
ideal para los datos generales: título, capacidad, descripción, tipos de evento y
características.

1. Descarga la **plantilla CSV** o **JSON** desde la misma pantalla.
2. Sube el archivo (máximo 2 MB y 500 filas). Se aceptan CSV separados por coma,
   punto y coma o tabulador, con BOM o codificación de Excel.
3. Revisa la **vista previa**: cada fila indica si se creará, si actualizará una
   terraza existente por `external_ref` o si tiene error, además de
   advertencias de catálogos desconocidos y cobertura geográfica.
4. Confirma la importación. Las filas con error se omiten y se listan en el
   resultado para corregirlas y volver a subirlas.

Columnas mínimas del CSV: `titulo`, `capacidad`, `descripcion`, `tipos_evento`,
`caracteristicas`. Opcionales: `external_ref`, `direccion`, `municipio`,
`estado`, `pais`, `codigo_postal`, `reglas`, `contacto_nombre`,
`contacto_email`, `contacto_telefono`, `whatsapp`, `facebook`, `instagram`,
`tiktok`, `google_maps`.

Las terrazas se crean en el Directorio como borrador y reclamables por su
dueño. Para publicarlas hacen falta imágenes, dirección y coordenadas, igual que
con la consola.

## Convertir una terraza existente en reclamable

Si una terraza ya existe en una cuenta normal y quieres que su propietario la
reclame, no necesitas volver a importarla:

- Desde el detalle de la terraza en `/app/platform/listings/{slug}`, usa
  **"Marcar como reclamable"**.
- O por consola:

```bash
php artisan listings:make-claimable terraza-jardin-encinos \
  --reason="Se cargará desde el Directorio para que su dueño la reclame."
```

La acción mueve la terraza al Directorio, limpia `claimed_at`/`claimed_by_user_id`,
transfiere solicitudes y registros relacionados, avisa al propietario anterior y
deja activo el botón público de reclamo. El slug y la URL pública no cambian.

## Reclamación pública

- El detalle de una terraza sin reclamar muestra el aviso **"Anuncio sin
  reclamar"** y el botón **"Reclamar esta terraza"**.
- La URL se puede compartir directamente:
  `/terrazas/{slug}/reclamar`.
- El contacto importado (teléfono, WhatsApp y correo) **no se muestra** hasta
  que la terraza sea reclamada.
- Para enviar la reclamación se requiere una cuenta con correo verificado.
- El solicitante puede elegir una cuenta existente de la que sea propietario o
  administrador, o crear una cuenta nueva con el nombre del negocio.
- Se acepta un documento opcional (PDF, JPG, PNG o WebP, máximo 10 MB) guardado
  en disco privado.

## Revisión en plataforma

La cola está en `/app/platform/claims` y requiere
`platform.claims.view`/`platform.claims.manage`.

Las reclamaciones pendientes tienen el botón **"Aprobar y transferir"**
directamente en la lista (con confirmación); también se puede revisar el detalle
en `/app/platform/claims/{id}` para ver el mensaje, el documento y la cuenta
destino.

Al aprobar:

1. Se crea la cuenta de negocio si el solicitante no eligió una.
2. La terraza cambia de `team_id` y se marca como reclamada.
3. Las solicitudes, clientes, cotizaciones, contratos y bloqueos de
   disponibilidad de la terraza se mueven a la cuenta destino.
4. Las demás reclamaciones pendientes de esa terraza se rechazan
   automáticamente.
5. Se notifica al propietario y se registra el evento en el log `moderation`.

Al rechazar se exige un motivo y se envía por correo al solicitante.

Para corregir una aprobación equivocada, abre la terraza en el panel de
plataforma y usa **"Devolver al Directorio"**. La terraza vuelve a ser
reclamable y el propietario anterior recibe un aviso.

## Límites de plan

Reclamar una terraza no se bloquea por el límite del plan. Si la cuenta queda por
encima del límite de terrazas, solo se bloquea **crear terrazas nuevas**; la
terraza reclamada sigue siendo administrable.

## Notificaciones y colas

- Reclamación nueva: correo al soporte y notificación a los superadmins.
- Solicitud de visitante en terraza sin reclamar: correo al soporte.
- Aprobada, rechazada o devuelta al Directorio: correo y notificación en la app
  al solicitante/propietario.

Las notificaciones son `ShouldQueue`; en producción el worker debe estar activo.

## Datos de prueba para reclamaciones

En entorno local se pueden crear dos terrazas reclamables y dos cuentas de
prueba con:

```bash
php artisan db:seed --class=ClaimFlowDemoSeeder
```

Crea (de forma idempotente y sin tocar otras filas):

- `Terraza Demo Reclamable Uno` (`/terrazas/terraza-demo-reclamable-uno`), en el
  Directorio, con una reclamación pendiente de `dueno.uno@terrazas.test`.
- `Terraza Demo Reclamable Dos` (`/terrazas/terraza-demo-reclamable-dos`), sin
  reclamar, para probar el flujo completo.
- Cuentas verificadas `dueno.uno@terrazas.test` y `dueno.dos@terrazas.test`, con
  contraseña `password`, cada una con su cuenta de negocio.

Flujo sugerido: entrar como `dueno.dos`, reclamar la terraza Dos, luego entrar
como superadmin a `/app/platform/claims` y aprobar ambas con un clic.

## Verificación

```bash
php artisan test Modules/Platform/tests/Feature/ListingClaimTest.php
php artisan test Modules/Listings/tests/Feature/ListingImportTest.php
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run check
npx vue-tsc --noEmit
npm run build
```

# Separación de características, servicios y paquetes

Fecha: 2026-11-10

## Diagnóstico inicial

- Las características vivían en `listings.services` como un JSON de claves de un catálogo único definido en `config/verticals.php`.
- Ese catálogo mezclaba instalaciones físicas con prestaciones comerciales (`extras`: banquetes, DJ, meseros, etc.).
- Los servicios por inmueble ya existían en `listing_services` (`service_key`, `description`, `is_included`, `price_cents`, `price_unit`, `active`), con una configuración distinta por inmueble.
- Los paquetes ya existían en `listing_packages` y guardaban servicios incluidos en un JSON (`included_services`), pero reutilizaban el catálogo mezclado de características.
- La ficha pública mostraba características y servicios, pero sin separar claramente el catálogo ni distinguir “precio a consultar”.

## Cambios implementados

### Catálogos

- `TerraceServiceCatalog` queda como catálogo de **características del lugar** (43 elementos, 7 categorías).
- Se agregó `CommercialServiceCatalog` con un catálogo independiente de **servicios** (31 elementos, 5 categorías) desde `verticals.terrace.commercial_services`.
- Se conservaron claves antiguas de servicios (`catering`, `taquizas`, `decoration`, `candy_bar`, `dj`, `waiters`, `ice_and_drinks`) y las claves legacy `lodging`/`rooms` para no romper registros existentes.

### Servicios por inmueble

- Se agregaron `pricing_mode` y `provider` a `listing_services`.
- Modalidades: `included`, `additional`, `quote`.
- El precio solo se exige para `additional`; en `included` y `quote` el backend guarda `price_cents = null`.
- `is_included` se conserva sincronizado con la modalidad para compatibilidad.
- El proveedor puede ser propio o externo.

### Paquetes

- Los servicios incluidos ahora se seleccionan del catálogo comercial independiente.
- Se mantiene la validación de duplicados y la compatibilidad con servicios históricos.

### Administración

- La página de servicios muestra las tres modalidades, proveedor, precio y unidad de cobro.
- La selección de servicios se agrupa por las nuevas categorías.
- La página de paquetes conserva su formulario y ahora usa el catálogo comercial correcto.
- El formulario de características usa solo el catálogo físico del lugar.

### Ficha pública

- Orden de secciones: características, servicios disponibles y paquetes.
- Características agrupadas por categoría.
- Servicios con distintivo de incluido, costo adicional o precio a consultar; se indica el proveedor externo.
- Paquetes con sus servicios incluidos.

## Migraciones

- `2026_11_10_000025_add_pricing_mode_and_provider_to_listing_services_table`
  - Agrega `pricing_mode` y `provider` con valores seguros.
  - Rellena registros existentes: incluido, costo adicional o precio a consultar según `is_included` y `price_cents`.
  - No elimina columnas ni datos.

## Tratamiento de datos existentes

- Las características guardadas se conservan.
- Las claves históricas de servicios siguen siendo válidas para lectura y edición.
- Los paquetes existentes conservan sus servicios; las claves antiguas tienen etiqueta de respaldo.
- No se ejecutaron borrados ni migraciones destructivas.

## Pruebas

- SQLite: 399 tests / 3,122 assertions.
- MySQL 8.4: 399 tests / 3,122 assertions.
- `composer ci:check`: PASS (Pint, PHPStan, Vue/TS, PHPUnit).
- `npm run build` y `npm run build:ssr`: PASS.

## Pendientes

- No se implementaron contratación, pagos, reservas ni WhatsApp, conforme al alcance.
- Si se desea, puede agregarse después un catálogo administrable desde base de datos en lugar de configuración.

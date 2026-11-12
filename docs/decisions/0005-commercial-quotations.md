# 0002 — Cotizaciones comerciales en PDF

## Estado

Implementado (Plan Pro).

## Alcance

Las cotizaciones son documentos **comerciales**. No son facturas fiscales, no
generan CFDI, no realizan timbrado SAT, no cobran pagos y no convierten
automáticamente una cotización aceptada en una reserva confirmada.

## Entitlement

El módulo usa la feature `quotations` de `config/billing.php`:

- Plan Free: `false`.
- Plan Pro: `true`.

`Entitlements` se evalúa siempre en el backend para listar, crear, editar,
emitir, descargar, duplicar y cancelar. Ocultar el módulo en Vue no es un
control de acceso. Al perder Pro no se eliminan cotizaciones ni PDFs, pero el
módulo queda bloqueado y redirige a facturación.

## Modelo de datos

Se amplió `customer_quotes` en lugar de crear un segundo sistema:

- `folio` único por cuenta (`COT-000001`).
- `rental_mode` (`hour`, `day`, `fixed`), horario y cantidad facturable.
- `tax_mode` (`none`, `added`, `included`), `tax_rate_bps`, `tax_cents`.
- Descuento (`none`, `fixed`, `percent`), `discount_value`, `discount_cents`.
- Importes en centavos: `subtotal_cents`, `total_cents`.
- Snapshots de emisor, cliente y terraza.
- `pdf_path`, `issued_at`, `pdf_generated_at`, `cancelled_at`.

Tablas nuevas:

- `customer_quote_items`: conceptos con snapshot de descripción, unidad,
  cantidad, precio unitario y orden.
- `customer_quote_status_histories`: auditoría de cambios de estado.
- `quote_sequences`: numeración segura por cuenta con bloqueo de la fila de
  equipo dentro de una transacción.

## Orden de cálculo

1. Importe de conceptos = suma de `cantidad × precio_unitario_cents`.
2. Descuento sobre el importe de conceptos (fijo o porcentaje).
3. Base = importe de conceptos − descuento.
4. IVA:
    - `none`: no se desglosa.
    - `added`: `round(base × tasa)` y se suma al total.
    - `included`: `round(base × tasa / (10000 + tasa))`; el total no aumenta.
5. Nunca se permiten totales negativos ni descuentos mayores al importe
   elegible.

La tasa inicial es 16% (`1600` puntos base) y vive en
`config/bookings.php → quotes.tax_rate_bps`.

## Renta y catálogo

- Se reutilizan `ListingService` y `ListingPackage` de `Listings`.
- Los conceptos se guardan como snapshot; cambios posteriores de tarifas no
  alteran cotizaciones históricas.
- Los paquetes se agregan como un solo concepto con sus inclusiones descritas.
  No se desglosan servicios incluidos para evitar doble cobro.

## Logo

`listings.logo_path` es independiente de la galería. Se sube desde el
formulario de terraza, se guarda en el disco público con nombre aleatorio y se
previsualiza en el editor. Si no existe, el PDF usa el nombre de la terraza.

Al emitir, el logo se copia a almacenamiento privado y se referencia desde el
snapshot, de modo que reemplazar o eliminar el logo de la terraza no altera el
documento emitido.

## PDF y almacenamiento

- Renderizado con `dompdf/dompdf` en el servidor.
- Tamaño carta configurable (`bookings.quotes.paper`).
- Encabezado repetido, filas sin cortes, totales y leyenda:
  «Documento de cotización comercial. No constituye factura fiscal ni
  comprobante CFDI.»
- PDFs en disco privado `local`, ruta
  `quotes/{team_id}/{quote_id}/quote-{folio}.pdf`.
- Descarga autenticada con validación de cuenta y entitlement; no hay URLs
  públicas permanentes. La vista previa usa el mismo renderizador y cálculo.

## Inmutabilidad

- Solo los borradores se editan.
- Emitir congela snapshots y genera el PDF.
- Para cambios posteriores se duplica la cotización, que crea un nuevo
  borrador con folio nuevo y conserva el documento original.

## Editor universal de partidas

Los conceptos se editan en tarjetas independientes con identidad temporal
(`uid`) para que Vue no reutilice el DOM de otra partida al agregar, duplicar o
reordenar.

- **Agregar renta de terraza** crea o actualiza la partida automática de renta.
  Nunca agrega una segunda renta automática por doble clic. El precio ajustado
  manualmente se conserva al recalcular horas o días.
- **Agregar concepto** crea una partida neutral: tipo Servicio, concepto vacío,
  unidad Servicio, cantidad 1 y precio 0. No copia datos de la renta.
- **Agregar servicio existente** incorpora el snapshot del catálogo de
  `Listings` y permite ajustar los valores solo para esa cotización.
- **Duplicar** es una acción explícita y genera una partida independiente.
- Reordenar conserva todos los campos; el backend persiste `sort_order` según
  el orden visible.
- El backend valida tipo, concepto, unidad, cantidad positiva y precio no
  negativo, y recalcula importes/totales en centavos sin confiar en el cliente.

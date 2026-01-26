# Filament Blueprint: Documentos y Partidas

## Objetivo
Implementar administracion de documentos comerciales con partidas para:
- cotizacion
- nota_venta_renta
- nota_venta_venta
- factura_cfdi
- devolucion_renta
- devolucion_venta

Cada documento debe capturar totales, moneda, y datos CFDI basicos, y cada partida debe capturar cantidad, item, descripcion, subtotal, impuestos, total.

## Modelos
### Documentos
Campos:
- tipo (string enum, requerido)
- serie (string, opcional)
- folio (string, opcional)
- fecha_emision (datetime, opcional)
- moneda (string 3, default MXN)
- tipo_cambio (decimal 18,6, default 1)
- subtotal (decimal 18,8)
- impuestos_total (decimal 18,8)
- total (decimal 18,8)
- estatus (string, default borrador)
- uso_cfdi (string 10, opcional)
- forma_pago (string 5, opcional)
- metodo_pago (string 5, opcional)
- regimen_fiscal_receptor (string 5, opcional)
- rfc_emisor (string 13, opcional)
- rfc_receptor (string 13, opcional)
- razon_social_receptor (string, opcional)
- cfdi_uuid (string 36, opcional)
- documento_origen_id (FK documentos, opcional, nullOnDelete)

Relaciones:
- documentos.partidas -> documento_partidas
- documentos.documento_origen -> documentos
- documentos.documentos_relacionados -> documentos

### DocumentoPartidas
Campos:
- documento_id (FK documentos)
- cantidad (decimal 18,8)
- item (string)
- descripcion (string)
- valor_unitario (decimal 18,8)
- subtotal (decimal 18,8)
- impuestos (decimal 18,8)
- total (decimal 18,8)

Relaciones:
- documento_partidas.documento -> documentos

## Enums y validaciones
Enum TipoDocumento con los valores:
- cotizacion
- nota_venta_renta
- nota_venta_venta
- factura_cfdi
- devolucion_renta
- devolucion_venta

Validaciones clave:
- documentos.tipo requerido y en enum
- documentos.moneda length 3
- documentos.uso_cfdi/forma_pago/metodo_pago/regimen_fiscal_receptor con max length
- documentos.rfc_emisor y rfc_receptor max length 13
- partidas.cantidad, valor_unitario, subtotal, impuestos, total numericos

## Filament Resource: Documentos
### Form (Schema)
Secciones:
1) Encabezado
   - tipo (Select con enum, searchable)
   - serie
   - folio
   - fecha_emision
   - moneda
   - tipo_cambio
   - estatus
   - documento_origen_id (Select, searchable)

2) Totales
   - subtotal
   - impuestos_total
   - total

3) CFDI
   - uso_cfdi
   - forma_pago
   - metodo_pago
   - regimen_fiscal_receptor
   - rfc_emisor
   - rfc_receptor
   - razon_social_receptor
   - cfdi_uuid

4) Partidas
   - Repeater relationship partidas
     - cantidad
     - item
     - descripcion
     - valor_unitario
     - subtotal
     - impuestos
     - total

### Table
Columnas principales:
- tipo (label del enum)
- serie
- folio
- fecha_emision
- moneda
- tipo_cambio
- subtotal
- impuestos_total
- total
- estatus
- rfc_receptor
- cfdi_uuid
- documento_origen_id

Filtros:
- tipo (Select)

Acciones:
- editar, borrar masivo

## Navegacion y permisos
- Un recurso Documentos con paginas listar/crear/editar.
- Sin permisos especiales por ahora.

## Notas
- Los totales pueden mantenerse manuales o calcularse en el frontend si se requiere en una iteracion posterior.
- Devoluciones pueden vincularse con documento_origen_id.

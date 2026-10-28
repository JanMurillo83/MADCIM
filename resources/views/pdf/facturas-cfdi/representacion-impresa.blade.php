<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Representación impresa CFDI {{ $timbre['UUID'] }}</title>
    <style>
        @page { margin: 28px 30px 46px; }
        * { box-sizing: border-box; }
        body { color: #202936; font-family: DejaVu Sans, sans-serif; font-size: 8.5px; line-height: 1.38; }
        h1, h2, h3, p { margin: 0; }
        h1 { color: #123a5a; font-size: 17px; letter-spacing: .3px; }
        h2 { border-bottom: 1px solid #cbd5df; color: #123a5a; font-size: 10px; margin: 12px 0 6px; padding-bottom: 4px; text-transform: uppercase; }
        h3 { color: #334155; font-size: 8px; margin: 7px 0 3px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        .top td { vertical-align: top; }
        .doc-title { color: #123a5a; font-size: 11px; font-weight: bold; text-align: right; text-transform: uppercase; }
        .small { color: #526171; font-size: 7.5px; }
        .muted { color: #64748b; }
        .box { border: 1px solid #d7dee7; border-radius: 3px; padding: 7px 8px; }
        .parties { margin-top: 9px; }
        .parties td { vertical-align: top; width: 50%; }
        .parties td:first-child { padding-right: 5px; }
        .parties td:last-child { padding-left: 5px; }
        .label { color: #64748b; font-size: 7px; text-transform: uppercase; }
        .value { font-size: 8.5px; font-weight: bold; }
        .parties p { margin-top: 2px; }
        .meta { margin-top: 8px; }
        .meta td { padding: 2px 4px 2px 0; vertical-align: top; width: 25%; }
        .items { margin-top: 5px; }
        .items th { background: #123a5a; color: #fff; font-size: 7px; font-weight: bold; padding: 5px 4px; text-align: left; }
        .items td { border-bottom: 1px solid #dbe2e9; padding: 5px 4px; vertical-align: top; }
        .items .numeric { text-align: right; white-space: nowrap; }
        .items tr { page-break-inside: avoid; }
        .description { white-space: pre-wrap; word-wrap: break-word; }
        .tax-detail { color: #526171; font-size: 7px; margin-top: 3px; }
        .totals { margin-top: 8px; }
        .totals td { padding: 2px 4px; }
        .totals .amount { font-weight: bold; text-align: right; white-space: nowrap; width: 125px; }
        .grand-total td { border-top: 1px solid #123a5a; color: #123a5a; font-size: 10px; font-weight: bold; padding-top: 5px; }
        .related { margin-top: 8px; }
        .related th, .related td { border: 1px solid #dbe2e9; padding: 4px; text-align: left; }
        .related th { background: #eef3f7; }
        .stamp-grid { margin-top: 8px; table-layout: fixed; }
        .stamp-grid td { vertical-align: top; }
        .qr { height: 112px; width: 112px; }
        .seal { border: 1px solid #d7dee7; margin-top: 4px; padding: 5px; word-wrap: break-word; }
        .seal-value { font-family: DejaVu Sans Mono, monospace; font-size: 6px; line-height: 1.3; overflow-wrap: anywhere; word-break: break-all; word-wrap: break-word; }
        .footer { bottom: -29px; color: #64748b; font-size: 7px; position: fixed; text-align: center; width: 100%; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <h1>Comprobante fiscal digital por Internet</h1>
                <div class="small">CFDI versión {{ $comprobante['Version'] ?? '' }} · Tipo {{ $comprobante['TipoDeComprobante'] ?? '' }} · Exportación {{ $comprobante['Exportacion'] ?? '' }}</div>
            </td>
            <td class="doc-title">
                @if (! empty($comprobante['Serie']) || ! empty($comprobante['Folio']))
                    {{ trim(($comprobante['Serie'] ?? '').' '.($comprobante['Folio'] ?? '')) }}<br>
                @endif
                {{ $comprobante['Fecha'] ?? '' }}
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td><div class="box">
                <div class="label">Emisor</div>
                <p class="value">{{ $emisor['Nombre'] ?? '' }}</p>
                <p>RFC: {{ $emisor['Rfc'] ?? '' }}</p>
                <p>Régimen fiscal: {{ $emisor['RegimenFiscal'] ?? '' }}</p>
                <p>Lugar de expedición: {{ $comprobante['LugarExpedicion'] ?? '' }}</p>
            </div></td>
            <td><div class="box">
                <div class="label">Receptor</div>
                <p class="value">{{ $receptor['Nombre'] ?? '' }}</p>
                <p>RFC: {{ $receptor['Rfc'] ?? '' }}</p>
                <p>Código postal fiscal: {{ $receptor['DomicilioFiscalReceptor'] ?? '' }}</p>
                <p>Régimen fiscal: {{ $receptor['RegimenFiscalReceptor'] ?? '' }} · Uso CFDI: {{ $receptor['UsoCFDI'] ?? '' }}</p>
                @if (! empty($receptor['ResidenciaFiscal']) || ! empty($receptor['NumRegIdTrib']))
                    <p>Residencia fiscal: {{ $receptor['ResidenciaFiscal'] ?? '' }} · Registro fiscal: {{ $receptor['NumRegIdTrib'] ?? '' }}</p>
                @endif
            </div></td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><div class="label">Folio fiscal (UUID)</div><div>{{ $timbre['UUID'] }}</div></td>
            <td><div class="label">Forma de pago</div><div>{{ $comprobante['FormaPago'] ?? '' }}</div></td>
            <td><div class="label">Método de pago</div><div>{{ $comprobante['MetodoPago'] ?? '' }}</div></td>
            <td><div class="label">Moneda</div><div>{{ $comprobante['Moneda'] ?? '' }} @if (! empty($comprobante['TipoCambio'])) · TC {{ $comprobante['TipoCambio'] }} @endif</div></td>
        </tr>
        @if (! empty($comprobante['CondicionesDePago']))
            <tr><td colspan="4"><span class="label">Condiciones de pago</span><br>{{ $comprobante['CondicionesDePago'] }}</td></tr>
        @endif
    </table>

    <h2>Conceptos</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 12%">Clave / identificación</th>
                <th style="width: 8%">Cantidad</th>
                <th style="width: 9%">Unidad</th>
                <th>Descripción</th>
                <th class="numeric" style="width: 12%">Valor unitario</th>
                <th class="numeric" style="width: 12%">Importe</th>
                <th class="numeric" style="width: 11%">Descuento</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($conceptos as $concepto)
                <tr>
                    <td>{{ $concepto['clave_prod_serv'] }}@if ($concepto['no_identificacion'] !== '')<br><span class="small">{{ $concepto['no_identificacion'] }}</span>@endif</td>
                    <td>{{ $concepto['cantidad'] }}</td>
                    <td>{{ $concepto['unidad'] !== '' ? $concepto['unidad'] : $concepto['clave_unidad'] }}<br><span class="small">{{ $concepto['clave_unidad'] }}</span></td>
                    <td>
                        <div class="description">{{ $concepto['descripcion'] }}</div>
                        @foreach ($concepto['impuestos']['traslados'] as $impuesto)
                            <div class="tax-detail">Traslado {{ $impuesto['Impuesto'] }} · Base {{ $impuesto['Base'] }} · {{ $impuesto['TipoFactor'] }} {{ $impuesto['TasaOCuota'] ?? '' }} · Importe {{ $impuesto['Importe'] ?? '' }}</div>
                        @endforeach
                        @foreach ($concepto['impuestos']['retenciones'] as $impuesto)
                            <div class="tax-detail">Retención {{ $impuesto['Impuesto'] }} · Base {{ $impuesto['Base'] }} · {{ $impuesto['TipoFactor'] }} {{ $impuesto['TasaOCuota'] ?? '' }} · Importe {{ $impuesto['Importe'] ?? '' }}</div>
                        @endforeach
                    </td>
                    <td class="numeric">{{ $concepto['valor_unitario'] }}</td>
                    <td class="numeric">{{ $concepto['importe'] }}</td>
                    <td class="numeric">{{ $concepto['descuento'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($impuestos['traslados'] !== [] || $impuestos['retenciones'] !== [])
        <h2>Impuestos</h2>
        <table class="related">
            <thead><tr><th>Tipo</th><th>Impuesto</th><th>Base</th><th>Factor</th><th>Tasa o cuota</th><th>Importe</th></tr></thead>
            <tbody>
                @foreach ($impuestos['traslados'] as $impuesto)
                    <tr><td>Traslado</td><td>{{ $impuesto['Impuesto'] }}</td><td>{{ $impuesto['Base'] }}</td><td>{{ $impuesto['TipoFactor'] }}</td><td>{{ $impuesto['TasaOCuota'] ?? '' }}</td><td>{{ $impuesto['Importe'] ?? '' }}</td></tr>
                @endforeach
                @foreach ($impuestos['retenciones'] as $impuesto)
                    <tr><td>Retención</td><td>{{ $impuesto['Impuesto'] }}</td><td>{{ $impuesto['Base'] ?? '' }}</td><td>{{ $impuesto['TipoFactor'] ?? '' }}</td><td>{{ $impuesto['TasaOCuota'] ?? '' }}</td><td>{{ $impuesto['Importe'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($relacionados !== [])
        <h2>CFDI relacionados</h2>
        <table class="related"><thead><tr><th>Tipo de relación</th><th>UUID relacionado</th></tr></thead><tbody>
            @foreach ($relacionados as $relacionado)
                <tr><td>{{ $relacionado['tipo'] }}</td><td>{{ $relacionado['uuid'] }}</td></tr>
            @endforeach
        </tbody></table>
    @endif

    <table class="totals">
        <tr><td>Subtotal</td><td class="amount">{{ $comprobante['SubTotal'] ?? '0' }} {{ $comprobante['Moneda'] ?? '' }}</td></tr>
        @if (! empty($comprobante['Descuento']))
            <tr><td>Descuento</td><td class="amount">{{ $comprobante['Descuento'] }} {{ $comprobante['Moneda'] ?? '' }}</td></tr>
        @endif
        @if ($impuestos['total_trasladados'] !== '')
            <tr><td>Total de impuestos trasladados</td><td class="amount">{{ $impuestos['total_trasladados'] }} {{ $comprobante['Moneda'] ?? '' }}</td></tr>
        @endif
        @if ($impuestos['total_retenidos'] !== '')
            <tr><td>Total de impuestos retenidos</td><td class="amount">-{{ $impuestos['total_retenidos'] }} {{ $comprobante['Moneda'] ?? '' }}</td></tr>
        @endif
        <tr class="grand-total"><td>Total</td><td class="amount">{{ $comprobante['Total'] ?? '0' }} {{ $comprobante['Moneda'] ?? '' }}</td></tr>
    </table>

    <h2>Datos de certificación del SAT</h2>
    <table class="stamp-grid">
        <tr>
            <td style="width: 78%">
                <div><strong>Fecha de emisión:</strong> {{ $comprobante['Fecha'] ?? '' }}</div>
                <div><strong>Fecha de certificación:</strong> {{ $timbre['FechaTimbrado'] ?? '' }}</div>
                <div><strong>RFC del proveedor de certificación:</strong> {{ $timbre['RfcProvCertif'] ?? '' }}</div>
                <div><strong>No. de certificado del emisor:</strong> {{ $no_certificado_emisor }}</div>
                <div><strong>No. de certificado del SAT:</strong> {{ $timbre['NoCertificadoSAT'] ?? '' }}</div>
                <div class="seal"><div class="label">Sello digital del emisor</div><div class="seal-value">{{ $sello_emisor }}</div></div>
                <div class="seal"><div class="label">Sello digital del SAT</div><div class="seal-value">{{ $sello_sat }}</div></div>
            </td>
            <td style="padding-left: 8px; text-align: center; width: 22%">
                <img class="qr" src="{{ $qr }}" alt="Código QR de verificación SAT">
                <div class="small">Verificación del CFDI en el SAT</div>
            </td>
        </tr>
    </table>

    <div class="seal">
        <div class="label">Cadena original del complemento de certificación digital del SAT</div>
        <div class="seal-value">{{ $cadena_original_timbre }}</div>
    </div>

    <div class="footer">Este documento es una representación impresa de un CFDI · Página <span class="page-number"></span></div>
</body>
</html>

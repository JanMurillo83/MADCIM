<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte diario {{ $reporte['fecha'] }}</title>
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: Arial, sans-serif; font-size: 9px; }
        h1 { font-size: 18px; margin: 0; }
        h2 { border-bottom: 1px solid #9ca3af; font-size: 12px; margin: 16px 0 6px; padding-bottom: 3px; }
        h3 { font-size: 10px; margin: 10px 0 4px; }
        .header { border-bottom: 2px solid #166534; display: flex; justify-content: space-between; padding-bottom: 8px; }
        .muted { color: #6b7280; }
        .summary { border-collapse: collapse; width: 100%; }
        .summary td { border: 1px solid #d1d5db; padding: 5px; }
        .summary .label { font-weight: bold; width: 28%; }
        .summary .value { text-align: right; width: 22%; }
        .columns { display: table; table-layout: fixed; width: 100%; }
        .column { display: table-cell; padding-right: 8px; vertical-align: top; width: 50%; }
        .column:last-child { padding-left: 8px; padding-right: 0; }
        table.detail { border-collapse: collapse; margin-bottom: 8px; width: 100%; }
        .detail th { background: #166534; color: #fff; padding: 4px; text-align: left; }
        .detail td { border-bottom: 1px solid #e5e7eb; padding: 4px; }
        .right { text-align: right !important; }
        .empty { color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Reporte diario de operaciones</h1>
            <div class="muted">Fecha operativa: {{ \Carbon\Carbon::parse($reporte['fecha'])->format('d/m/Y') }}</div>
        </div>
        <div class="muted">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <h2>Resumen ejecutivo</h2>
    <table class="summary">
        <tbody>
            <tr>
                <td class="label">Notas de renta</td><td class="value">{{ $reporte['resumen']['notas_renta']['cantidad'] }} / ${{ number_format($reporte['resumen']['notas_renta']['total'], 2) }}</td>
                <td class="label">Notas de venta</td><td class="value">{{ $reporte['resumen']['notas_venta']['cantidad'] }} / ${{ number_format($reporte['resumen']['notas_venta']['total'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Notas de envío</td><td class="value">{{ $reporte['resumen']['notas_envio']['cantidad'] }}</td>
                <td class="label">Notas de devolución</td><td class="value">{{ $reporte['resumen']['notas_devolucion']['cantidad'] }} / {{ number_format($reporte['resumen']['notas_devolucion']['unidades'], 2) }} unidades</td>
            </tr>
            <tr>
                <td class="label">Productos vendidos</td><td class="value">{{ number_format($reporte['resumen']['productos_vendidos']['cantidad'], 2) }} / ${{ number_format($reporte['resumen']['productos_vendidos']['total'], 2) }}</td>
                <td class="label">Productos rentados</td><td class="value">{{ number_format($reporte['resumen']['productos_rentados']['cantidad'], 2) }} / ${{ number_format($reporte['resumen']['productos_rentados']['total'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Pagos recibidos</td><td class="value">{{ $reporte['resumen']['pagos']['cantidad'] }} / ${{ number_format($reporte['resumen']['pagos']['total'], 2) }}</td>
                <td class="label">Egresos</td><td class="value">{{ $reporte['resumen']['egresos']['cantidad'] }} / ${{ number_format($reporte['resumen']['egresos']['total'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Notas a crédito</td><td class="value">{{ $reporte['resumen']['notas_credito']['cantidad'] }} / ${{ number_format($reporte['resumen']['notas_credito']['total'], 2) }}</td>
                <td class="label">Fecha</td><td class="value">{{ $reporte['fecha'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Documentos del día</h2>
    <div class="columns">
        <div class="column">
            <h3>Notas de renta</h3>
            <table class="detail"><thead><tr><th>Folio</th><th>Cliente</th><th class="right">Total</th><th>Estatus</th></tr></thead><tbody>
                @forelse($reporte['rentas'] as $row)<tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="right">${{ number_format($row['total'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>@empty<tr><td class="empty" colspan="4">Sin registros</td></tr>@endforelse
            </tbody></table>
            <h3>Notas de envío</h3>
            <table class="detail"><thead><tr><th>Tipo</th><th>Folio</th><th>Cliente</th><th>Estatus</th></tr></thead><tbody>
                @forelse($reporte['envios'] as $row)<tr><td>{{ $row['tipo'] }}</td><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td>{{ $row['estatus'] }}</td></tr>@empty<tr><td class="empty" colspan="4">Sin registros</td></tr>@endforelse
            </tbody></table>
        </div>
        <div class="column">
            <h3>Notas de venta</h3>
            <table class="detail"><thead><tr><th>Folio</th><th>Cliente</th><th class="right">Total</th><th>Estatus</th></tr></thead><tbody>
                @forelse($reporte['ventas'] as $row)<tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="right">${{ number_format($row['total'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>@empty<tr><td class="empty" colspan="4">Sin registros</td></tr>@endforelse
            </tbody></table>
            <h3>Notas de devolución</h3>
            <table class="detail"><thead><tr><th>Folio</th><th>Cliente</th><th class="right">Unidades</th><th>Estatus</th></tr></thead><tbody>
                @forelse($reporte['devoluciones'] as $row)<tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="right">{{ number_format($row['unidades'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>@empty<tr><td class="empty" colspan="4">Sin registros</td></tr>@endforelse
            </tbody></table>
        </div>
    </div>

    <h2>Productos</h2>
    <div class="columns">
        <div class="column">
            <h3>Productos vendidos</h3>
            <table class="detail"><thead><tr><th>Clave</th><th>Producto</th><th class="right">Cantidad</th><th class="right">Total</th></tr></thead><tbody>
                @forelse($reporte['productos_vendidos'] as $row)<tr><td>{{ $row['clave'] }}</td><td>{{ $row['descripcion'] }}</td><td class="right">{{ number_format($row['cantidad'], 2) }}</td><td class="right">${{ number_format($row['total'], 2) }}</td></tr>@empty<tr><td class="empty" colspan="4">Sin registros</td></tr>@endforelse
            </tbody></table>
        </div>
        <div class="column">
            <h3>Productos rentados</h3>
            <table class="detail"><thead><tr><th>Clave</th><th>Producto</th><th class="right">Cantidad</th><th class="right">Importe renta</th></tr></thead><tbody>
                @forelse($reporte['productos_rentados'] as $row)<tr><td>{{ $row['clave'] }}</td><td>{{ $row['descripcion'] }}</td><td class="right">{{ number_format($row['cantidad'], 2) }}</td><td class="right">${{ number_format($row['importe_renta'], 2) }}</td></tr>@empty<tr><td class="empty" colspan="4">Sin registros</td></tr>@endforelse
            </tbody></table>
        </div>
    </div>

    <h2>Pagos, egresos y crédito</h2>
    <div class="columns">
        <div class="column">
            <h3>Pagos por forma de pago</h3>
            <table class="detail"><thead><tr><th>Forma</th><th class="right">Cantidad</th><th class="right">Total</th></tr></thead><tbody>
                @forelse($reporte['pagos'] as $row)<tr><td>{{ $row['forma_pago'] }}</td><td class="right">{{ $row['cantidad'] }}</td><td class="right">${{ number_format($row['total'], 2) }}</td></tr>@empty<tr><td class="empty" colspan="3">Sin registros</td></tr>@endforelse
            </tbody></table>
        </div>
        <div class="column">
            <h3>Egresos</h3>
            <table class="detail"><thead><tr><th>Concepto</th><th>Forma</th><th class="right">Importe</th></tr></thead><tbody>
                @forelse($reporte['egresos'] as $row)<tr><td>{{ $row['concepto'] }}</td><td>{{ $row['metodo_pago'] }}</td><td class="right">${{ number_format($row['importe'], 2) }}</td></tr>@empty<tr><td class="empty" colspan="3">Sin registros</td></tr>@endforelse
            </tbody></table>
        </div>
    </div>
    <h3>Notas a crédito</h3>
    <table class="detail"><thead><tr><th>Tipo</th><th>Folio</th><th>Cliente</th><th class="right">Total</th><th>Vencimiento</th><th>Estatus</th></tr></thead><tbody>
        @forelse($reporte['notas_credito'] as $row)<tr><td>{{ $row['tipo'] }}</td><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="right">${{ number_format($row['total'], 2) }}</td><td>{{ $row['vencimiento'] }}</td><td>{{ $row['estatus'] }}</td></tr>@empty<tr><td class="empty" colspan="6">Sin registros</td></tr>@endforelse
    </tbody></table>
</body>
</html>

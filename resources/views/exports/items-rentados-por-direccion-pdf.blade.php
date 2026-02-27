<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Items Rentados por Dirección - {{ $cliente->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 13px; margin: 15px 0 5px; padding: 5px; background: #f0f0f0; }
        .info { text-align: center; font-size: 10px; color: #666; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #4a5568; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .subtotal-row { background: #f7fafc; font-weight: bold; }
        .totales { margin-top: 20px; }
        .totales table { width: 50%; margin-left: auto; }
        .totales th { background: #2d3748; }
        .totales td { font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Items Rentados por Dirección de Entrega</h1>
    <p class="info">Cliente: <strong>{{ $cliente->nombre }}</strong> | Fecha: {{ now()->format('d/m/Y H:i') }}</p>

    @foreach($itemsAgrupados as $direccionId => $itemsGrupo)
        @php
            $direccion = $itemsGrupo->first()->notaVentaRenta?->direccionEntrega;
            $direccionNombre = $direccion ? $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa : $itemsGrupo->first()->cliente_direccion;
            $subtotalRenta = $itemsGrupo->sum('importe_renta');
            $subtotalVenta = $itemsGrupo->sum(fn($i) => ($i->producto?->precio_venta ?? 0) * $i->cantidad);
        @endphp
        <h2>📍 {{ $direccionNombre ?? 'Sin dirección asignada' }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Producto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Días Renta</th>
                    <th class="text-right">Importe Renta</th>
                    <th class="text-right">Precio Venta Unit.</th>
                    <th class="text-right">Total Precio Venta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itemsGrupo as $item)
                    @php $precioVenta = $item->producto?->precio_venta ?? 0; @endphp
                    <tr>
                        <td>{{ $item->producto?->clave ?? 'N/A' }}</td>
                        <td>{{ $item->producto?->descripcion ?? 'N/A' }}</td>
                        <td class="text-center">{{ $item->cantidad }}</td>
                        <td class="text-center">{{ $item->dias_renta }}</td>
                        <td class="text-right">${{ number_format($item->importe_renta, 2) }}</td>
                        <td class="text-right">${{ number_format($precioVenta, 2) }}</td>
                        <td class="text-right">${{ number_format($precioVenta * $item->cantidad, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="2">Subtotal dirección</td>
                    <td class="text-center">{{ $itemsGrupo->sum('cantidad') }}</td>
                    <td></td>
                    <td class="text-right">${{ number_format($subtotalRenta, 2) }}</td>
                    <td></td>
                    <td class="text-right">${{ number_format($subtotalVenta, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <div class="totales">
        <table>
            <tr>
                <th>Concepto</th>
                <th class="text-right">Importe</th>
            </tr>
            <tr>
                <td>Total Items Rentados</td>
                <td class="text-right">{{ $items->sum('cantidad') }}</td>
            </tr>
            <tr>
                <td>Total Importe Renta</td>
                <td class="text-right">${{ number_format($totalImporteRenta, 2) }}</td>
            </tr>
            <tr>
                <td>Equivalente Precio Venta</td>
                <td class="text-right">${{ number_format($totalPrecioVenta, 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>

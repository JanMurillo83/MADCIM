<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos Rentados por Dirección - {{ $cliente->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #202124; }
        .header { width: 100%; margin-bottom: 12px; }
        .logo { width: 170px; height: auto; }
        .header-title { text-align: center; vertical-align: middle; }
        h1 { font-size: 17px; margin: 0 0 5px; }
        .info { margin: 0; font-size: 10px; color: #555; }
        h2 { font-size: 11px; margin: 14px 0 4px; padding: 5px 7px; background: #edf0f3; border-bottom: 1px solid #d8dde3; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #46566d; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
        td { padding: 4px 6px; border-bottom: 1px solid #e1e5e9; font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .subtotal-row { background: #f2f4f6; font-weight: bold; }
        .totales { width: 50%; margin: 16px 0 0 auto; }
        .totales th { background: #46566d; }
        .totales td { font-weight: bold; font-size: 10px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 25%; border: 0; padding: 0;">
                <img class="logo" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/Logo_n.png'))) }}" alt="MADCIM">
            </td>
            <td class="header-title" style="width: 75%; border: 0; padding: 0;">
                <h1>Madera Rentada por Obra</h1>
                <p class="info">Cliente: <strong>{{ $cliente->nombre }}</strong> | Fecha: {{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>

    @foreach($itemsAgrupados as $direccionId => $itemsGrupo)
        @php
            $direccion = $itemsGrupo->first()->notaVentaRenta?->direccionEntrega;
            $direccionNombre = $direccion ? $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa : $itemsGrupo->first()->cliente_direccion;
            $subtotalRenta = $itemsGrupo->sum('importe_renta');
            $subtotalVenta = $itemsGrupo->sum(fn($i) => ($i->producto?->precio_venta ?? 0) * $i->cantidad);
        @endphp
        @php
            $subtotalCantidad = $itemsGrupo->sum('cantidad');
            $subtotalDevueltos = $itemsGrupo->sum('cantidad_devuelta');
            $subtotalPendientes = max(0, $subtotalCantidad - $subtotalDevueltos);
        @endphp
        <h2>{{ $direccionNombre ?? 'Sin dirección asignada' }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Producto</th>
                    <th class="text-center">Madera Enviada</th>
                    <th class="text-center">Devuelta</th>
                    <th class="text-center">Pendientes</th>
                    <th class="text-right">Importe Renta</th>
                    <th class="text-right">Precio Venta Unit.</th>
                    <th class="text-right">Total Precio Venta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itemsGrupo as $item)
                    @php
                        $precioVenta = $item->producto?->precio_venta ?? 0;
                        $cantidad = (float) $item->cantidad;
                        $devueltos = (float) $item->cantidad_devuelta;
                    @endphp
                    <tr>
                        <td>{{ $item->producto?->clave ?? 'N/A' }}</td>
                        <td>{{ $item->producto?->descripcion ?? 'N/A' }}</td>
                        <td class="text-center">{{ $cantidad }}</td>
                        <td class="text-center">{{ $devueltos }}</td>
                        <td class="text-center">{{ max(0, $cantidad - $devueltos) }}</td>
                        <td class="text-right">${{ number_format($item->importe_renta, 2) }}</td>
                        <td class="text-right">${{ number_format($precioVenta, 2) }}</td>
                        <td class="text-right">${{ number_format($precioVenta * $cantidad, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="2">Subtotal dirección</td>
                    <td class="text-center">{{ $subtotalCantidad }}</td>
                    <td class="text-center">{{ $subtotalDevueltos }}</td>
                    <td class="text-center">{{ $subtotalPendientes }}</td>
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
                <td>Total Productos Rentados</td>
                <td class="text-right">{{ $items->sum('cantidad') }}</td>
            </tr>
            <tr>
                <td>Total Importe Renta</td>
                <td class="text-right">${{ number_format($totalImporteRenta, 2) }}</td>
            </tr>
            <tr>
                <td>Importe a Cobrar por Faltante de Madera</td>
                <td class="text-right">${{ number_format($itemsAgrupados->flatten(1)->sum(function ($item) {
                    $pendientes = max(0, (float) $item->cantidad - (float) $item->cantidad_devuelta);
                    return $pendientes * (float) ($item->producto?->precio_venta ?? 0);
                }), 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>

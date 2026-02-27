<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 6px; }
        .info { text-align: center; font-size: 10px; color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4a5568; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
        .totales { margin-top: 12px; }
        .totales table { width: 45%; margin-left: auto; }
        .totales th { background: #2d3748; }
        .totales td { font-weight: bold; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Inventario</h1>
    <p class="info">Generado: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Clave</th>
                <th>Producto</th>
                <th>Linea</th>
                <th>Grupo</th>
                <th class="text-right">Existencia</th>
                <th class="text-right">Precio venta</th>
                <th class="text-right">Valor inventario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $row)
                <tr>
                    <td>{{ $row['clave'] }}</td>
                    <td>{{ $row['descripcion'] }}</td>
                    <td>{{ $row['linea'] }}</td>
                    <td>{{ $row['grupo'] }}</td>
                    <td class="text-right">{{ number_format($row['existencia'], 2) }}</td>
                    <td class="text-right">${{ number_format($row['precio_venta'], 2) }}</td>
                    <td class="text-right">${{ number_format($row['valor'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totales">
        <table>
            <tr>
                <th>Concepto</th>
                <th class="text-right">Importe</th>
            </tr>
            <tr>
                <td>Total existencia</td>
                <td class="text-right">{{ number_format($totals['existencia'], 2) }}</td>
            </tr>
            <tr>
                <td>Valor inventario</td>
                <td class="text-right">${{ number_format($totals['valor'], 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>

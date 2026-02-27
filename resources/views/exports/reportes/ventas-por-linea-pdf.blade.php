<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas por Linea</title>
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
    <h1>Ventas por Linea</h1>
    <p class="info">Periodo: {{ $fecha_inicio }} a {{ $fecha_fin }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Linea</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $row)
                <tr>
                    <td>{{ $row['linea'] }}</td>
                    <td class="text-right">{{ number_format($row['cantidad'], 2) }}</td>
                    <td class="text-right">${{ number_format($row['subtotal'], 2) }}</td>
                    <td class="text-right">${{ number_format($row['total'], 2) }}</td>
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
                <td>Cantidad total</td>
                <td class="text-right">{{ number_format($totals['cantidad'], 2) }}</td>
            </tr>
            <tr>
                <td>Subtotal</td>
                <td class="text-right">${{ number_format($totals['subtotal'], 2) }}</td>
            </tr>
            <tr>
                <td>Total</td>
                <td class="text-right">${{ number_format($totals['total'], 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>

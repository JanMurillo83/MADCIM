<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ordenes por Estatus</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 6px; }
        .info { text-align: center; font-size: 10px; color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4a5568; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Ordenes de Compra por Estatus</h1>
    <p class="info">Periodo: {{ $fecha_inicio }} a {{ $fecha_fin }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Serie/Folio</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th class="text-right">Total</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordenes as $orden)
                <tr>
                    <td>{{ $orden->serie }}-{{ $orden->folio }}</td>
                    <td>{{ optional($orden->fecha_emision)->format('Y-m-d') }}</td>
                    <td>{{ $orden->proveedor?->nombre ?? 'N/A' }}</td>
                    <td class="text-right">${{ number_format($orden->total, 2) }}</td>
                    <td>{{ $orden->estatus }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

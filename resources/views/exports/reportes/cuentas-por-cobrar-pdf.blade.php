<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuentas por Cobrar</title>
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
    <h1>Cuentas por Cobrar</h1>
    <p class="info">Periodo: {{ $fecha_inicio }} a {{ $fecha_fin }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Serie/Folio</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th class="text-right">Total</th>
                <th class="text-right">Saldo pendiente</th>
                <th>Estatus</th>
                <th class="text-right">Dias vencido</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuentas as $row)
                <tr>
                    <td>{{ $row['tipo'] }}</td>
                    <td>{{ $row['serie_folio'] }}</td>
                    <td>{{ $row['fecha_emision'] }}</td>
                    <td>{{ $row['cliente'] }}</td>
                    <td class="text-right">${{ number_format($row['total'], 2) }}</td>
                    <td class="text-right">${{ number_format($row['saldo_pendiente'], 2) }}</td>
                    <td>{{ $row['estatus'] }}</td>
                    <td class="text-right">{{ $row['dias_vencido'] }}</td>
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
                <td>Saldo pendiente total</td>
                <td class="text-right">${{ number_format($totals['saldo'], 2) }}</td>
            </tr>
            <tr>
                <td>Documentos</td>
                <td class="text-right">{{ $totals['documentos'] }}</td>
            </tr>
        </table>
    </div>
</body>
</html>

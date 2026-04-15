<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 24px;
            color: #1d4ed8;
        }

        .meta {
            margin-bottom: 12px;
        }

        .meta-row {
            margin-bottom: 3px;
        }

        .group-title {
            margin: 14px 0 6px;
            font-size: 13px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #4b5563;
            padding: 6px;
        }

        thead th {
            background: #dfe8d8;
            font-weight: bold;
        }

        .num {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>

    @forelse($grupos as $index => $grupo)
        <div class="group-title">Grupo {{ $index + 1 }}</div>

        <div class="meta">
            <div class="meta-row"><strong>Cliente:</strong> {{ $grupo['resumen']['cliente'] }}</div>
            <div class="meta-row"><strong>Nota Origen:</strong> {{ $grupo['resumen']['nota_origen'] }}</div>
            <div class="meta-row"><strong>Tel:</strong> {{ $grupo['resumen']['telefono'] }}</div>
            <div class="meta-row"><strong>Direccion de Obra:</strong> {{ $grupo['resumen']['direccion_obra'] }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>clave</th>
                    <th>producto</th>
                    <th>Cantidad Enviada</th>
                    <th>Canti. Devuelta</th>
                    <th>Cant. Pend. X devolver</th>
                    <th>Importe Enviado</th>
                    <th>Importe Devuelto</th>
                    <th>Importe x Cobrar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupo['filas'] as $row)
                    <tr>
                        <td>{{ $row['clave'] }}</td>
                        <td>{{ $row['producto'] }}</td>
                        <td class="num">{{ number_format($row['cantidad_enviada'], 2) }}</td>
                        <td class="num">{{ number_format($row['cantidad_devuelta'], 2) }}</td>
                        <td class="num">{{ number_format($row['cantidad_pendiente'], 2) }}</td>
                        <td class="num">${{ number_format($row['importe_enviado'], 2) }}</td>
                        <td class="num">${{ number_format($row['importe_devuelto'], 2) }}</td>
                        <td class="num">${{ number_format($row['importe_cobrar'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><strong>TOTAL</strong></td>
                    <td></td>
                    <td class="num"><strong>{{ number_format($grupo['totales']['cantidad_enviada'], 2) }}</strong></td>
                    <td class="num"><strong>{{ number_format($grupo['totales']['cantidad_devuelta'], 2) }}</strong></td>
                    <td class="num"><strong>{{ number_format($grupo['totales']['cantidad_pendiente'], 2) }}</strong></td>
                    <td class="num"><strong>${{ number_format($grupo['totales']['importe_enviado'], 2) }}</strong></td>
                    <td class="num"><strong>${{ number_format($grupo['totales']['importe_devuelto'], 2) }}</strong></td>
                    <td class="num"><strong>${{ number_format($grupo['totales']['importe_cobrar'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @empty
        <table>
            <tbody>
                <tr>
                    <td style="text-align: center;">Sin datos para los filtros seleccionados.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if(!empty($totalGeneral))
        <div class="group-title">Total General</div>
        <table>
            <thead>
                <tr>
                    <th>Cant. Enviada</th>
                    <th>Cant. Devuelta</th>
                    <th>Cant. Pendiente</th>
                    <th>Importe Enviado</th>
                    <th>Importe Devuelto</th>
                    <th>Importe x Cobrar</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="num">{{ number_format($totalGeneral['cantidad_enviada'], 2) }}</td>
                    <td class="num">{{ number_format($totalGeneral['cantidad_devuelta'], 2) }}</td>
                    <td class="num">{{ number_format($totalGeneral['cantidad_pendiente'], 2) }}</td>
                    <td class="num">${{ number_format($totalGeneral['importe_enviado'], 2) }}</td>
                    <td class="num">${{ number_format($totalGeneral['importe_devuelto'], 2) }}</td>
                    <td class="num">${{ number_format($totalGeneral['importe_cobrar'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>

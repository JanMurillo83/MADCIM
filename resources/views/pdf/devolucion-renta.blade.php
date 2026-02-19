<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Devolución - {{ $nota->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-section p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
        .totals p {
            margin: 5px 0;
            font-size: 14px;
        }
        .totals .final {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        .warning {
            background-color: #fff3cd;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TICKET DE DEVOLUCIÓN</h1>
        <p>Nota de Venta: {{ $nota->folio }}</p>
        <p>Fecha: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info-section">
        <p><strong>Cliente:</strong> {{ $nota->cliente->nombre }}</p>
        <p><strong>Fecha de Renta:</strong> {{ $nota->fecha_emision->format('d/m/Y') }}</p>
    </div>

    <h3>Items Devueltos</h3>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-center">Rentada</th>
                <th class="text-center">Devuelta</th>
                <th class="text-center">Faltante</th>
                <th class="text-right">Descuento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item['producto'] }}</td>
                    <td class="text-center">{{ $item['cantidad_rentada'] }}</td>
                    <td class="text-center">{{ $item['cantidad_devuelta'] }}</td>
                    <td class="text-center">{{ $item['cantidad_faltante'] }}</td>
                    <td class="text-right">${{ number_format($item['descuento'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($total_descuento > 0)
        <div class="warning">
            <strong>⚠ ATENCIÓN:</strong> Se han detectado faltantes. El depósito será ajustado según el precio de venta de los items faltantes.
        </div>
    @endif

    <div class="totals">
        <p><strong>Depósito Inicial:</strong> <span style="float: right;">${{ number_format($deposito_inicial, 2) }}</span></p>
        <p><strong>Descuento por Faltantes:</strong> <span style="float: right;">-${{ number_format($total_descuento, 2) }}</span></p>
        <p class="final"><strong>Depósito a Devolver:</strong> <span style="float: right;">${{ number_format($deposito_devolver, 2) }}</span></p>
    </div>

    <div class="footer">
        <p>──────────────────────────────────</p>
        <p>Firma del Cliente</p>
        <p style="margin-top: 20px;">Gracias por su preferencia</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>

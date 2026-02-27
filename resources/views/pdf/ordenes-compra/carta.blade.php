<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra {{ $orden->serie }}-{{ $orden->folio }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #333;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9pt;
            color: #666;
            line-height: 1.4;
        }
        .document-title {
            font-size: 20pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .document-number {
            font-size: 14pt;
            color: #666;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        .info-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
        }
        .info-title {
            font-weight: bold;
            font-size: 12pt;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 5px;
        }
        .info-row {
            margin: 8px 0;
            line-height: 1.5;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table thead {
            background: #2c3e50;
            color: white;
        }
        .items-table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }
        .items-table th.text-right,
        .items-table td.text-right {
            text-align: right;
        }
        .items-table th.text-center,
        .items-table td.text-center {
            text-align: center;
        }
        .items-table tbody tr {
            border-bottom: 1px solid #dee2e6;
        }
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .items-table td {
            padding: 10px 8px;
            font-size: 10pt;
        }
        .totals-section {
            width: 100%;
            margin-top: 20px;
        }
        .totals-table {
            width: 350px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }
        .totals-table .label-col {
            font-weight: bold;
            background: #f8f9fa;
            width: 60%;
        }
        .totals-table .value-col {
            text-align: right;
            width: 40%;
        }
        .totals-table .grand-total {
            background: #2c3e50;
            color: white;
            font-size: 12pt;
            font-weight: bold;
        }
        .observations {
            clear: both;
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #2c3e50;
        }
        .observations-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="company-name">MADCIM</div>
            <div class="company-info">
                Renta y Venta de Cimbra<br>
            </div>
        </div>
        <div class="header-right">
            <div class="document-title">ORDEN DE COMPRA</div>
            <div class="document-number">{{ $orden->serie }}-{{ $orden->folio }}</div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-left">
            <div class="info-box">
                <div class="info-title">Proveedor</div>
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    {{ $orden->proveedor->nombre ?? 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">RFC:</span>
                    {{ $orden->proveedor->rfc ?? 'N/A' }}
                </div>
            </div>
        </div>
        <div class="info-right">
            <div class="info-box">
                <div class="info-title">Detalle</div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    {{ optional($orden->fecha_emision)->format('d/m/Y') }}
                </div>
                <div class="info-row">
                    <span class="info-label">Estatus:</span>
                    {{ $orden->estatus }}
                </div>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripcion</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">IVA</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orden->partidas as $partida)
                <tr>
                    <td>{{ $partida->descripcion }}</td>
                    <td class="text-center">{{ number_format($partida->cantidad, 2) }}</td>
                    <td class="text-right">${{ number_format($partida->precio_unitario, 2) }}</td>
                    <td class="text-right">${{ number_format($partida->subtotal, 2) }}</td>
                    <td class="text-right">${{ number_format($partida->impuestos, 2) }}</td>
                    <td class="text-right">${{ number_format($partida->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section clearfix">
        <table class="totals-table">
            <tr>
                <td class="label-col">Subtotal</td>
                <td class="value-col">${{ number_format($orden->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label-col">IVA</td>
                <td class="value-col">${{ number_format($orden->impuestos_total, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label-col">TOTAL</td>
                <td class="value-col">${{ number_format($orden->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($orden->observaciones)
    <div class="observations">
        <div class="observations-title">Observaciones</div>
        {{ $orden->observaciones }}
    </div>
    @endif

    <div class="footer">
        Documento generado por el sistema MADCIM.
    </div>
</body>
</html>

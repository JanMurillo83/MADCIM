<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Venta Renta {{ $notaVenta->serie }}-{{ $notaVenta->folio }}</title>
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
            font-size: 18pt;
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
        .rental-items-section {
            margin-bottom: 30px;
            padding: 15px;
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
        }
        .rental-items-title {
            font-weight: bold;
            font-size: 12pt;
            color: #856404;
            margin-bottom: 15px;
            text-align: center;
            text-transform: uppercase;
        }
        .rental-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .rental-items-table thead {
            background: #ffc107;
            color: #000;
        }
        .rental-items-table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            border: 1px solid #ffcd39;
        }
        .rental-items-table td {
            padding: 8px;
            font-size: 9pt;
            border: 1px solid #ffe69c;
        }
        .rental-items-table tbody tr:nth-child(even) {
            background: #fff8e1;
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
        .footer {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .alert-box {
            margin-top: 20px;
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            color: #721c24;
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
                <!-- Agregar aqu� direcci�n, tel�fono, etc. si est� disponible -->
            </div>
        </div>
        <div class="header-right">
            <div class="document-title">NOTA DE VENTA RENTA</div>
            <div class="document-number">{{ $notaVenta->serie }}-{{ $notaVenta->folio }}</div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-left">
            <div class="info-box">
                <div class="info-title">DATOS DEL CLIENTE</div>
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    {{ $notaVenta->cliente->nombre ?? 'N/A' }}
                </div>
                @if($notaVenta->cliente && $notaVenta->cliente->rfc)
                <div class="info-row">
                    <span class="info-label">RFC:</span>
                    {{ $notaVenta->cliente->rfc }}
                </div>
                @endif
                @if($notaVenta->cliente && $notaVenta->cliente->direccion)
                <div class="info-row">
                    <span class="info-label">Direcci�n:</span>
                    {{ $notaVenta->cliente->direccion }}
                </div>
                @endif
                @if($notaVenta->cliente && $notaVenta->cliente->telefono)
                <div class="info-row">
                    <span class="info-label">Tel�fono:</span>
                    {{ $notaVenta->cliente->telefono }}
                </div>
                @endif
            </div>
        </div>
        <div class="info-right">
            <div class="info-box">
                <div class="info-title">INFORMACI�N</div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    {{ $notaVenta->fecha_emision->format('d/m/Y H:i') }}
                </div>
                <div class="info-row">
                    <span class="info-label">Estatus:</span>
                    {{ $notaVenta->estatus }}
                </div>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 8%;">CANT.</th>
                <th style="width: 15%;">CLAVE</th>
                <th style="width: 37%;">DESCRIPCI�N</th>
                <th class="text-right" style="width: 13%;">P. UNIT.</th>
                <th class="text-right" style="width: 13%;">SUBTOTAL</th>
                <th class="text-right" style="width: 14%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notaVenta->partidas as $partida)
            <tr>
                <td class="text-center">{{ $partida->cantidad }}</td>
                <td>{{ $partida->item }}</td>
                <td>{{ $partida->descripcion }}</td>
                <td class="text-right">${{ number_format($partida->valor_unitario, 2) }}</td>
                <td class="text-right">${{ number_format($partida->subtotal, 2) }}</td>
                <td class="text-right">${{ number_format($partida->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($notaVenta->registrosRenta && $notaVenta->registrosRenta->count() > 0)
    <div class="rental-items-section">
        <div class="rental-items-title">Detalle de Items en Renta</div>
        <table class="rental-items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">Cant.</th>
                    <th style="width: 45%;">Descripci�n</th>
                    <th style="width: 10%;" class="text-center">D�as</th>
                    <th style="width: 20%;" class="text-center">Fecha Renta</th>
                    <th style="width: 20%;" class="text-center">Vencimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notaVenta->registrosRenta as $registro)
                <tr>
                    <td class="text-center">{{ $registro->cantidad }}</td>
                    <td>{{ $registro->observaciones }}</td>
                    <td class="text-center">{{ $registro->dias_renta }}</td>
                    <td class="text-center">{{ $registro->fecha_renta->format('d/m/Y') }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ $registro->fecha_vencimiento->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="totals-section clearfix">
        <table class="totals-table">
            <tr>
                <td class="label-col">Subtotal Partidas:</td>
                <td class="value-col">${{ number_format($notaVenta->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label-col">IVA (16%):</td>
                <td class="value-col">${{ number_format($notaVenta->impuestos_total, 2) }}</td>
            </tr>
            <tr style="background: #e3f2fd;">
                <td class="label-col">Depósito:</td>
                <td class="value-col">${{ number_format($notaVenta->deposito, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label-col">TOTAL:</td>
                <td class="value-col">${{ number_format($notaVenta->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="alert-box">
        <strong>IMPORTANTE:</strong> Favor de verificar la devoluci�n de todos los materiales en las fechas indicadas.
        El incumplimiento en la fecha de devoluci�n generar� cargos adicionales.
    </div>

    <div class="footer">
        <p>�Gracias por su preferencia!</p>
    </div>
</body>
</html>

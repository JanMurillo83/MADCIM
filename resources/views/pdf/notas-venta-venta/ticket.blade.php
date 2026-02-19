<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización {{ $notaVenta->serie }}-{{ $notaVenta->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-section {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .label {
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .items-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        .item-row {
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        .item-desc {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }
        .totals {
            margin-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .total-label {
            font-weight: bold;
        }
        .grand-total {
            font-size: 12px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">MADCIM</div>
        <div>NOTA DE VENTA</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Folio:</span>
            <span>{{ $notaVenta->serie }}-{{ $notaVenta->folio }}</span>
        </div>
        <div class="info-row">
            <span class="label">Fecha:</span>
            <span>{{ $notaVenta->fecha_emision->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="label">Cliente:</span>
            <span>{{ $notaVenta->cliente->nombre }}</span>
        </div>
    </div>

    <div class="items-table">
        <div class="items-header">
            <span>CONCEPTO</span>
            <span>TOTAL</span>
        </div>
        @foreach($notaVenta->partidas as $partida)
        <div class="item-row">
            <div class="item-desc">{{ $partida->descripcion }}</div>
            <div class="item-details">
                <span>{{ $partida->cantidad }} x ${{ number_format($partida->valor_unitario, 2) }}</span>
                <span>${{ number_format($partida->total, 2) }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="totals">
        <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span>${{ number_format($notaVenta->subtotal, 2) }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">IVA (16%):</span>
            <span>${{ number_format($notaVenta->impuestos_total, 2) }}</span>
        </div>
        <div class="total-row grand-total">
            <span class="total-label">TOTAL:</span>
            <span>${{ number_format($notaVenta->total, 2) }}</span>
        </div>
    </div>

    <div class="footer">
        <div>¡Gracias por su preferencia!</div>
        @if($notaVenta->observaciones)
        <div style="margin-top: 10px; text-align: left;">
            <strong>Observaciones:</strong><br>
            {{ $notaVenta->observaciones }}
        </div>
        @endif
    </div>
</body>
</html>

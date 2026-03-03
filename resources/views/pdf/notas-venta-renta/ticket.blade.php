<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Venta Renta {{ $notaVenta->serie }}-{{ $notaVenta->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            margin: 0;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm 7mm 5mm 5mm;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 16px;
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
            flex-wrap: wrap;
            gap: 2px;
        }
        .info-row-block {
            flex-direction: column;
            align-items: flex-start;
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
            font-size: 11px;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-top: 15px;
            margin-bottom: 5px;
            border-bottom: 2px solid #000;
            padding-bottom: 3px;
        }
        .rental-items {
            margin-bottom: 10px;
        }
        .rental-item {
            padding: 3px 0;
            font-size: 11px;
            border-bottom: 1px dotted #ddd;
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
            font-size: 14px;
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
            font-size: 10px;
            margin-left: 0.1rem;
            margin-right: 1rem;
            max-width: 90%;
        }
        .legend {
            margin-top: 6px;
            font-size: 9px;
            text-align: justify;
            text-justify: inter-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">MADCIM</div>
        <div>NOTA DE RENTA</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Folio:</span>
            <span>{{ $notaVenta->serie }}-{{ $notaVenta->folio }}</span>
        </div>
        <div class="info-row">
            <span class="label">Fecha:</span>
            <span>{{ $notaVenta->fecha_emision->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="label">Cliente:</span>
            <span>{{ $notaVenta->cliente->nombre ?? 'N/A' }}</span>
        </div>
        @if($notaVenta->cliente && $notaVenta->cliente->telefono)
        <div class="info-row">
            <span class="label">Tel�fono:</span>
            <span>{{ $notaVenta->cliente->telefono }}</span>
        </div>
        @endif
        @if($notaVenta->direccionEntrega)
        <div class="info-row info-row-block">
            <span class="label">Direccion de entrega:</span>
            <span>{{ $notaVenta->direccionEntrega->direccion_completa }}</span>
        </div>
        @endif
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
                <span>{{ number_format($partida->cantidad, 2) }} x ${{ number_format($partida->valor_unitario, 2) }}</span>
                <span>${{ number_format($partida->total, 2) }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="totals">
        @php
            $impuestosTotal = $notaVenta->impuestos_total ?? 0;
            $subtotalConImpuestos = ($notaVenta->subtotal ?? 0) + $impuestosTotal;
        @endphp
        <div class="total-row">
            <span class="total-label">Subtotal Partidas:</span>
            <span>${{ number_format($subtotalConImpuestos, 2) }}</span>
        </div>
        <div class="total-row" style="background: #f0f0f0;">
            <span class="total-label">Depósito:</span>
            <span>${{ number_format($notaVenta->deposito, 2) }}</span>
        </div>
        <div class="total-row grand-total">
            <span class="total-label">TOTAL:</span>
            <span>${{ number_format($notaVenta->total, 2) }}</span>
        </div>
    </div>

    <div class="footer">
        <div>�Gracias por su preferencia!</div>
        <div class="legend">
            Recibi de MADERERIA MADCIM la MADERA o EQUIPO aquí especificada (o) en calidad de ARRENDAMIENTO por el tiempo especificado en este documento. Asi mismo me obligo a cuidarla (o) y devolverla (o) en buen estado en tiempo y forma, de lo contrario en caso de EXTRAVIARLA (o) o NO DEVOLVERLA (o) me OBLIGO a pagar el monto en dinero que cubra el valor de la  MADERA o EQUIPO NO DEVUELTO.
        </div>
    </div>
</body>
</html>

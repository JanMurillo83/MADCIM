<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre Devolución - Envío {{ $notaEnvio->serie ?? '' }}{{ $notaEnvio->serie ? '-' : '' }}{{ $notaEnvio->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
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
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
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
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-top: 10px;
            margin-bottom: 5px;
            border-bottom: 2px solid #000;
            padding-bottom: 3px;
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
        .totals-section {
            margin-top: 10px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .total-row.grand {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .total-row.highlight {
            font-size: 13px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 10px;
        }
        .nota-venta-info {
            margin-top: 8px;
            padding: 5px;
            border: 1px solid #000;
            text-align: center;
            font-size: 10px;
        }
        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #333;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            gap: 10px;
            z-index: 9999;
            align-items: center;
        }
        .toolbar button, .toolbar a {
            background: #4CAF50;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }
        .toolbar a.back {
            background: #666;
        }
        @media print {
            .toolbar { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print();">🖨️ Imprimir</button>
        <a href="{{ url('/notas-envio/notas-envios') }}" class="back">← Volver a Notas de Envío</a>
    </div>

    <div style="margin-top: 50px;">
        <div class="header">
            <div class="company-name">MADCIM</div>
            <div class="doc-title">CIERRE DE DEVOLUCIÓN</div>
            <div style="margin-top: 5px;">Fecha: {{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="label">Nota Envío:</span>
                <span>{{ $notaEnvio->serie ?? '' }}{{ $notaEnvio->serie ? '-' : '' }}{{ $notaEnvio->folio }}</span>
            </div>
            <div class="info-row">
                <span class="label">Nota Renta:</span>
                <span>{{ $notaRenta->serie ?? '' }}-{{ $notaRenta->folio ?? '' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Cliente:</span>
                <span>{{ $notaEnvio->cliente->nombre ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha Envío:</span>
                <span>{{ $notaEnvio->fecha_emision ? $notaEnvio->fecha_emision->format('d/m/Y') : '-' }}</span>
            </div>
        </div>

        <div class="section-title">DETALLE DE PARTIDAS</div>
        @foreach($items as $item)
            <div class="item-row">
                <div class="item-desc">{{ $item->producto->descripcion ?? ($item->observaciones ?? 'Item') }}</div>
                <div class="item-details">
                    <span>Rentado: {{ (float)$item->cantidad }}</span>
                    <span>Devuelto: {{ (float)$item->cantidad_devuelta }}</span>
                    <span>Faltante: {{ (float)$item->cantidad - (float)$item->cantidad_devuelta }}</span>
                </div>
                @if(((float)$item->cantidad - (float)$item->cantidad_devuelta) > 0)
                    <div style="font-size: 9px; color: #666;">
                        Cargo: ${{ number_format(((float)$item->cantidad - (float)$item->cantidad_devuelta) * ($item->producto ? (float)$item->producto->precio_venta : 0), 2) }}
                    </div>
                @endif
            </div>
        @endforeach

        <div class="totals-section">
            <div class="total-row">
                <span class="label">Depósito:</span>
                <span>${{ number_format($deposito, 2) }}</span>
            </div>
            <div class="total-row">
                <span class="label">Cargo por faltantes:</span>
                <span>${{ number_format($totalDescuento, 2) }}</span>
            </div>
            @if($totalDescuento > 0)
                <div class="total-row">
                    <span class="label">IVA faltantes (16%):</span>
                    <span>${{ number_format($impuestosFaltantes, 2) }}</span>
                </div>
            @endif
            <div class="total-row grand">
                <span>Depósito a devolver:</span>
                <span>${{ number_format($importeDevolver, 2) }}</span>
            </div>
        </div>

        @if($notaVentaVenta)
            <div class="nota-venta-info">
                <strong>Nota de Venta generada por faltantes:</strong><br>
                {{ $notaVentaVenta->serie }}-{{ $notaVentaVenta->folio }}<br>
                Total: ${{ number_format($notaVentaVenta->total, 2) }}
            </div>
        @endif

        @if($observaciones)
            <div style="margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px;">
                <span class="label">Observaciones:</span>
                <div style="margin-top: 3px;">{{ $observaciones }}</div>
            </div>
        @endif

        <div class="footer">
            <div>Cierre procesado correctamente</div>
            <div style="margin-top: 5px;">{{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

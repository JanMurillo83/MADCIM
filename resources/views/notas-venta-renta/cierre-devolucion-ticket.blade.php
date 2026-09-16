<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre Devolución - Nota {{ $notaVenta->serie ?? '' }}-{{ $notaVenta->folio }}</title>
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
            gap: 8px;
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
            flex-direction: column;
            gap: 2px;
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
        .box {
            margin-top: 8px;
            padding: 5px;
            border: 1px solid #000;
            text-align: center;
            font-size: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
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
        <button onclick="window.print();">Imprimir</button>
        <a href="{{ url('/notas-venta-renta/notas-venta-rentas') }}" class="back">Volver a Notas de Renta</a>
    </div>

    <div style="margin-top: 50px;">
        <div class="header">
            <div class="company-name">MADCIM</div>
            <div class="doc-title">CIERRE DE DEVOLUCIÓN</div>
            <div style="margin-top: 5px;">Fecha: {{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span class="label">Nota de Renta Origen:</span>
                <span>{{ $notasOrigen->map(fn ($nota) => trim(($nota->serie ?? '') . '-' . ($nota->folio ?? '')))->implode(', ') }}</span>
            </div>
            @if($notaVentaVenta)
                <div class="info-row">
                    <span class="label">Nota de Venta:</span>
                    <span>{{ $notaVentaVenta->serie }}-{{ $notaVentaVenta->folio }}</span>
                </div>
            @endif
            <div class="info-row">
                <span class="label">Cliente:</span>
                <span>{{ $notaVenta->cliente->nombre ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha emisión:</span>
                <span>{{ $notaVenta->fecha_emision ? $notaVenta->fecha_emision->format('d/m/Y') : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Envíos vinculados:</span>
                <span>{{ $notaVenta->notasEnvio->count() }}</span>
            </div>
        </div>

        <div class="section-title">DETALLE DE FALTANTES</div>
        @forelse($resumen['rows'] as $row)
            <div class="item-row">
                <div class="item-desc">{{ $row['producto'] }}</div>
                <div class="item-details">
                    <span>Faltante: {{ number_format((float) $row['faltante'], 2) }}</span>
                    <span>Precio unitario (IVA incluido): ${{ number_format((float) ($row['faltante'] > 0 ? $row['total'] / $row['faltante'] : 0), 2) }}</span>
                    <span>Total (IVA incluido): ${{ number_format((float) $row['total'], 2) }}</span>
                </div>
            </div>
        @empty
            <div class="item-row">
                <div class="item-desc">No hay faltantes por cobrar</div>
            </div>
        @endforelse

        <div class="totals-section">
            <div class="total-row">
                <span class="label">Total de Madera Faltante (IVA incluido):</span>
                <span>${{ number_format((float) $resumen['totales']['total_faltantes'], 2) }}</span>
            </div>
            <div class="total-row">
                <span class="label">Total Depósito:</span>
                <span>${{ number_format((float) $resumen['totales']['deposito'], 2) }}</span>
            </div>
            <div class="total-row">
                <span class="label">Saldo por cobrar:</span>
                <span>${{ number_format((float) $resumen['totales']['saldo_por_cobrar'], 2) }}</span>
            </div>
        </div>

        @if($observaciones)
            <div style="margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px;">
                <span class="label">Observaciones:</span>
                <div style="margin-top: 3px;">{{ $observaciones }}</div>
            </div>
        @endif

        <div class="footer">
            <div>Cierre consolidado de renta procesado correctamente</div>
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

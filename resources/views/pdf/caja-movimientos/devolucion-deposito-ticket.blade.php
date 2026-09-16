<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Devolución de depósito</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .title { text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 10px; }
        .block { margin-bottom: 10px; }
        .row { margin-bottom: 4px; }
        .label { font-weight: bold; }
        .amount { border-top: 1px solid #111; border-bottom: 1px solid #111; padding: 8px 0; text-align: right; font-size: 15px; font-weight: bold; }
        .footer { margin-top: 18px; text-align: center; color: #666; font-size: 9px; }
    </style>
</head>
<body>
    <div class="title">DEVOLUCIÓN DE DEPÓSITO</div>

    <div class="block">
        <div class="row"><span class="label">Fecha:</span> {{ optional($movimiento->fecha)->format('d/m/Y H:i') }}</div>
        <div class="row"><span class="label">Cliente:</span> {{ $devolucion->documentoOrigen?->cliente?->nombre ?? 'N/A' }}</div>
        <div class="row"><span class="label">Nota de renta:</span> {{ trim(($devolucion->documentoOrigen?->serie ?? '') . '-' . ($devolucion->documentoOrigen?->folio ?? '')) }}</div>
        <div class="row"><span class="label">Referencia:</span> {{ $movimiento->referencia ?? 'N/A' }}</div>
        <div class="row"><span class="label">Caja:</span> {{ $movimiento->caja?->nombre ?? 'N/A' }}</div>
        <div class="row"><span class="label">Entregado por:</span> {{ $movimiento->user?->name ?? 'N/A' }}</div>
    </div>

    <div class="amount">Importe entregado: ${{ number_format((float) $movimiento->importe, 2) }}</div>

    @if ($movimiento->observaciones)
        <div class="block"><span class="label">Observaciones:</span> {{ $movimiento->observaciones }}</div>
    @endif

    <div class="footer">Comprobante de devolución de depósito de renta.</div>
</body>
</html>

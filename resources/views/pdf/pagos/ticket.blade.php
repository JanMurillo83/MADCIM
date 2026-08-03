<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de pago</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { width: 80mm; margin: 0 auto; padding: 5mm; font: 12px 'Courier New', monospace; }
        .center { text-align: center; }
        .header { border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .strong { font-weight: bold; }
        .total { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px 0; font-size: 14px; font-weight: bold; }
        .footer { border-top: 1px dashed #000; margin-top: 12px; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header center">
        <div class="strong" style="font-size: 17px;">MADCIM</div>
        <div>RECIBO DE PAGO</div>
    </div>

    <div class="row"><span class="strong">Recibo:</span><span>{{ $pago->id }}</span></div>
    <div class="row"><span class="strong">Fecha:</span><span>{{ optional($pago->fecha_pago_hora ?? $pago->fecha_pago)->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span class="strong">Nota:</span><span>{{ $documento->serie }}-{{ $documento->folio }}</span></div>
    <div class="row"><span class="strong">Cliente:</span><span>{{ $pago->cliente?->nombre ?? 'N/A' }}</span></div>

    <div class="line"></div>
    @php $totalCobrado = $pagos->sum('importe'); @endphp
    @foreach($pagos as $pagoLinea)
        <div class="row"><span>{{ match ($pagoLinea->forma_pago) {
            '01' => 'Efectivo', '02' => 'Cheque', '03' => 'Transferencia', '04' => 'Tarjeta crédito', '28' => 'Tarjeta débito', default => $pagoLinea->forma_pago,
        } }}:</span><span>${{ number_format($pagoLinea->importe, 2) }}</span></div>
        @if($pagoLinea->forma_pago === '01')
            <div class="row"><span>Recibido:</span><span>${{ number_format($pagoLinea->importe_recibido, 2) }}</span></div>
            <div class="row"><span>Cambio:</span><span>${{ number_format($pagoLinea->cambio, 2) }}</span></div>
        @endif
    @endforeach
    <div class="row total"><span>Total aplicado:</span><span>${{ number_format($totalCobrado, 2) }}</span></div>
    @if($pago->referencia)
        <div class="row"><span>Referencia:</span><span>{{ $pago->referencia }}</span></div>
    @endif

    <div class="footer center">Pago registrado correctamente.<br>Gracias por su preferencia.</div>
</body>
</html>

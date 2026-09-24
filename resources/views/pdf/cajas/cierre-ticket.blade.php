<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja {{ $caja->id }}</title>
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111827; font-size: 11px; }
        h1 { margin: 0; font-size: 20px; }
        h2 { margin: 18px 0 8px; font-size: 14px; border-bottom: 1px solid #9ca3af; padding-bottom: 4px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 8px; }
        .muted { color: #4b5563; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; }
        .row { display: flex; justify-content: space-between; border-bottom: 1px dotted #d1d5db; padding: 4px 0; }
        .strong { font-weight: 700; }
        .difference { font-size: 14px; font-weight: 700; border: 1px solid #111827; padding: 8px; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 5px; text-align: left; }
        th { background: #e5e7eb; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 70px; margin-top: 70px; }
        .signature { border-top: 1px solid #111827; padding-top: 6px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>CIERRE DE CAJA</h1>
            <div class="muted">Caja {{ $caja->nombre ?: '#' . $caja->id }} | Fecha: {{ optional($caja->fecha_cierre)->format('d/m/Y H:i') }}</div>
        </div>
        <div class="strong">MADCIM</div>
    </div>

    <h2>Resumen de efectivo</h2>
    <div class="grid">
        <div class="row"><span>Saldo inicial</span><span>${{ number_format((float) $caja->saldo_inicial_cash, 2) }}</span></div>
        <div class="row"><span>Ingresos efectivo</span><span>${{ number_format((float) $caja->total_ingresos_cash, 2) }}</span></div>
        <div class="row"><span>Egresos efectivo</span><span>${{ number_format((float) $caja->total_egresos_cash, 2) }}</span></div>
        <div class="row"><span>Efectivo teórico</span><span>${{ number_format((float) $caja->efectivo_teorico, 2) }}</span></div>
        <div class="row"><span>Efectivo contado</span><span>${{ number_format((float) $caja->efectivo_contado, 2) }}</span></div>
    </div>
    <div class="difference">Diferencia: ${{ number_format((float) $caja->total_diferencia, 2) }}</div>

    <h2>Desglose de movimientos</h2>
    <table>
        <thead><tr><th>Forma de pago</th><th>Ingresos</th><th>Egresos</th></tr></thead>
        <tbody>
        @forelse($caja->movimientos->groupBy(fn ($movimiento) => $movimiento->metodo_pago ?: 'Sin especificar') as $metodo => $movimientos)
            <tr>
                <td>{{ $metodo }}</td>
                <td>${{ number_format((float) $movimientos->where('tipo', 'Ingreso')->sum('importe'), 2) }}</td>
                <td>${{ number_format((float) $movimientos->where('tipo', 'Egreso')->sum('importe'), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Sin movimientos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Conteo de efectivo</h2>
    <table>
        <thead><tr><th>Denominación</th><th>Cantidad</th><th>Importe</th></tr></thead>
        <tbody>
        @foreach(($caja->denominaciones_efectivo ?? []) as $clave => $cantidad)
            @php
                $valor = match ($clave) {
                    'moneda_0_5' => 0.5, 'moneda_1' => 1, 'moneda_2' => 2, 'moneda_5' => 5, 'moneda_10' => 10, 'moneda_20' => 20,
                    'billete_20' => 20, 'billete_50' => 50, 'billete_100' => 100, 'billete_200' => 200, 'billete_500' => 500, 'billete_1000' => 1000,
                    default => 0,
                };
            @endphp
            <tr><td>${{ number_format($valor, 2) }}</td><td>{{ $cantidad }}</td><td>${{ number_format($valor * $cantidad, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    @if($caja->observaciones_cierre)
        <h2>Observaciones</h2>
        <div>{{ $caja->observaciones_cierre }}</div>
    @endif

    <div class="signatures">
        <div class="signature">Responsable de caja<br>{{ $caja->usuarioCierre?->name ?? '' }}</div>
        <div class="signature">Revisó / autorizó</div>
    </div>
</body>
</html>

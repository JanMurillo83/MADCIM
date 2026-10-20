<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cortes de Caja</title>
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #111827; font-size: 10px; }
        h1 { margin: 0; font-size: 18px; }
        h2 { margin: 18px 0 8px; font-size: 13px; border-bottom: 1px solid #9ca3af; padding-bottom: 4px; }
        .info { color: #4b5563; margin: 4px 0 12px; }
        .corte { page-break-inside: avoid; border-bottom: 1px solid #9ca3af; padding-bottom: 10px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 7px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 20px; }
        .row { display: flex; justify-content: space-between; border-bottom: 1px dotted #d1d5db; padding: 3px 0; }
        .strong { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 4px; text-align: left; }
        th { background: #e5e7eb; }
        .text-right { text-align: right; }
        .totals { margin-top: 12px; }
        .totals table { width: 45%; margin-left: auto; }
    </style>
</head>
<body>
    <div class="header"><h1>CORTES DE CAJA</h1><div class="strong">MADCIM</div></div>
    <p class="info">Cierres del {{ $fecha_inicio }} al {{ $fecha_fin }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>

    @forelse($cortes as $corte)
        <div class="corte">
            <h2>{{ $corte->sucursal?->nombre ?? 'Global' }} · {{ $corte->caja?->nombre ?? 'Caja ' . $corte->caja_id }}</h2>
            <p class="info">Cierre: {{ $corte->fecha_cierre?->format('d/m/Y H:i') }} | Responsable: {{ $corte->usuarioCierre?->name ?? 'N/A' }}</p>
            <div class="grid">
                <div class="row"><span>Saldo inicial</span><span>${{ number_format((float) $corte->saldo_inicial_cash, 2) }}</span></div>
                <div class="row"><span>Ingresos efectivo</span><span>${{ number_format((float) $corte->total_ingresos_cash, 2) }}</span></div>
                <div class="row"><span>Egresos efectivo</span><span>${{ number_format((float) $corte->total_egresos_cash, 2) }}</span></div>
                <div class="row"><span>Efectivo teórico</span><span>${{ number_format((float) $corte->efectivo_teorico, 2) }}</span></div>
                <div class="row"><span>Efectivo contado</span><span>${{ number_format((float) $corte->efectivo_contado, 2) }}</span></div>
                <div class="row strong"><span>Diferencia</span><span>${{ number_format((float) $corte->total_diferencia, 2) }}</span></div>
            </div>
            <h2>Desglose por forma de pago</h2>
            <table>
                <thead><tr><th>Forma de pago</th><th class="text-right">Ingresos</th><th class="text-right">Egresos</th></tr></thead>
                <tbody>
                    @forelse($corte->movimientos->groupBy(fn ($movimiento) => $movimiento->metodo_pago ?: 'Sin especificar') as $metodo => $movimientos)
                        <tr><td>{{ $metodo }}</td><td class="text-right">${{ number_format((float) $movimientos->where('tipo', 'Ingreso')->sum('importe'), 2) }}</td><td class="text-right">${{ number_format((float) $movimientos->where('tipo', 'Egreso')->sum('importe'), 2) }}</td></tr>
                    @empty
                        <tr><td colspan="3">Sin movimientos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <p>No hay cortes guardados para los filtros seleccionados.</p>
    @endforelse

    <div class="totals">
        <table>
            <tr><th>Totales del filtro</th><th class="text-right">Importe</th></tr>
            <tr><td>Ingresos efectivo</td><td class="text-right">${{ number_format($totals['ingresos'], 2) }}</td></tr>
            <tr><td>Egresos efectivo</td><td class="text-right">${{ number_format($totals['egresos'], 2) }}</td></tr>
            <tr><td>Diferencia acumulada</td><td class="text-right">${{ number_format($totals['diferencia'], 2) }}</td></tr>
        </table>
    </div>
</body>
</html>

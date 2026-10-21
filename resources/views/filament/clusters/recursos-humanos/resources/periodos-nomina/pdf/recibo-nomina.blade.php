<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de nómina</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { color: #166534; font-size: 18px; margin: 0 0 14px; }
        h2 { background: #dcfce7; border: 1px solid #86efac; font-size: 12px; margin: 18px 0 0; padding: 6px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #86efac; padding: 6px; }
        th { text-align: left; width: 32%; }
        .amount { text-align: right; }
        .total { font-size: 13px; font-weight: bold; }
        .muted { color: #6b7280; }
        .signature { height: 70px; text-align: center; vertical-align: bottom; }
    </style>
</head>
<body>
    <h1>RECIBO DE PAGO</h1>

    <table>
        <tr>
            <th>Periodo de la nómina</th>
            <td>{{ $periodo->fecha_inicio->format('d/m/Y') }} al {{ $periodo->fecha_fin->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Código</th>
            <td>{{ $empleado?->numero_empleado }}</td>
        </tr>
        <tr>
            <th>Nombre completo</th>
            <td>{{ $empleado?->nombre_completo }}</td>
        </tr>
        <tr>
            <th>Fecha de ingreso</th>
            <td>{{ $empleado?->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <th>Sueldo diario</th>
            <td class="amount">${{ number_format((float) ($empleado?->sueldo_diario ?? 0), 2) }}</td>
        </tr>
        <tr>
            <th>Sueldo semanal</th>
            <td class="amount">${{ number_format((float) ($empleado?->sueldo_semanal ?? 0), 2) }}</td>
        </tr>
        <tr>
            <th>Faltas</th>
            <td>{{ $nomina->faltas }}</td>
        </tr>
        <tr>
            <th>Días programados / trabajados</th>
            <td>{{ $nomina->dias_programados }} / {{ $nomina->dias_trabajados }}</td>
        </tr>
        <tr>
            <th>Días de descanso laborados</th>
            <td>{{ $nomina->dias_descanso_laborados }}</td>
        </tr>
        <tr>
            <th>Días festivos laborados</th>
            <td>{{ $nomina->dias_festivos }}</td>
        </tr>
        <tr>
            <th>Minutos de retardo</th>
            <td>{{ $nomina->minutos_retardo }}</td>
        </tr>
    </table>

    <h2>Percepciones</h2>
    <table>
        <tr><th>Importe de la semana</th><td class="amount">${{ number_format((float) $nomina->sueldo_base, 2) }}</td></tr>
        <tr><th>Importe tiempo extra</th><td class="amount">${{ number_format((float) $nomina->horas_extra, 2) }}</td></tr>
        <tr><th>Bonos</th><td class="amount">${{ number_format((float) $nomina->bonos, 2) }}</td></tr>
        <tr><th>Aguinaldo operativo</th><td class="amount">${{ number_format((float) $nomina->aguinaldo, 2) }}</td></tr>
        <tr><th>Apoyos</th><td class="amount">${{ number_format((float) $nomina->apoyos, 2) }}</td></tr>
        <tr><th>Días festivos y descansos laborados</th><td class="amount">${{ number_format((float) $nomina->importe_dia_festivo + (float) $nomina->importe_descanso_laborado, 2) }}</td></tr>
        <tr class="total"><th>Subtotal</th><td class="amount">${{ number_format((float) $nomina->sueldo_base + (float) $nomina->horas_extra + (float) $nomina->bonos, 2) }}</td></tr>
    </table>

    <h2>Deducciones</h2>
    <table>
        <tr><th>Descuento por faltas</th><td class="amount">${{ number_format((float) $nomina->descuento_faltas, 2) }}</td></tr>
        <tr><th>Descuento por retardos</th><td class="amount">${{ number_format((float) $nomina->descuento_retardos, 2) }}</td></tr>
        <tr><th>Otros descuentos</th><td class="amount">${{ number_format((float) $nomina->otros_descuentos, 2) }}</td></tr>
        <tr><th>Anticipos</th><td class="amount">${{ number_format((float) $nomina->anticipos, 2) }}</td></tr>
        <tr><th>Préstamos</th><td class="amount">${{ number_format((float) $nomina->prestamos, 2) }}</td></tr>
        <tr class="total"><th>Sueldo neto</th><td class="amount">${{ number_format((float) $nomina->total, 2) }}</td></tr>
    </table>

    @if($conceptos->isNotEmpty())
        <h2>Conceptos operativos capturados</h2>
        <table>
            <tr><th>Tipo</th><th>Concepto</th><th>Cantidad</th><th class="amount">Importe</th></tr>
            @foreach($conceptos as $concepto)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $concepto->tipo)) }}</td>
                    <td>{{ $concepto->concepto }}</td>
                    <td>{{ number_format((float) $concepto->cantidad, 2) }}</td>
                    <td class="amount">${{ number_format((float) $concepto->importe, 2) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table>
        <tr><td class="signature">Firma del trabajador</td></tr>
    </table>
    <p class="muted">Documento operativo interno. No representa un comprobante fiscal ni incluye cálculos de impuestos.</p>
</body>
</html>

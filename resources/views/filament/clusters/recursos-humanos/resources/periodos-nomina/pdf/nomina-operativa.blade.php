<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nómina operativa</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p { margin: 2px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bdbdbd; padding: 4px; }
        th { background: #eeeeee; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Nómina operativa semanal</h1>
    <p>Periodo: {{ $periodo->fecha_inicio->format('d/m/Y') }} al {{ $periodo->fecha_fin->format('d/m/Y') }} | Estatus: {{ ucfirst($periodo->estatus) }}</p>
    <table>
        <thead>
            <tr>
                <th>Número</th><th>Empleado</th><th>Sueldo base</th><th>Días prog.</th><th>Días trab.</th>
                <th>Descanso lab.</th><th>Festivos</th><th>Faltas</th><th>Min. retardo</th><th>Desc. faltas</th>
                <th>Desc. retardos</th><th>Extra</th><th>Bonos</th><th>Aguinaldo</th><th>Apoyos</th>
                <th>Anticipos</th><th>Préstamos</th><th>Otros desc.</th><th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nominas as $nomina)
                <tr>
                    <td>{{ $nomina->empleado?->numero_empleado }}</td>
                    <td>{{ $nomina->empleado?->nombre_completo }}</td>
                    <td class="right">${{ number_format($nomina->sueldo_base, 2) }}</td>
                    <td class="right">{{ $nomina->dias_programados }}</td>
                    <td class="right">{{ $nomina->dias_trabajados }}</td>
                    <td class="right">{{ $nomina->dias_descanso_laborados }}</td>
                    <td class="right">{{ $nomina->dias_festivos }}</td>
                    <td class="right">{{ $nomina->faltas }}</td>
                    <td class="right">{{ $nomina->minutos_retardo }}</td>
                    <td class="right">${{ number_format($nomina->descuento_faltas, 2) }}</td>
                    <td class="right">${{ number_format($nomina->descuento_retardos, 2) }}</td>
                    <td class="right">${{ number_format($nomina->horas_extra, 2) }}</td>
                    <td class="right">${{ number_format($nomina->bonos, 2) }}</td>
                    <td class="right">${{ number_format($nomina->aguinaldo, 2) }}</td>
                    <td class="right">${{ number_format($nomina->apoyos, 2) }}</td>
                    <td class="right">${{ number_format($nomina->anticipos, 2) }}</td>
                    <td class="right">${{ number_format($nomina->prestamos, 2) }}</td>
                    <td class="right">${{ number_format($nomina->otros_descuentos, 2) }}</td>
                    <td class="right">${{ number_format($nomina->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Totales</th>
                <th class="right">${{ number_format($nominas->sum('sueldo_base'), 2) }}</th>
                <th class="right">{{ $nominas->sum('dias_programados') }}</th>
                <th class="right">{{ $nominas->sum('dias_trabajados') }}</th>
                <th class="right">{{ $nominas->sum('dias_descanso_laborados') }}</th>
                <th class="right">{{ $nominas->sum('dias_festivos') }}</th>
                <th class="right">{{ $nominas->sum('faltas') }}</th>
                <th class="right">{{ $nominas->sum('minutos_retardo') }}</th>
                <th class="right">${{ number_format($nominas->sum('descuento_faltas'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('descuento_retardos'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('horas_extra'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('bonos'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('aguinaldo'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('apoyos'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('anticipos'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('prestamos'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('otros_descuentos'), 2) }}</th>
                <th class="right">${{ number_format($nominas->sum('total'), 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>

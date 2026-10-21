<div class="max-h-[70vh] overflow-auto">
    <table class="w-full text-sm">
        <thead class="sticky top-0 bg-gray-50">
            <tr>
                <th class="px-3 py-2 text-left">Empleado</th>
                <th class="px-3 py-2 text-right">Base</th>
                <th class="px-3 py-2 text-right">Faltas</th>
                <th class="px-3 py-2 text-right">Retardos</th>
                <th class="px-3 py-2 text-right">Desc. faltas</th>
                <th class="px-3 py-2 text-right">Desc. retardos</th>
                <th class="px-3 py-2 text-right">Extra</th>
                <th class="px-3 py-2 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($nominas as $nomina)
                <tr>
                    <td class="px-3 py-2">{{ $nomina->empleado?->nombre_completo }}</td>
                    <td class="px-3 py-2 text-right">${{ number_format($nomina->sueldo_base, 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ $nomina->faltas }}</td>
                    <td class="px-3 py-2 text-right">{{ $nomina->minutos_retardo }}</td>
                    <td class="px-3 py-2 text-right">${{ number_format($nomina->descuento_faltas, 2) }}</td>
                    <td class="px-3 py-2 text-right">${{ number_format($nomina->descuento_retardos, 2) }}</td>
                    <td class="px-3 py-2 text-right">${{ number_format($nomina->horas_extra, 2) }}</td>
                    <td class="px-3 py-2 text-right font-semibold">${{ number_format($nomina->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">Este periodo todavía no tiene nómina generada.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

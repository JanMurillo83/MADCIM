<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-xs text-gray-500">Empleados</div>
                <div class="text-xl font-semibold">{{ $this->totales['empleados'] }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-xs text-gray-500">Faltas</div>
                <div class="text-xl font-semibold">{{ $this->totales['faltas'] }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-xs text-gray-500">Min. retardo</div>
                <div class="text-xl font-semibold">{{ $this->totales['minutos_retardo'] }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-xs text-gray-500">Horas extra</div>
                <div class="text-xl font-semibold">${{ number_format($this->totales['horas_extra'], 2) }}</div>
            </div>
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-xs text-gray-500">Descuentos</div>
                <div class="text-xl font-semibold">${{ number_format($this->totales['descuentos'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-success-200 bg-success-50 p-4 shadow-sm">
                <div class="text-xs text-success-700">Total a pagar</div>
                <div class="text-xl font-semibold text-success-800">${{ number_format($this->totales['total'], 2) }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Empleado</th>
                            <th class="px-4 py-3 text-right">Sueldo base</th>
                            <th class="px-4 py-3 text-right">Faltas</th>
                            <th class="px-4 py-3 text-right">Min. retardo</th>
                            <th class="px-4 py-3 text-right">Desc. faltas</th>
                            <th class="px-4 py-3 text-right">Desc. retardos</th>
                            <th class="px-4 py-3 text-right">Horas extra</th>
                            <th class="px-4 py-3 text-right">Bonos</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->nominas as $nomina)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $nomina->empleado?->nombre_completo }}</div>
                                    <div class="text-xs text-gray-500">{{ $nomina->empleado?->numero_empleado }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">${{ number_format($nomina->sueldo_base, 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ $nomina->faltas }}</td>
                                <td class="px-4 py-3 text-right">{{ $nomina->minutos_retardo }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($nomina->descuento_faltas, 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($nomina->descuento_retardos, 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($nomina->horas_extra, 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($nomina->bonos, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">${{ number_format($nomina->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    Este periodo todavía no tiene una nómina generada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

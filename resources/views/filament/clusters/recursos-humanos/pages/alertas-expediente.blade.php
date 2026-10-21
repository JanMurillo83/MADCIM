<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800">
            Se muestran contratos que vencen en los próximos 30 días y documentos dentro de su periodo de alerta.
            Registros vencidos: <strong>{{ $this->vencidos }}</strong>.
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4 py-3 font-semibold">Contratos por vencer</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Empleado</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Vencimiento</th>
                            <th class="px-4 py-3 text-left">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->contratos as $contrato)
                            <tr>
                                <td class="px-4 py-3">{{ $contrato->empleado?->nombre_completo }}</td>
                                <td class="px-4 py-3">{{ $contrato->tipo }}</td>
                                <td class="px-4 py-3">{{ $contrato->fecha_fin->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <x-filament::badge :color="$contrato->fecha_fin->isPast() && ! $contrato->fecha_fin->isToday() ? 'danger' : 'warning'">
                                        {{ $contrato->fecha_fin->isPast() && ! $contrato->fecha_fin->isToday() ? 'Vencido' : 'Por vencer' }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay contratos próximos a vencer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4 py-3 font-semibold">Documentos por vencer</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Empleado</th>
                            <th class="px-4 py-3 text-left">Documento</th>
                            <th class="px-4 py-3 text-left">Vencimiento</th>
                            <th class="px-4 py-3 text-left">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->documentos as $documento)
                            <tr>
                                <td class="px-4 py-3">{{ $documento->empleado?->nombre_completo }}</td>
                                <td class="px-4 py-3">{{ $documento->nombre }}</td>
                                <td class="px-4 py-3">{{ $documento->fecha_vencimiento->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <x-filament::badge :color="$documento->fecha_vencimiento->isPast() && ! $documento->fecha_vencimiento->isToday() ? 'danger' : 'warning'">
                                        {{ $documento->fecha_vencimiento->isPast() && ! $documento->fecha_vencimiento->isToday() ? 'Vencido' : 'Por vencer' }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay documentos próximos a vencer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

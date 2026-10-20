<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                <input type="date" wire:model.live="fecha"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            @if(auth()->user()?->isAdmin())
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sucursal</label>
                    <select wire:model.live="sucursal_id"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todas</option>
                        @foreach($this->sucursales as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
            @foreach([
                ['label' => 'Programados', 'value' => $this->totales['programados']],
                ['label' => 'Presentes', 'value' => $this->totales['presentes']],
                ['label' => 'Puntuales', 'value' => $this->totales['puntuales']],
                ['label' => 'Retardos', 'value' => $this->totales['retardos']],
                ['label' => 'Ausentes', 'value' => $this->totales['ausentes']],
                ['label' => 'Sin salida', 'value' => $this->totales['sin_salida']],
            ] as $stat)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="text-sm text-gray-500">{{ $stat['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stat['value'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Empleado</th>
                            <th class="px-4 py-3 text-left">Sucursal</th>
                            <th class="px-4 py-3 text-left">Entrada</th>
                            <th class="px-4 py-3 text-left">Comida</th>
                            <th class="px-4 py-3 text-left">Salida</th>
                            <th class="px-4 py-3 text-right">Retardo</th>
                            <th class="px-4 py-3 text-right">Efectivo</th>
                            <th class="px-4 py-3 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->resumen as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $row['nombre'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['numero_empleado'] }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $row['sucursal'] }}</td>
                                <td class="px-4 py-3">{{ $row['entrada']?->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    {{ $row['salida_comida']?->format('H:i') ?? '—' }}
                                    /
                                    {{ $row['regreso_comida']?->format('H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">{{ $row['salida']?->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    {{ $row['minutos_retardo'] ? $row['minutos_retardo'].' min' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ $row['minutos_efectivos'] ? $row['minutos_efectivos'].' min' : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                                        {{ match($row['estado']) {
                                            'puntual' => 'bg-green-100 text-green-800',
                                            'tolerancia' => 'bg-yellow-100 text-yellow-800',
                                            'retardo' => 'bg-orange-100 text-orange-800',
                                            'ausente' => 'bg-red-100 text-red-800',
                                            'descanso' => 'bg-gray-100 text-gray-800',
                                            default => 'bg-blue-100 text-blue-800',
                                        } }}">
                                        {{ $row['estado_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    No hay empleados activos programados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

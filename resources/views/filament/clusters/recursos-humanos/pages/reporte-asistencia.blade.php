<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha inicio</label>
                <input type="date" wire:model.live="fecha_inicio"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha fin</label>
                <input type="date" wire:model.live="fecha_fin"
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
            <div>
                <label class="block text-sm font-medium text-gray-700">Empleado</label>
                <select wire:model.live="empleado_id"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Todos</option>
                    @foreach($this->empleados as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <x-filament::button wire:click="exportarCsv" color="success" icon="heroicon-o-arrow-down-tray">
                    Exportar CSV
                </x-filament::button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Empleado</th>
                            <th class="px-4 py-3 text-left">Sucursal</th>
                            <th class="px-4 py-3 text-right">Programados</th>
                            <th class="px-4 py-3 text-right">Laborados</th>
                            <th class="px-4 py-3 text-right">Puntuales</th>
                            <th class="px-4 py-3 text-right">Retardos</th>
                            <th class="px-4 py-3 text-right">Faltas</th>
                            <th class="px-4 py-3 text-right">Sin salida</th>
                            <th class="px-4 py-3 text-right">Min. retardo</th>
                            <th class="px-4 py-3 text-right">Min. efectivos</th>
                            <th class="px-4 py-3 text-right">Min. extra</th>
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
                                <td class="px-4 py-3 text-right">{{ $row['dias_programados'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['dias_laborados'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['puntuales'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['retardos'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['faltas'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['sin_salida'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['minutos_retardo'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['minutos_efectivos'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['horas_extra_autorizadas'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                                    No hay datos para el periodo seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

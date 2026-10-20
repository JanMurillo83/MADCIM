<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Fecha inicio</label>
                <input type="date" wire:model.live="fecha_inicio" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Fecha fin</label>
                <input type="date" wire:model.live="fecha_fin" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            @if(auth()->user()?->isAdmin())
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Sucursal</label>
                    <select wire:model.live="sucursal_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Global: todas las sucursales</option>
                        @foreach($this->sucursales as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Caja</label>
                <select wire:model.live="caja_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Todas las cajas</option>
                    @foreach($this->cajas as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">Exportar PDF</x-filament::button>
            <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">Exportar Excel (CSV)</x-filament::button>
            <x-report-preview
                title="Vista previa de cortes de caja"
                subtitle="Cierres del {{ $fecha_inicio }} al {{ $fecha_fin }}"
                :preview-view="'exports.reportes.cortes-caja-pdf'"
                :preview-data="['cortes' => $this->cortes, 'totals' => ['ingresos' => $this->totalIngresos, 'egresos' => $this->totalEgresos, 'diferencia' => $this->totalDiferencia], 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]"
            />
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600">Fecha cierre</th>
                            <th class="px-4 py-2 text-left text-gray-600">Sucursal</th>
                            <th class="px-4 py-2 text-left text-gray-600">Caja</th>
                            <th class="px-4 py-2 text-left text-gray-600">Usuario</th>
                            <th class="px-4 py-2 text-right text-gray-600">Ingresos</th>
                            <th class="px-4 py-2 text-right text-gray-600">Egresos</th>
                            <th class="px-4 py-2 text-right text-gray-600">Teórico</th>
                            <th class="px-4 py-2 text-right text-gray-600">Contado</th>
                            <th class="px-4 py-2 text-right text-gray-600">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->cortes as $corte)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $corte->fecha_cierre?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $corte->sucursal?->nombre ?? 'Global' }}</td>
                                <td class="px-4 py-2">{{ $corte->caja?->nombre ?? 'Caja ' . $corte->caja_id }}</td>
                                <td class="px-4 py-2">{{ $corte->usuarioCierre?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $corte->total_ingresos_cash, 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $corte->total_egresos_cash, 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $corte->efectivo_teorico, 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $corte->efectivo_contado, 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $corte->total_diferencia, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">No hay cortes guardados para los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow"><p class="text-sm text-gray-500">Ingresos efectivo</p><p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalIngresos, 2) }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow"><p class="text-sm text-gray-500">Egresos efectivo</p><p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalEgresos, 2) }}</p></div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow"><p class="text-sm text-gray-500">Diferencia acumulada</p><p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalDiferencia, 2) }}</p></div>
        </div>
    </div>
</x-filament-panels::page>

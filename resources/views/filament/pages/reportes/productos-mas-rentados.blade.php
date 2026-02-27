<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                <input type="date" wire:model.live="fecha_inicio"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                <input type="date" wire:model.live="fecha_fin"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                <select wire:model.live="cliente_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->clientes as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sucursal</label>
                <select wire:model.live="sucursal_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todas --</option>
                    @foreach($this->sucursales as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                <select wire:model.live="usuario_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->usuarios as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                <select wire:model.live="producto_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->productos as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3">
            <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">
                Exportar PDF
            </x-filament::button>
            <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                Exportar Excel (CSV)
            </x-filament::button>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600">Clave</th>
                            <th class="px-4 py-2 text-left text-gray-600">Producto</th>
                            <th class="px-4 py-2 text-right text-gray-600">Cantidad</th>
                            <th class="px-4 py-2 text-right text-gray-600">Importe renta</th>
                            <th class="px-4 py-2 text-right text-gray-600">Importe deposito</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->productos as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $row['clave'] }}</td>
                                <td class="px-4 py-2">{{ $row['descripcion'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['cantidad'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['importe_renta'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['importe_deposito'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="5">Sin resultados para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Cantidad total</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($this->totalCantidad, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Importe renta</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalImporteRenta, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Importe deposito</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalImporteDeposito, 2) }}</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>

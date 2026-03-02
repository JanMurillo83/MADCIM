<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Linea</label>
                <select wire:model.live="linea"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todas --</option>
                    @foreach($this->lineas as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grupo</label>
                <select wire:model.live="grupo"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->grupos as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
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
                            <th class="px-4 py-2 text-left text-gray-600">Linea</th>
                            <th class="px-4 py-2 text-left text-gray-600">Grupo</th>
                            <th class="px-4 py-2 text-right text-gray-600">Existencia</th>
                            <th class="px-4 py-2 text-right text-gray-600">Costo</th>
                            <th class="px-4 py-2 text-right text-gray-600">Valor inventario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->items as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $row['clave'] }}</td>
                                <td class="px-4 py-2">{{ $row['descripcion'] }}</td>
                                <td class="px-4 py-2">{{ $row['linea'] }}</td>
                                <td class="px-4 py-2">{{ $row['grupo'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['existencia'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['costo'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['valor'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="7">Sin resultados para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Total existencia</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($this->totalExistencia, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Valor inventario</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalValor, 2) }}</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>

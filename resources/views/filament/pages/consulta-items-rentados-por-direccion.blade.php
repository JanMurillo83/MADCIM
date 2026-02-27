<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <div class="space-y-6">
        {{-- Filtros --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                <select wire:model.live="cliente_id"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Seleccione un cliente --</option>
                    @foreach($this->clientes as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección de Entrega</label>
                <select wire:model.live="direccion_entrega_id"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todas las direcciones --</option>
                    @foreach($this->direcciones as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Botones de exportación --}}
        @if($this->cliente_id && $this->items->count() > 0)
            <div class="flex gap-3">
                <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">
                    Exportar PDF
                </x-filament::button>
                <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                    Exportar Excel (CSV)
                </x-filament::button>
            </div>
        @endif

        {{-- Resultados --}}
        @if($this->cliente_id)
            @if($this->items->count() > 0)
                {{-- Totales generales --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Items Rentados</p>
                        <p class="text-2xl font-bold text-primary-600">{{ $this->items->sum('cantidad') }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Importe Total Renta</p>
                        <p class="text-2xl font-bold text-warning-600">${{ number_format($this->totalImporteRenta, 2) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Equivalente Precio Venta</p>
                        <p class="text-2xl font-bold text-success-600">${{ number_format($this->totalPrecioVenta, 2) }}</p>
                    </div>
                </div>

                {{-- Tabla agrupada por dirección --}}
                @foreach($this->itemsAgrupados as $direccionId => $itemsGrupo)
                    @php
                        $direccion = $itemsGrupo->first()->notaVentaRenta?->direccionEntrega;
                        $direccionNombre = $direccion ? $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa : $itemsGrupo->first()->cliente_direccion;
                        $subtotalRenta = $itemsGrupo->sum('importe_renta');
                        $subtotalVenta = $itemsGrupo->sum(fn($i) => ($i->producto?->precio_venta ?? 0) * $i->cantidad);
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                <x-heroicon-o-map-pin class="w-4 h-4 inline-block mr-1" />
                                {{ $direccionNombre ?? 'Sin dirección asignada' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $itemsGrupo->sum('cantidad') }} items |
                                Renta: ${{ number_format($subtotalRenta, 2) }} |
                                Venta equiv.: ${{ number_format($subtotalVenta, 2) }}
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-600">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Clave</th>
                                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Producto</th>
                                        <th class="px-4 py-2 text-center text-gray-600 dark:text-gray-300">Cantidad</th>
                                        <th class="px-4 py-2 text-center text-gray-600 dark:text-gray-300">Días Renta</th>
                                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">Importe Renta</th>
                                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">Precio Venta Unit.</th>
                                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">Total Precio Venta</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    @foreach($itemsGrupo as $item)
                                        @php
                                            $precioVenta = $item->producto?->precio_venta ?? 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $item->producto?->clave ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $item->producto?->descripcion ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $item->cantidad }}</td>
                                            <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $item->dias_renta }}</td>
                                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($item->importe_renta, 2) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($precioVenta, 2) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($precioVenta * $item->cantidad, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No se encontraron items rentados activos para este cliente.</p>
                </div>
            @endif
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-funnel class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Seleccione un cliente para ver los items rentados por dirección de entrega.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>

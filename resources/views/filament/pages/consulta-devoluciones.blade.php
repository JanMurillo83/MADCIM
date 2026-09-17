<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</label>
                <select wire:model.live="cliente_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos los clientes --</option>
                    @foreach($this->clientes as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección de Entrega</label>
                <select wire:model.live="direccion_entrega_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    @disabled(!$this->cliente_id)>
                    <option value="">-- Todas las direcciones --</option>
                    @foreach($this->direcciones as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($this->items->isEmpty())
            <div class="py-10 text-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-inbox class="mx-auto mb-2 h-12 w-12 opacity-50" />
                <p>No se encontraron productos rentados activos.</p>
            </div>
        @else
            @foreach($this->itemsAgrupados as $itemsGrupo)
                @php
                    $itemReferencia = $itemsGrupo->first();
                    $notaRenta = $itemReferencia->notaVentaRenta;
                    $direccion = $itemReferencia->notaVentaRenta?->direccionEntrega;
                    $direccionNombre = $direccion
                        ? $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa
                        : ($itemReferencia->cliente_direccion ?? 'Sin dirección asignada');
                    $subtotalCantidad = $itemsGrupo->sum('cantidad');
                    $subtotalDevuelto = $itemsGrupo->sum('cantidad_devuelta');
                    $subtotalPendiente = max(0, $subtotalCantidad - $subtotalDevuelto);
                    $subtotalRenta = $itemsGrupo->sum('importe_renta');
                    $subtotalVenta = $itemsGrupo->sum(fn ($item) => ($item->producto?->precio_venta ?? 0) * $item->cantidad);
                    $subtotalDeposito = $itemsGrupo->sum('importe_deposito');
                @endphp

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-600 dark:bg-gray-700 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                <x-heroicon-o-map-pin class="mr-1 inline-block h-4 w-4" />
                                {{ $direccionNombre }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $subtotalCantidad }} productos enviados |
                                Devueltos: {{ $subtotalDevuelto }} |
                                Pendientes: {{ $subtotalPendiente }} |
                                Venta total: ${{ number_format($subtotalVenta, 2) }} |
                                Depósitos recibidos: ${{ number_format($subtotalDeposito, 2) }}
                            </p>
                        </div>
                        @if($notaRenta)
                            <div class="flex shrink-0 gap-2">
                                <x-filament::button
                                    tag="a"
                                    href="{{ route('notas-venta-renta.devolucion', $notaRenta->id) }}"
                                    icon="heroicon-o-arrow-uturn-left"
                                    color="warning"
                                    target="_blank">
                                    Devolución
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="cerrarObra({{ $itemReferencia->cliente_id }}, {{ $itemReferencia->notaVentaRenta?->direccion_entrega_id ?? 0 }})"
                                    icon="heroicon-o-check-circle"
                                    color="danger">
                                    Cerrar Obra
                                </x-filament::button>
                            </div>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-600">
                                <tr>
                                    <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Cliente</th>
                                    <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Clave</th>
                                    <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">Producto</th>
                                    <th class="px-4 py-2 text-center text-gray-600 dark:text-gray-300">Cantidad Enviada</th>
                                    <th class="px-4 py-2 text-center text-gray-600 dark:text-gray-300">Devueltos</th>
                                    <th class="px-4 py-2 text-center text-gray-600 dark:text-gray-300">Pendientes</th>
                                    <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">Importe Renta</th>
                                    <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">Precio Venta Unit.</th>
                                    <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">Total Precio Venta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                @foreach($itemsGrupo as $item)
                                    @php
                                        $precioVenta = $item->producto?->precio_venta ?? 0;
                                        $cantidad = (float) $item->cantidad;
                                        $devueltos = (float) $item->cantidad_devuelta;
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $item->cliente?->nombre ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $item->producto?->clave ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $item->producto?->descripcion ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $cantidad }}</td>
                                        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $devueltos }}</td>
                                        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ max(0, $cantidad - $devueltos) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($item->importe_renta, 2) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($precioVenta, 2) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($precioVenta * $cantidad, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-100 font-semibold dark:bg-gray-600">
                                    <td colspan="3" class="px-4 py-2 text-gray-700 dark:text-gray-200">Subtotal dirección</td>
                                    <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-200">{{ $subtotalCantidad }}</td>
                                    <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-200">{{ $subtotalDevuelto }}</td>
                                    <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-200">{{ $subtotalPendiente }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">${{ number_format($subtotalRenta, 2) }}</td>
                                    <td class="px-4 py-2"></td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">${{ number_format($subtotalVenta, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

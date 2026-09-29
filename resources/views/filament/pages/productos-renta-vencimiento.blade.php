<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ $this->tituloConsulta() }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $this->descripcionConsulta() }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Cliente</label>
                <select wire:model.live="cliente_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Todos los clientes</option>
                    @foreach($this->clientes as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()?->isAdmin())
<div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Sucursal</label>
                <select wire:model.live="sucursal_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Todas las sucursales</option>
                    @foreach($this->sucursales as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
@endif
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Registros encontrados</p>
                <p class="text-2xl font-bold text-primary-600">{{ $this->productos->count() }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Cantidad pendiente de devolución</p>
                <p class="text-2xl font-bold text-warning-600">{{ number_format($this->totalPendiente, 2) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 font-medium text-gray-600">Folio</th>
                            <th class="px-4 py-3 font-medium text-gray-600">Cliente</th>
                            <th class="px-4 py-3 font-medium text-gray-600">Producto</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Rentado</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Devuelto</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Pendiente</th>
                            <th class="px-4 py-3 font-medium text-gray-600">Fecha renta</th>
                            <th class="px-4 py-3 font-medium text-gray-600">Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->productos as $registro)
                            @php
                                $pendiente = $this->cantidadPendiente($registro);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ trim(($registro->notaVentaRenta?->serie ?? '') . '-' . ($registro->notaVentaRenta?->folio ?? $registro->nota_venta_renta_id), '-') }}</td>
                                <td class="px-4 py-3">{{ $registro->cliente?->nombre ?? $registro->cliente_nombre ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $registro->producto?->descripcion ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $registro->producto?->clave ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">{{ number_format((float) $registro->cantidad, 2) }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format((float) ($registro->cantidad_devuelta ?? 0), 2) }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ number_format($pendiente, 2) }}</td>
                                <td class="px-4 py-3">{{ $registro->fecha_renta?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium">{{ $registro->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay productos que coincidan con la consulta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

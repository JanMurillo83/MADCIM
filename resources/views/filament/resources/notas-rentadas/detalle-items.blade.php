@vite(['resources/css/app.css', 'resources/js/app.js'])
<div class="space-y-4 rounded-lg bg-gray-800 p-4 text-white">
    @if($items->isEmpty())
        <p class="text-sm text-white">No hay productos registrados en renta para esta nota.</p>
    @else
        <table class="w-full text-sm text-left text-white rtl:text-right">
            <thead class="border-b border-gray-500 bg-gray-700 text-white">
                <tr>
                    <th class="px-4 py-2 font-medium text-white">Producto rentado</th>
                    <th class="px-4 py-2 text-center font-medium text-white">Cantidad rentada</th>
                    <th class="px-4 py-2 text-center font-medium text-white">Vencimiento de la renta</th>
                    <th class="px-4 py-2 text-center font-medium text-white">Cantidad devuelta</th>
                    <th class="px-4 py-2 text-center font-medium text-white">Cantidad pendiente</th>
                    <th class="px-4 py-2 font-medium text-white">Observaciones</th>
                    <th class="px-4 py-2 text-center font-medium text-white">Estado del producto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600 text-white">
                @foreach($items as $item)
                    @php
                        $pendiente = $item->cantidad - ($item->cantidad_devuelta ?? 0);
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-white">{{ $item->producto?->descripcion ?? $item->descripcion ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-white">{{ $item->cantidad }}</td>
                        <td class="px-4 py-3 text-center text-white">{{ $item->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-white">{{ (float)($item->cantidad_devuelta ?? 0) }}</td>
                        <td class="px-4 py-3 text-center text-white">{{ $pendiente }}</td>
                        <td class="px-4 py-3 text-white">{{ $item->observaciones ?? '-' }}</td>
                        <td class="px-8 py-4 text-center">
                            @if(($item->estado ?? 'Activo') === 'Devuelto')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Devuelto</span>
                            @elseif(($item->cantidad_devuelta ?? 0) > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Parcial</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Activo</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

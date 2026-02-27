@vite(['resources/css/app.css', 'resources/js/app.js'])
<div class="space-y-4">
    @if($items->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay items registrados en renta para esta nota.</p>
    @else
        <table class="w-full text-sm text-left rtl:text-right text-body">
            <thead class="bg-neutral-secondary-soft border-b border-default">
                <tr>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300">Producto</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300 text-center">Cantidad</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300 text-center">Devuelto</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300 text-center">Pendiente</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300">Observaciones</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                @foreach($items as $item)
                    @php
                        $pendiente = $item->cantidad - ($item->cantidad_devuelta ?? 0);
                    @endphp
                    <tr>
                        <td class="px-8 py-4 text-gray-900 dark:text-gray-100">{{ $item->producto?->descripcion ?? $item->descripcion ?? '-' }}</td>
                        <td class="px-8 py-4 text-center text-gray-900 dark:text-gray-100">{{ $item->cantidad }}</td>
                        <td class="px-8 py-4 text-center text-gray-900 dark:text-gray-100">{{ (float)($item->cantidad_devuelta ?? 0) }}</td>
                        <td class="px-8 py-4 text-center text-gray-900 dark:text-gray-100">{{ $pendiente }}</td>
                        <td class="px-8 py-4 text-gray-900 dark:text-gray-100">{{ $item->observaciones ?? '-' }}</td>
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

<div class="space-y-4">
    @if($items->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay items registrados en renta para esta nota.</p>
    @else
        <table class="w-full text-sm text-left rtl:text-right text-body">
            <thead class="bg-neutral-secondary-soft border-b border-default">
                <tr>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300">Producto</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300 text-center">Cantidad</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300 text-center">Días Renta</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300">Fecha Renta</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300">Fecha Vencimiento</th>
                    <th class="px-8 py-2 font-medium text-gray-600 dark:text-gray-300 text-right">Importe Financiado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                @foreach($items as $item)
                    <tr>
                        <td class="px-8 py-4 text-gray-900 dark:text-gray-100">{{ $item->producto?->descripcion ?? '-' }}</td>
                        <td class="px-8 py-4 text-center text-gray-900 dark:text-gray-100">{{ $item->cantidad }}</td>
                        <td class="px-8 py-4 text-center text-gray-900 dark:text-gray-100">{{ $item->dias_renta }}</td>
                        <td class="px-8 py-4 text-gray-900 dark:text-gray-100">{{ $item->fecha_renta?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-8 py-4 text-gray-900 dark:text-gray-100">{{ $item->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-8 py-4 text-right text-gray-900 dark:text-gray-100">MXN ${{ number_format($item->precio_venta, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

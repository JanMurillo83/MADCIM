@vite(['resources/css/app.css', 'resources/js/app.js'])

<div class="cliente-pagos-pendientes space-y-6">
    <div class="rounded-lg border border-default bg-white p-4 dark:bg-gray-900">
        <h3 class="text-base font-semibold !text-black">Resumen</h3>
        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded bg-gray-100 p-3 dark:bg-gray-800">
                <div class="text-sm !text-black dark:!text-white">Cliente</div>
                <div class="text-lg font-semibold !text-black dark:!text-white">{{ $cliente->nombre }}</div>
            </div>
            <div class="rounded bg-gray-100 p-3 dark:bg-gray-800">
                <div class="text-sm !text-black dark:!text-white">Saldo registrado</div>
                <div class="text-lg font-semibold !text-black dark:!text-white">${{ number_format((float) $cliente->saldo, 2) }}</div>
            </div>
            <div class="rounded bg-gray-100 p-3 dark:bg-gray-800">
                <div class="text-sm !text-black dark:!text-white">Total pendiente (documentos abiertos)</div>
                <div class="text-lg font-semibold !text-black dark:!text-white">${{ number_format((float) $totalPendiente, 2) }}</div>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold !text-black">Notas de venta con saldo pendiente</h3>
        @if($notasVenta->isEmpty())
            <p class="mt-2 text-sm !text-black">No hay notas de venta con saldo pendiente.</p>
        @else
            <table class="mt-3 w-full text-left text-sm rtl:text-right !text-black">
                <thead class="border-b border-default bg-neutral-secondary-soft">
                    <tr>
                        <th class="px-4 py-2 font-medium">Folio</th>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium text-right">Total</th>
                        <th class="px-4 py-2 font-medium text-right">Pagado</th>
                        <th class="px-4 py-2 font-medium text-right">Saldo pendiente</th>
                        <th class="px-4 py-2 font-medium">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($notasVenta as $nota)
                        <tr>
                            <td class="px-4 py-2">{{ $nota->serie }}-{{ $nota->folio }}</td>
                            <td class="px-4 py-2">{{ $nota->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $nota->total, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) ($nota->total - $nota->saldo_pendiente), 2) }}</td>
                            <td class="px-4 py-2 text-right font-semibold">${{ number_format((float) $nota->saldo_pendiente, 2) }}</td>
                            <td class="px-4 py-2">{{ $nota->estatus ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-default bg-neutral-secondary-soft font-semibold">
                    <tr>
                        <td class="px-4 py-2" colspan="4">Total notas de venta</td>
                        <td class="px-4 py-2 text-right">${{ number_format((float) $notasVenta->sum('saldo_pendiente'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <div>
        <h3 class="text-base font-semibold !text-black">Notas de renta con saldo pendiente</h3>
        @if($notasRenta->isEmpty())
            <p class="mt-2 text-sm !text-black">No hay notas de renta con saldo pendiente.</p>
        @else
            <table class="mt-3 w-full text-left text-sm rtl:text-right !text-black">
                <thead class="border-b border-default bg-neutral-secondary-soft">
                    <tr>
                        <th class="px-4 py-2 font-medium">Folio</th>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium text-right">Total</th>
                        <th class="px-4 py-2 font-medium text-right">Pagado</th>
                        <th class="px-4 py-2 font-medium text-right">Saldo pendiente</th>
                        <th class="px-4 py-2 font-medium">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($notasRenta as $nota)
                        <tr>
                            <td class="px-4 py-2">{{ $nota->serie }}-{{ $nota->folio }}</td>
                            <td class="px-4 py-2">{{ $nota->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $nota->total, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) ($nota->total - $nota->saldo_pendiente), 2) }}</td>
                            <td class="px-4 py-2 text-right font-semibold">${{ number_format((float) $nota->saldo_pendiente, 2) }}</td>
                            <td class="px-4 py-2">{{ $nota->estatus ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-default bg-neutral-secondary-soft font-semibold">
                    <tr>
                        <td class="px-4 py-2" colspan="4">Total notas de renta</td>
                        <td class="px-4 py-2 text-right">${{ number_format((float) $notasRenta->sum('saldo_pendiente'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <div>
        <h3 class="text-base font-semibold !text-black">Pagos recibidos</h3>
        @if($pagos->isEmpty())
            <p class="mt-2 text-sm !text-black">No hay pagos registrados para este cliente.</p>
        @else
            <table class="mt-3 w-full text-left text-sm rtl:text-right !text-black">
                <thead class="border-b border-default bg-neutral-secondary-soft">
                    <tr>
                        <th class="px-4 py-2 font-medium">Folio</th>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium">Documento</th>
                        <th class="px-4 py-2 font-medium">Forma de pago</th>
                        <th class="px-4 py-2 font-medium text-right">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($pagos as $pago)
                        <tr>
                            <td class="px-4 py-2">{{ $pago->serie }}-{{ $pago->folio }}</td>
                            <td class="px-4 py-2">{{ $pago->fecha_pago?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $pago->documento_tipo }} #{{ $pago->documento_id }}</td>
                            <td class="px-4 py-2">{{ $pago->forma_pago }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $pago->importe, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-default bg-neutral-secondary-soft font-semibold">
                    <tr>
                        <td class="px-4 py-2" colspan="4">Total pagos recibidos</td>
                        <td class="px-4 py-2 text-right">${{ number_format((float) $pagos->sum('importe'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>
</div>

<x-filament-panels::page>
    <x-filament-actions::modals />

    <div
        x-data
        x-on:abrir-ticket-pago.window="window.open($event.detail.url, '_blank')"
        class="space-y-6"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Notas de venta pendientes de pago</h2>
                <p class="text-sm text-gray-500">Registra pagos de renta y venta desde una sola consulta.</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">Saldo pendiente</div>
                <div class="text-xl font-bold text-gray-950 dark:text-white">${{ number_format($this->notasPendientes->sum('saldo'), 2) }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Nota</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Saldo pendiente</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->notasPendientes as $nota)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3">{{ $nota['tipo_label'] }}</td>
                                <td class="px-4 py-3 font-medium">{{ $nota['folio'] }}</td>
                                <td class="px-4 py-3">{{ $nota['fecha'] }}</td>
                                <td class="px-4 py-3">{{ $nota['cliente'] }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($nota['total'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-warning-600">${{ number_format($nota['saldo'], 2) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <x-filament::button
                                            size="sm"
                                            color="success"
                                            icon="heroicon-o-banknotes"
                                            wire:click="abrirPago('{{ $nota['tipo'] }}', {{ $nota['id'] }})"
                                        >
                                            Pagar
                                        </x-filament::button>
                                        <x-filament::button
                                            tag="a"
                                            size="sm"
                                            color="gray"
                                            icon="heroicon-o-printer"
                                            href="{{ $nota['tipo'] === 'notas_venta_renta' ? route('notas-venta-renta.pdf.ticket', $nota['id']) : route('notas-venta-venta.pdf.ticket', $nota['id']) }}"
                                            target="_blank"
                                        >
                                            Imprimir
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay notas pendientes de pago.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

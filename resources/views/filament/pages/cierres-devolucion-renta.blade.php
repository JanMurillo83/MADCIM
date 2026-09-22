<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="overflow-x-auto rounded-xl border border-gray-700 bg-gray-800 text-white shadow-sm">
            <table class="min-w-[1100px] w-full text-sm text-white">
                <thead class="bg-gray-700 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Cliente</th>
                        <th class="px-4 py-3 text-left">Obra</th>
                        <th class="px-4 py-3 text-right">Faltantes</th>
                        <th class="px-4 py-3 text-right">Depósito</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                        <th class="px-4 py-3 text-center">Estatus</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600 text-white">
                    @forelse($this->cierres as $cierre)
                        <tr class="text-white hover:bg-gray-700/70">
                            <td class="whitespace-nowrap px-4 py-3">{{ $cierre->cerrada_en?->format('d/m/Y H:i') ?? $cierre->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $cierre->cliente?->nombre ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $cierre->direccionEntrega?->nombre_direccion ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">${{ number_format((float) $cierre->total_faltantes, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">${{ number_format((float) $cierre->deposito_acumulado, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">${{ number_format((float) $cierre->saldo_por_cobrar, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $cierre->estatus === 'Cancelado' ? 'bg-red-500 text-white' : ($cierre->estatus === 'Procesado' ? 'bg-green-600 text-white' : 'bg-amber-500 text-white') }}">
                                    {{ $cierre->estatus }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex min-w-[285px] flex-wrap justify-end gap-2">
                                    @if($cierre->estatus === 'Procesado')
                                        <x-filament::button
                                            wire:click="devolucionExtemporanea({{ $cierre->id }})"
                                            size="sm"
                                            color="warning"
                                            icon="heroicon-o-arrow-uturn-left">
                                            Integrar devolución
                                        </x-filament::button>
                                    @endif
                                    @if($cierre->estatus !== 'Cancelado')
                                        <x-filament::button
                                            wire:click="cancelarCierre({{ $cierre->id }})"
                                            wire:confirm="¿Deseas cancelar este cierre y revertir sus movimientos?"
                                            size="sm"
                                            color="danger"
                                            icon="heroicon-o-x-circle">
                                            Cancelar
                                        </x-filament::button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-white">No hay cierres de obra registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <x-filament-actions::modals />
</x-filament-panels::page>

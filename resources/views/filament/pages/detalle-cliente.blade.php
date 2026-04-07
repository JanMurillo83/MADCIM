<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                <select
                    wire:model.live="cliente_id"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">-- Seleccione un cliente --</option>
                    @foreach($this->clientes as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($this->cliente_id && $this->notasPorFecha->isNotEmpty())
            <div class="flex gap-3">
                <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">
                    Exportar PDF
                </x-filament::button>
                <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                    Exportar Excel (CSV)
                </x-filament::button>
            </div>
        @endif

        @if(!$this->cliente_id)
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-funnel class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Seleccione un cliente para ver el detalle de notas y envíos.</p>
            </div>
        @else
            @if($this->notasPorFecha->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No se encontraron notas de renta para este cliente.</p>
                </div>
            @else
                <details open class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <summary class="cursor-pointer select-none px-4 py-3 bg-gray-50 dark:bg-gray-700 flex items-center justify-between">
                        <div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Cliente: {{ $this->clientes->get($this->cliente_id) }}
                            </span>
                            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">Vista en árbol por fecha, nota de renta, nota de envío e items.</span>
                        </div>
                        <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                    </summary>
                    <div class="px-4 py-3 space-y-4">
                        @foreach($this->notasPorFecha as $fechaKey => $notas)
                            @php
                                $fechaLabel = $fechaKey === 'Sin fecha'
                                    ? 'Sin fecha'
                                    : \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaKey)->format('d/m/Y');
                                $totalNotas = $notas->count();
                                $totalEnvios = $notas->sum(fn ($nota) => $nota->notasEnvio->count());
                                $totalItems = $notas->sum(fn ($nota) => $nota->notasEnvio->sum(fn ($envio) => $envio->partidas->count()));
                            @endphp
                            <details open class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <summary class="cursor-pointer select-none px-4 py-3 bg-gray-50 dark:bg-gray-700 flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $fechaLabel }}</span>
                                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $totalNotas }} notas | {{ $totalEnvios }} envíos | {{ $totalItems }} items
                                        </span>
                                    </div>
                                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                                </summary>
                                <div class="px-4 py-3 space-y-3">
                                    @foreach($notas as $nota)
                                        <details open class="rounded-lg border border-gray-200 dark:border-gray-700">
                                            <summary class="cursor-pointer select-none px-4 py-2 bg-white dark:bg-gray-800 flex items-center justify-between">
                                                <div>
                                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                                        Nota de Renta {{ $nota->serie }}-{{ $nota->folio }}
                                                    </span>
                                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $nota->fecha_emision?->format('d/m/Y') ?? '-' }}
                                                    </span>
                                                </div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $nota->notasEnvio->count() }} envíos
                                                </span>
                                            </summary>
                                            <div class="px-4 py-3 space-y-3">
                                                @if($nota->notasEnvio->isEmpty())
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin notas de envío asociadas.</p>
                                                @else
                                                    @foreach($nota->notasEnvio as $envio)
                                                        @php
                                                            $itemsCount = $envio->partidas->count();
                                                            $totalEnviada = (float)$envio->partidas->sum('cantidad');
                                                            $totalDevuelta = (float)$envio->partidas->sum(fn ($item) => (float)($item->cantidad_devuelta ?? 0));
                                                            $totalPendiente = $totalEnviada - $totalDevuelta;
                                                        @endphp
                                                        <details open class="rounded-lg border border-gray-200 dark:border-gray-700">
                                                            <summary class="cursor-pointer select-none px-4 py-2 bg-gray-50 dark:bg-gray-700 flex items-center justify-between">
                                                                <div>
                                                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                                                        Nota de Envío {{ $envio->serie ?? '' }}{{ $envio->serie ? '-' : '' }}{{ $envio->folio }}
                                                                    </span>
                                                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                                                        {{ $envio->fecha_emision?->format('d/m/Y') ?? '-' }}
                                                                    </span>
                                                                </div>
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                    {{ $itemsCount }} items | Enviada: {{ $totalEnviada }} | Devuelta: {{ $totalDevuelta }} | Pendiente: {{ $totalPendiente }}
                                                                </div>
                                                            </summary>
                                                            <div class="px-4 py-3">
                                                                @if($envio->partidas->isEmpty())
                                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin items en esta nota de envío.</p>
                                                                @else
                                                                    <div class="overflow-x-auto">
                                                                        <table class="w-full text-sm">
                                                                            <thead class="bg-gray-100 dark:bg-gray-600">
                                                                                <tr>
                                                                                    <th class="px-3 py-2 text-left text-gray-600 dark:text-gray-300">Producto</th>
                                                                                    <th class="px-3 py-2 text-center text-gray-600 dark:text-gray-300">Cantidad enviada</th>
                                                                                    <th class="px-3 py-2 text-center text-gray-600 dark:text-gray-300">Devuelta</th>
                                                                                    <th class="px-3 py-2 text-center text-gray-600 dark:text-gray-300">Pendiente</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                                                                @foreach($envio->partidas as $item)
                                                                                    @php
                                                                                        $devuelta = (float)($item->cantidad_devuelta ?? 0);
                                                                                        $pendiente = (float)$item->cantidad - $devuelta;
                                                                                    @endphp
                                                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                                                            {{ $item->descripcion ?? ($item->producto?->descripcion ?? 'Item') }}
                                                                                        </td>
                                                                                        <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ (float)$item->cantidad }}</td>
                                                                                        <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ $devuelta }}</td>
                                                                                        <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ $pendiente }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </details>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </details>
            @endif
        @endif
    </div>
</x-filament-panels::page>

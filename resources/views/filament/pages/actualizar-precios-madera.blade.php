<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Precios base de madera entera</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Edita renta diaria y venta para recalcular la pedacería elegible.</p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Fórmula: precio base / 250 cm × largo, redondeado a pesos enteros
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($precios as $index => $precio)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $precio['label'] }}</div>
                                <div class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $precio['clave'] }}</div>
                            </div>
                            @if (!$precio['disponible'])
                                <span class="rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-950 dark:text-red-300">Falta base</span>
                            @endif
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Renta diaria</span>
                                <input type="number" min="0" step="0.01" wire:model.live="precios.{{ $index }}.renta" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            </label>
                            <label class="block">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Precio de venta</span>
                                <input type="number" min="0" step="0.01" wire:model.live="precios.{{ $index }}.venta" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="previsualizar" wire:loading.attr="disabled" class="inline-flex items-center rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 disabled:opacity-50 dark:bg-white dark:text-gray-900">
                    Previsualizar cambios
                </button>
                <button type="button" wire:click="aplicarPrecios" wire:loading.attr="disabled" wire:confirm="¿Aplicar los precios base y actualizar toda la pedacería elegible?" @disabled($this->filasListas() === 0) class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50">
                    Aplicar actualización masiva
                </button>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $this->filasListas() }} filas listas</span>
                @if ($loteAplicado !== '')
                    <span class="text-sm text-green-700 dark:text-green-400">Último lote: {{ $loteAplicado }}</span>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-1 border-b border-gray-200 px-5 py-4 dark:border-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Previsualización de pedacería</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Los cambios se aplican únicamente a las filas marcadas como listas.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3">Producto</th>
                            <th class="px-5 py-3">Familia base</th>
                            <th class="px-5 py-3 text-right">Largo</th>
                            <th class="px-5 py-3 text-right">Renta actual</th>
                            <th class="px-5 py-3 text-right">Renta nueva</th>
                            <th class="px-5 py-3 text-right">Venta actual</th>
                            <th class="px-5 py-3 text-right">Venta nueva</th>
                            <th class="px-5 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($filas as $fila)
                            <tr class="{{ $fila['listo'] ? '' : 'bg-red-50/50 dark:bg-red-950/20' }}">
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $fila['producto'] }}</div>
                                    <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $fila['clave'] }}</div>
                                </td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $fila['base_clave'] ?? '-' }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $fila['largo_cm'] ? number_format($fila['largo_cm'], 2) . ' cm' : '-' }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">${{ number_format($fila['renta_actual'], 2) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-semibold text-gray-950 dark:text-white">{{ $fila['renta_nueva'] === null ? '-' : '$' . number_format($fila['renta_nueva'], 2) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">${{ number_format($fila['venta_actual'], 2) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-semibold text-gray-950 dark:text-white">{{ $fila['venta_nueva'] === null ? '-' : '$' . number_format($fila['venta_nueva'], 2) }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $fila['listo'] ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300' }}">{{ $fila['estado'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No se encontraron productos de pedacería elegibles.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>

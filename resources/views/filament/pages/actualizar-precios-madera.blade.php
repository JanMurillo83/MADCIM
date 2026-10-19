<x-filament-panels::page>
    <div class="mc-precios-page">
        <section class="mc-precios-base">
            <div class="mc-precios-ribbon">
                <div class="mc-precios-ribbon-item mc-precios-renta">
                    <span class="mc-precios-ribbon-icon" aria-hidden="true">↻</span>
                    <div>
                        <strong>PRECIOS DE RENTA</strong>
                        <span>Actualiza únicamente rentas diarias.</span>
                    </div>
                </div>
                <div class="mc-precios-ribbon-item mc-precios-venta">
                    <span class="mc-precios-ribbon-icon" aria-hidden="true">◆</span>
                    <div>
                        <strong>PRECIOS DE VENTA</strong>
                        <span>Actualiza únicamente precios de venta.</span>
                    </div>
                </div>
            </div>

            <div class="mc-precios-base-heading">
                <div>
                    <h2>Precios base de madera entera</h2>
                    <p>Define los precios base que se usarán para recalcular la pedacería. Los cambios se aplicarán según el tipo seleccionado (renta o venta).</p>
                </div>
                <span class="mc-precios-info">ⓘ Estos precios son la base para calcular la pedacería.</span>
            </div>

            <div class="mc-precios-cards">
                @foreach ($precios as $index => $precio)
                    <div class="mc-precio-card">
                        <div class="mc-precio-card-heading">
                            <div>
                                <strong>{{ $precio['label'] }}</strong>
                                <span>{{ $precio['clave'] }}</span>
                            </div>
                            @if (!$precio['disponible'])
                                <span class="mc-precio-missing">Falta base</span>
                            @endif
                        </div>
                        <div class="mc-precio-fields">
                            <label class="mc-precio-field mc-precio-field-renta">
                                <span>Renta diaria</span>
                                <input type="text" inputmode="decimal" value="{{ number_format((float) $precio['renta'], 2, '.', ',') }}" wire:model.live="precios.{{ $index }}.renta" x-init="$nextTick(() => { if ($el.value) $el.value = Number($el.value.replaceAll(',', '')).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) })" x-on:focus="$el.value = $el.value.replaceAll(',', '')" x-on:input="$el.value = $el.value.replaceAll(',', '')" x-on:blur="if ($el.value !== '') $el.value = Number($el.value.replaceAll(',', '')).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">
                            </label>
                            <label class="mc-precio-field mc-precio-field-venta">
                                <span>Precio de venta</span>
                                <input type="text" inputmode="decimal" value="{{ number_format((float) $precio['venta'], 2, '.', ',') }}" wire:model.live="precios.{{ $index }}.venta" x-init="$nextTick(() => { if ($el.value) $el.value = Number($el.value.replaceAll(',', '')).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) })" x-on:focus="$el.value = $el.value.replaceAll(',', '')" x-on:input="$el.value = $el.value.replaceAll(',', '')" x-on:blur="if ($el.value !== '') $el.value = Number($el.value.replaceAll(',', '')).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mc-precios-actions">
                <div class="mc-precios-buttons">
                <button type="button" wire:click="previsualizar" wire:loading.attr="disabled" class="mc-precios-preview">
                    Previsualizar cambios
                </button>
                <button type="button" wire:click="aplicarPrecios" wire:loading.attr="disabled" wire:confirm="¿Aplicar los precios base y actualizar toda la pedacería elegible?" @disabled($this->filasListas() === 0) class="mc-precios-apply">
                    Aplicar actualización masiva
                </button>
                </div>
                <div class="mc-precios-summary">
                    <span><strong>{{ $this->filasListas() }}</strong><small>piezas afectadas<br>en la pedacería</small></span>
                    <span><strong>{{ count($precios) }}</strong><small>familias actualizadas<br>de madera entera</small></span>
                </div>
                @if ($loteAplicado !== '')
                    <span class="mc-precios-lote">Último lote: {{ $loteAplicado }}</span>
                @endif
            </div>
        </section>

        <section class="mc-precios-preview-section">
            <div class="mc-precios-preview-heading">
                <div>
                    <h2>Previsualización de pedacería</h2>
                    <p>Los cambios se aplican únicamente a las filas marcadas como listas.</p>
                </div>
            </div>
            <div class="mc-precios-table-wrap">
                <table class="mc-precios-table">
                    <thead>
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

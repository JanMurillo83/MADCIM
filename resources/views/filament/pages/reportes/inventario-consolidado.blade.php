<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">
                        <x-heroicon-o-cube class="h-7 w-7" />
                    </span>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-950">Inventario</h2>
                        <p class="text-sm text-gray-500">Consulta, control y administración de existencias</p>
                    </div>
                </div>
                <label class="ml-3 flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <span>Sucursal:</span>
                    <select wire:model.live="sucursal_id" @disabled(!auth()->user()?->isAdmin()) class="min-w-56 rounded-lg border-gray-300 py-2 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @if(auth()->user()?->isAdmin())<option value="">Consolidado (Todas)</option>@endif
                        @foreach($this->sucursales as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ \App\Filament\Resources\Productos\ProductosResource::getUrl('index') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">
                    <x-heroicon-o-plus class="h-4 w-4" /> Nuevo producto
                </a>
                <a href="{{ \App\Filament\Resources\Inventario\InventarioResource::getUrl('create', ['tipo' => 'ajuste', 'producto_id' => $producto_id, 'sucursal_id' => $sucursal_id]) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    <x-heroicon-o-wrench-screwdriver class="h-4 w-4" /> Ajuste de inventario
                </a>
                <button type="button" wire:click="abrirTransferencia" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    <x-heroicon-o-arrows-right-left class="h-4 w-4" /> Transferencia
                </button>
                <button type="button" wire:click="abrirKardex" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    <x-heroicon-o-book-open class="h-4 w-4" /> Kardex
                </button>
                @if($this instanceof \App\Filament\Pages\Reportes\Inventario)
                    <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">PDF</x-filament::button>
                    <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">CSV</x-filament::button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm lg:grid-cols-12">
            <label class="space-y-1 text-sm font-semibold text-gray-700 lg:col-span-3">
                Producto
                <select wire:model.live="producto_id" class="w-full rounded-lg border-gray-300 font-normal shadow-sm">
                    <option value="">-- Todos --</option>
                    @foreach($this->productos as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm font-semibold text-gray-700 lg:col-span-2">
                Línea
                <select wire:model.live="linea" class="w-full rounded-lg border-gray-300 font-normal shadow-sm">
                    <option value="">-- Todas --</option>
                    @foreach($this->lineas as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm font-semibold text-gray-700 lg:col-span-2">
                Grupo
                <select wire:model.live="grupo" class="w-full rounded-lg border-gray-300 font-normal shadow-sm">
                    <option value="">-- Todos --</option>
                    @foreach($this->grupos as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm font-semibold text-gray-700 lg:col-span-2">
                Existencia
                <select wire:model.live="existencia" class="w-full rounded-lg border-gray-300 font-normal shadow-sm">
                    <option value="todas">Todas</option>
                    <option value="positiva">Con existencia</option>
                    <option value="cero">Sin existencia</option>
                </select>
            </label>
            @if(auth()->user()?->isAdmin())
                <label class="flex cursor-pointer items-center gap-3 self-end pb-2 text-sm text-gray-700 lg:col-span-3">
                    <input type="checkbox" wire:model.live="soloDiferencias" class="h-5 w-5 rounded-full border-gray-300 text-emerald-700 focus:ring-emerald-600">
                    Mostrar solo con diferencias entre sucursales
                </label>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-emerald-900 text-left text-white">
                            <th rowspan="2" class="border-r border-emerald-800 px-3 py-3">Clave</th>
                            <th rowspan="2" class="border-r border-emerald-800 px-3 py-3">Producto</th>
                            <th rowspan="2" class="border-r border-emerald-800 px-3 py-3">Línea</th>
                            <th rowspan="2" class="border-r border-emerald-800 px-3 py-3">Grupo</th>
                            <th colspan="{{ max(1, $this->sucursalesVisibles->count()) }}" class="bg-emerald-700 px-3 py-2 text-center">Existencia por sucursal</th>
                            <th rowspan="2" class="bg-emerald-800 px-3 py-3 text-right">Total</th>
                            <th rowspan="2" class="px-3 py-3 text-right">Disponible</th>
                            <th rowspan="2" class="bg-emerald-800 px-3 py-3 text-right">Costo prom.</th>
                            <th rowspan="2" class="px-3 py-3 text-right">Último costo</th>
                            <th rowspan="2" class="bg-emerald-800 px-3 py-3 text-right">Valor inventario</th>
                            <th rowspan="2" class="px-3 py-3"></th>
                        </tr>
                        <tr class="bg-emerald-800 text-white">
                            @forelse($this->sucursalesVisibles as $sucursal)
                                <th class="whitespace-nowrap border-r border-emerald-700 px-3 py-2 text-center">⌂ {{ $sucursal->nombre }}</th>
                            @empty
                                <th class="px-3 py-2 text-center">Sin sucursales</th>
                            @endforelse
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->items as $row)
                            <tr class="transition hover:bg-emerald-50/60">
                                <td class="whitespace-nowrap px-3 py-2.5 font-semibold text-gray-800">{{ $row['clave'] }}</td>
                                <td class="min-w-56 px-3 py-2.5 text-gray-800">{{ $row['descripcion'] }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-gray-600">{{ $row['linea'] }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-gray-600">{{ $row['grupo'] }}</td>
                                @foreach($this->sucursalesVisibles as $sucursal)
                                    @php($stock = (float) ($row['existencias'][$sucursal->id] ?? 0))
                                    <td class="px-3 py-2.5 text-center {{ $row['diferente'] && $stock < $row['total'] ? 'bg-rose-50 font-semibold text-rose-700' : 'text-gray-800' }}">{{ number_format($stock, 0) }}</td>
                                @endforeach
                                <td class="bg-gray-50 px-3 py-2.5 text-right font-semibold text-gray-800">{{ number_format($row['total'], 0) }}</td>
                                <td class="bg-emerald-50 px-3 py-2.5 text-right font-semibold text-emerald-800">{{ number_format($row['disponible'], 0) }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right">${{ number_format($row['costo'], 2) }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right">${{ number_format($row['ultimo_costo'], 2) }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right font-medium">${{ number_format($row['valor'], 2) }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <div class="flex justify-center gap-1">
                                        <a title="Ajustar inventario" href="{{ \App\Filament\Resources\Inventario\InventarioResource::getUrl('create', ['tipo' => 'ajuste', 'producto_id' => $row['id'], 'sucursal_id' => $this->sucursal_id]) }}" class="rounded-md border border-gray-300 px-2 py-1 text-gray-600 hover:bg-gray-100">±</a>
                                        <button title="Ver kardex" type="button" wire:click="abrirKardex({{ $row['id'] }})" class="rounded-md border border-gray-300 px-2 py-1 text-gray-600 hover:bg-gray-100">···</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-10 text-center text-gray-500" colspan="{{ 10 + $this->sucursalesVisibles->count() }}">No hay productos que coincidan con los filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col justify-between gap-3 border-t border-gray-200 px-4 py-3 text-sm text-gray-600 md:flex-row md:items-center">
                <span>Mostrando {{ $this->totalProductos ? (($pagina - 1) * $porPagina + 1) : 0 }} – {{ min($pagina * $porPagina, $this->totalProductos) }} de {{ number_format($this->totalProductos) }} productos</span>
                <div class="flex flex-wrap items-center justify-center gap-1">
                    <button wire:click="irPagina({{ max(1, $pagina - 1) }})" @disabled($pagina <= 1) class="rounded px-2 py-1 disabled:opacity-40">«</button>
                    @for($p = max(1, $pagina - 2); $p <= min($this->totalPaginas, $pagina + 2); $p++)
                        <button wire:click="irPagina({{ $p }})" class="min-w-8 rounded px-2 py-1 {{ $p === $pagina ? 'bg-emerald-700 font-bold text-white' : 'hover:bg-gray-100' }}">{{ $p }}</button>
                    @endfor
                    <button wire:click="irPagina({{ min($this->totalPaginas, $pagina + 1) }})" @disabled($pagina >= $this->totalPaginas) class="rounded px-2 py-1 disabled:opacity-40">»</button>
                    <label class="ml-3 flex items-center gap-2">Registros por página
                        <select wire:model.live="porPagina" class="rounded-lg border-gray-300 py-1 text-sm">
                            <option value="5">5</option><option value="10">10</option><option value="25">25</option><option value="50">50</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="mb-3 flex items-center gap-2 font-bold text-gray-900"><x-heroicon-o-building-storefront class="h-5 w-5 text-emerald-700" /> Existencia por sucursal</h3>
                <table class="w-full text-xs"><thead><tr class="bg-emerald-800 text-left text-white"><th class="rounded-l px-2 py-1.5">Sucursal</th><th class="px-2 py-1.5 text-right">Existencia</th><th class="px-2 py-1.5 text-right">Apartado</th><th class="rounded-r px-2 py-1.5 text-right">Disponible</th></tr></thead>
                    <tbody>@forelse($this->existenciaPorSucursal as $sucursal)<tr class="border-b"><td class="px-2 py-2">⌂ {{ $sucursal['nombre'] }}</td><td class="px-2 py-2 text-right">{{ number_format($sucursal['existencia'], 0) }}</td><td class="px-2 py-2 text-right">{{ number_format($sucursal['apartado'], 0) }}</td><td class="px-2 py-2 text-right font-semibold text-emerald-800">{{ number_format($sucursal['disponible'], 0) }}</td></tr>@empty<tr><td colspan="4" class="px-2 py-3 text-center text-gray-500">Sin sucursales</td></tr>@endforelse</tbody>
                </table>
            </section>
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <h3 class="flex items-center gap-2 font-bold text-gray-900"><x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-600" /> Productos sin existencia</h3>
                <p class="my-4 text-center text-3xl font-extrabold text-amber-900">{{ number_format($this->productosSinExistencia) }} <span class="text-base font-semibold">productos</span></p>
                <button type="button" wire:click="$set('existencia', 'cero')" class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">Ver productos agotados →</button>
            </section>
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="mb-3 flex items-center gap-2 font-bold text-gray-900"><x-heroicon-o-arrow-trending-up class="h-5 w-5 text-emerald-700" /> Movimientos recientes</h3>
                <ul class="space-y-3 text-xs">@forelse($this->movimientosRecientes as $movimiento)<li class="flex gap-2"><span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $movimiento->tipo === 'salida' ? 'bg-rose-500' : ($movimiento->tipo === 'ajuste' ? 'bg-amber-500' : 'bg-emerald-600') }}"></span><span class="min-w-0 flex-1"><b>{{ ucfirst($movimiento->tipo) }}:</b> {{ $movimiento->producto?->clave }} · {{ number_format($movimiento->cantidad, 0) }}<small class="block text-gray-500">{{ $movimiento->sucursal?->nombre }} · {{ $movimiento->fecha_movimiento?->diffForHumans() }}</small></span></li>@empty<li class="text-gray-500">Aún no hay movimientos recientes.</li>@endforelse</ul>
                <button wire:click="abrirKardex" class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Ver movimientos →</button>
            </section>
            <section class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div><h3 class="flex items-center gap-2 font-bold text-gray-900"><x-heroicon-o-truck class="h-6 w-6 text-emerald-700" /> Transferencias entre sucursales</h3><p class="mt-3 text-sm text-gray-600">Mueve productos de una sucursal a otra y registra ambos movimientos en el kardex.</p></div>
                <button wire:click="abrirTransferencia" class="mt-4 inline-flex w-fit items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"><x-heroicon-o-arrows-right-left class="h-4 w-4" /> Nueva transferencia →</button>
            </section>
        </div>
    </div>

    @if($transferenciaAbierta)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/50 p-4" wire:key="modal-transferencia-inventario">
            <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between"><div><h2 class="text-lg font-bold text-gray-900">Transferencia entre sucursales</h2><p class="text-sm text-gray-500">Se registrará una salida y una entrada con el mismo folio.</p></div><button wire:click="$set('transferenciaAbierta', false)" class="text-2xl text-gray-400 hover:text-gray-700">×</button></div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="space-y-1 text-sm font-medium text-gray-700 md:col-span-2">Producto<select wire:model="productoTransferenciaId" class="w-full rounded-lg border-gray-300"><option value="">Selecciona un producto</option>@foreach($this->productos as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select>@error('productoTransferenciaId')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="space-y-1 text-sm font-medium text-gray-700">Sucursal origen<select wire:model="sucursalOrigenId" @disabled(!auth()->user()?->isAdmin()) class="w-full rounded-lg border-gray-300">@foreach(\App\Models\Sucursal::query()->orderBy('nombre')->get(['id','nombre']) as $sucursal)<option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>@endforeach</select>@error('sucursalOrigenId')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="space-y-1 text-sm font-medium text-gray-700">Sucursal destino<select wire:model="sucursalDestinoId" class="w-full rounded-lg border-gray-300"><option value="">Selecciona sucursal</option>@foreach(\App\Models\Sucursal::query()->orderBy('nombre')->get(['id','nombre']) as $sucursal)<option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>@endforeach</select>@error('sucursalDestinoId')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="space-y-1 text-sm font-medium text-gray-700">Cantidad<input type="number" min="1" step="1" wire:model="cantidadTransferencia" class="w-full rounded-lg border-gray-300">@error('cantidadTransferencia')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="space-y-1 text-sm font-medium text-gray-700">Motivo<input type="text" wire:model="motivoTransferencia" class="w-full rounded-lg border-gray-300" placeholder="Opcional"></label>
                </div>
                <div class="mt-6 flex justify-end gap-2"><button wire:click="$set('transferenciaAbierta', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium">Cancelar</button><button wire:click="transferirInventario" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Registrar transferencia</button></div>
            </div>
        </div>
    @endif

    @if($kardexAbierto)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/50 p-4" wire:key="modal-kardex-inventario">
            <div class="w-full max-w-6xl rounded-xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between"><div><h2 class="text-lg font-bold text-gray-900">Kardex de inventario</h2><p class="text-sm text-gray-500">{{ $productoKardexId ? ($this->productos[$productoKardexId] ?? 'Producto') : 'Movimientos recientes' }}</p></div><button wire:click="cerrarKardex" class="text-2xl text-gray-400 hover:text-gray-700">×</button></div>
                <div class="max-h-[65vh] overflow-auto rounded-lg border border-gray-200"><table class="w-full text-sm"><thead class="sticky top-0 bg-gray-100 text-left text-gray-600"><tr><th class="px-3 py-2">Fecha</th><th class="px-3 py-2">Sucursal</th><th class="px-3 py-2">Producto</th><th class="px-3 py-2">Tipo</th><th class="px-3 py-2 text-right">Cantidad</th><th class="px-3 py-2 text-right">Antes</th><th class="px-3 py-2 text-right">Después</th><th class="px-3 py-2">Referencia / motivo</th></tr></thead><tbody class="divide-y">@forelse($this->kardex as $movimiento)<tr><td class="whitespace-nowrap px-3 py-2">{{ $movimiento->fecha_movimiento?->format('d/m/Y H:i') }}</td><td class="px-3 py-2">{{ $movimiento->sucursal?->nombre }}</td><td class="px-3 py-2">{{ $movimiento->producto?->clave }} · {{ $movimiento->producto?->descripcion }}</td><td class="px-3 py-2">{{ ucfirst($movimiento->tipo) }}</td><td class="px-3 py-2 text-right">{{ number_format($movimiento->cantidad, 0) }}</td><td class="px-3 py-2 text-right">{{ number_format($movimiento->existencia_antes, 0) }}</td><td class="px-3 py-2 text-right">{{ number_format($movimiento->existencia_despues, 0) }}</td><td class="px-3 py-2">{{ $movimiento->documento_referencia ?: $movimiento->motivo }}</td></tr>@empty<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay movimientos para mostrar.</td></tr>@endforelse</tbody></table></div>
                <div class="mt-4 flex justify-end"><button wire:click="cerrarKardex" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cerrar</button></div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

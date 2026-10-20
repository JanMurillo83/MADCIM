<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4 xl:grid-cols-8">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Fecha inicio</label><input type="date" wire:model.live="fecha_inicio" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" /></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Fecha fin</label><input type="date" wire:model.live="fecha_fin" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" /></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Cliente</label><select wire:model.live="cliente_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">-- Todos --</option>@foreach($this->clientes as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
            @if(auth()->user()?->isAdmin())
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Sucursal</label><select wire:model.live="sucursal_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">-- Todas --</option>@foreach($this->sucursales as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
            @endif
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Usuario</label><select wire:model.live="usuario_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">-- Todos --</option>@foreach($this->usuarios as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Producto</label><select wire:model.live="producto_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">-- Todos --</option>@foreach($this->productos as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Linea</label><select wire:model.live="linea" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">-- Todas --</option>@foreach($this->lineas as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Grupo</label><select wire:model.live="grupo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">-- Todos --</option>@foreach($this->grupos as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        </div>

        <div class="flex gap-3">
            <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">Exportar PDF</x-filament::button>
            <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">Exportar Excel (CSV)</x-filament::button>
            <x-report-preview title="Vista previa de rentas por línea" subtitle="Periodo: {{ $fecha_inicio }} al {{ $fecha_fin }}" :preview-view="'exports.reportes.rentas-por-linea-pdf'" :preview-data="['rentas' => $this->rentas, 'totals' => ['cantidad' => $this->totalCantidad, 'total' => $this->totalTotal], 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]" />
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-gray-600">Linea</th><th class="px-4 py-2 text-left text-gray-600">Grupo</th><th class="px-4 py-2 text-left text-gray-600">Producto</th><th class="px-4 py-2 text-right text-gray-600">Cantidad</th><th class="px-4 py-2 text-right text-gray-600">Importe renta</th></tr></thead><tbody class="divide-y divide-gray-200">@forelse($this->rentas as $row)<tr class="hover:bg-gray-50"><td class="px-4 py-2">{{ $row['linea'] }}</td><td class="px-4 py-2">{{ $row['grupo'] }}</td><td class="px-4 py-2">{{ $row['clave'] }} - {{ $row['descripcion'] }}</td><td class="px-4 py-2 text-right">{{ number_format($row['cantidad'], 2) }}</td><td class="px-4 py-2 text-right">${{ number_format($row['total'], 2) }}</td></tr>@empty<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin resultados para los filtros seleccionados.</td></tr>@endforelse</tbody></table></div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2"><div class="rounded-xl border border-gray-200 bg-white p-4 shadow"><p class="text-sm text-gray-500">Cantidad total</p><p class="text-2xl font-bold text-gray-800">{{ number_format($this->totalCantidad, 2) }}</p></div><div class="rounded-xl border border-gray-200 bg-white p-4 shadow"><p class="text-sm text-gray-500">Importe total de renta</p><p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalTotal, 2) }}</p></div></div>
    </div>
</x-filament-panels::page>

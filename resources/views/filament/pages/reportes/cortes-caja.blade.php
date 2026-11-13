<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('pdf.cajas.cierre-estilos')
    <style>
        @media screen {
            .corte-sheet { background: #fff; padding: 18px; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: 0 2px 8px #0000000d; font-size: 11px; }
            .corte-sheet .title { font-size: 26px; }
            .corte-sheet .brand img { width: 210px !important; }
            .corte-sheet .date { font-size: 14px; }
            .corte-sheet .bar { font-size: 13px; padding: 7px 10px; }
            .corte-sheet .data th { font-size: 10px; }
            .corte-sheet .data th, .corte-sheet .data td { padding: 5px 8px; font-size: 11px; }
            .corte-sheet .line { padding: 6px 10px; }
            .corte-sheet .obs { height: 86px; }
            .corte-sheet .fine { font-size: 9px; }
        }
        @media screen and (max-width: 900px) {
            .corte-sheet .cols, .corte-sheet .cols tbody, .corte-sheet .cols tr, .corte-sheet .cols td { display: block; width: 100% !important; }
            .corte-sheet .cols { border-spacing: 0; }
        }
        @media print {
            .cortes-filtros, .cortes-acciones, .fi-header, .fi-sidebar { display: none !important; }
            .corte-sheet { box-shadow: none !important; break-after: page; }
            .corte-sheet:last-child { break-after: auto; }
        }
    </style>

    <div class="space-y-6">
        <div class="cortes-filtros grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Fecha inicio</label>
                <input type="date" wire:model.live="fecha_inicio" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Fecha fin</label>
                <input type="date" wire:model.live="fecha_fin" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            @if(auth()->user()?->isAdmin())
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Sucursal</label>
                    <select wire:model.live="sucursal_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Global: todas las sucursales</option>
                        @foreach($this->sucursales as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Caja</label>
                <select wire:model.live="caja_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Todas las cajas</option>
                    @foreach($this->cajas as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="cortes-acciones flex flex-wrap items-center gap-3">
            <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">Exportar PDF</x-filament::button>
            <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">Exportar Excel (CSV)</x-filament::button>
            <span class="text-sm text-gray-500">{{ $this->cortes->count() }} cortes · {{ $fecha_inicio }} al {{ $fecha_fin }}</span>
        </div>

        @forelse($this->cortesParaPresentacion as $corte)
            @php($caja = $this->cajaParaPresentacion($corte))
            @include('pdf.cajas.cierre-contenido')
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-gray-500">
                No hay cortes guardados para las fechas y filtros seleccionados.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>

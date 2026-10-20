<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .rd-shell { color: #18251c; }
        .rd-header { border: 1px solid #dfe8e1; border-radius: 1rem; background: linear-gradient(135deg, #f0f8f2, #fff 58%); box-shadow: 0 3px 12px rgb(31 61 43 / 6%); }
        .rd-eyebrow { color: #28784a; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .rd-date { color: #173b26; font-size: 1.35rem; font-weight: 800; letter-spacing: -.025em; }
        .rd-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1rem; }
        .rd-summary-card { display: flex; min-height: 7.1rem; align-items: flex-start; gap: .75rem; border: 1px solid #e1e9e2; border-radius: .85rem; background: #fff; padding: 1rem; box-shadow: 0 3px 10px rgb(31 61 43 / 5%); }
        .rd-summary-icon { display: grid; width: 2.55rem; height: 2.55rem; flex: 0 0 auto; place-items: center; border-radius: .7rem; background: #e8f5eb; color: #28784a; }
        .rd-summary-icon svg { width: 1.3rem; height: 1.3rem; }
        .rd-summary-label { color: #637168; font-size: .76rem; font-weight: 700; line-height: 1.2; }
        .rd-summary-value { margin-top: .38rem; color: #173b26; font-size: 1.45rem; font-weight: 800; letter-spacing: -.04em; }
        .rd-summary-detail { margin-top: .1rem; color: #6f7b72; font-size: .72rem; }
        .rd-table-card { overflow: hidden; border: 1px solid #e0e8e1; border-radius: .85rem; background: #fff; box-shadow: 0 3px 10px rgb(31 61 43 / 5%); }
        .rd-table-heading { border-bottom: 1px solid #e6eee7; background: #f6faf7; padding: .8rem 1rem; }
        .rd-table-heading h3 { display: flex; align-items: center; gap: .55rem; color: #214530; font-size: .9rem; font-weight: 800; }
        .rd-table-icon { display: grid; width: 1.85rem; height: 1.85rem; place-items: center; border-radius: .5rem; background: #dcefe1; color: #28784a; }
        .rd-table-icon svg { width: 1rem; height: 1rem; }
        .rd-table-card table thead { background: #edf4ef; }
        .rd-table-card table th { color: #526058; font-size: .68rem; font-weight: 800; letter-spacing: .02em; text-transform: uppercase; }
        .rd-table-card table td { color: #48584d; font-size: .78rem; }
        .rd-table-card table tbody tr:hover { background: #f7fbf8; }
        @media (max-width: 1280px) { .rd-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .rd-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>

    @php($reporte = $this->reporte)
    @php($resumen = $reporte['resumen'])

    <div class="rd-shell space-y-6">
        <div class="rd-header flex flex-col gap-4 p-5 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="rd-eyebrow">Corte operativo</p>
                <h2 class="rd-date mt-1">{{ \Carbon\Carbon::parse($reporte['fecha'])->locale('es')->translatedFormat('l d \\d\\e F \\d\\e Y') }}</h2>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Día del reporte</label>
                    <input type="date" wire:model.live="fecha" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <div class="flex gap-2">
                    <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">
                        PDF
                    </x-filament::button>
                    <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                        Excel
                    </x-filament::button>
                    <x-report-preview
                        title="Vista previa del reporte diario"
                        subtitle="Fecha: {{ $fecha }}"
                        :preview-view="'exports.reportes.reporte-diario-pdf'"
                        :preview-data="['reporte' => $reporte]"
                    />
                </div>
            </div>
        </div>

        <div class="rd-summary">
            @foreach([
                ['label' => 'Notas de renta', 'value' => $resumen['notas_renta']['cantidad'], 'detail' => '$' . number_format($resumen['notas_renta']['total'], 2), 'icon' => 'heroicon-o-document-text'],
                ['label' => 'Notas de venta', 'value' => $resumen['notas_venta']['cantidad'], 'detail' => '$' . number_format($resumen['notas_venta']['total'], 2), 'icon' => 'heroicon-o-receipt-percent'],
                ['label' => 'Notas de envío', 'value' => $resumen['notas_envio']['cantidad'], 'detail' => 'documentos', 'icon' => 'heroicon-o-truck'],
                ['label' => 'Notas de devolución', 'value' => $resumen['notas_devolucion']['cantidad'], 'detail' => number_format($resumen['notas_devolucion']['unidades'], 2) . ' unidades', 'icon' => 'heroicon-o-arrow-uturn-left'],
                ['label' => 'Notas a crédito', 'value' => $resumen['notas_credito']['cantidad'], 'detail' => '$' . number_format($resumen['notas_credito']['total'], 2), 'icon' => 'heroicon-o-credit-card'],
                ['label' => 'Productos vendidos', 'value' => number_format($resumen['productos_vendidos']['cantidad'], 2), 'detail' => '$' . number_format($resumen['productos_vendidos']['total'], 2), 'icon' => 'heroicon-o-cube'],
                ['label' => 'Productos rentados', 'value' => number_format($resumen['productos_rentados']['cantidad'], 2), 'detail' => '$' . number_format($resumen['productos_rentados']['total'], 2), 'icon' => 'heroicon-o-wrench-screwdriver'],
                ['label' => 'Pagos recibidos', 'value' => $resumen['pagos']['cantidad'], 'detail' => '$' . number_format($resumen['pagos']['total'], 2), 'icon' => 'heroicon-o-banknotes'],
                ['label' => 'Egresos', 'value' => $resumen['egresos']['cantidad'], 'detail' => '$' . number_format($resumen['egresos']['total'], 2), 'icon' => 'heroicon-o-arrow-trending-down'],
            ] as $tarjeta)
                <div class="rd-summary-card">
                    <span class="rd-summary-icon"><x-dynamic-component :component="$tarjeta['icon']" /></span>
                    <div>
                        <p class="rd-summary-label">{{ $tarjeta['label'] }}</p>
                        <p class="rd-summary-value">{{ $tarjeta['value'] }}</p>
                        <p class="rd-summary-detail">{{ $tarjeta['detail'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-reportes-diario-tabla titulo="Notas de renta" icon="heroicon-o-document-text" :encabezados="['Folio', 'Cliente', 'Total', 'Estatus']">
                @forelse($reporte['rentas'] as $row)
                    <tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de renta.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas de venta" icon="heroicon-o-receipt-percent" :encabezados="['Folio', 'Cliente', 'Total', 'Estatus']">
                @forelse($reporte['ventas'] as $row)
                    <tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de venta.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas de envío" icon="heroicon-o-truck" :encabezados="['Tipo', 'Folio', 'Cliente', 'Estatus']">
                @forelse($reporte['envios'] as $row)
                    <tr><td>{{ $row['tipo'] }}</td><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de envío.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas de devolución" icon="heroicon-o-arrow-uturn-left" :encabezados="['Folio', 'Cliente', 'Unidades', 'Estatus']">
                @forelse($reporte['devoluciones'] as $row)
                    <tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">{{ number_format($row['unidades'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de devolución.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-reportes-diario-tabla titulo="Productos vendidos" icon="heroicon-o-cube" :encabezados="['Clave', 'Producto', 'Cantidad', 'Total']">
                @forelse($reporte['productos_vendidos'] as $row)
                    <tr><td>{{ $row['clave'] }}</td><td>{{ $row['descripcion'] }}</td><td class="text-right">{{ number_format($row['cantidad'], 2) }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin productos vendidos.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Productos rentados" icon="heroicon-o-wrench-screwdriver" :encabezados="['Clave', 'Producto', 'Cantidad', 'Importe renta']">
                @forelse($reporte['productos_rentados'] as $row)
                    <tr><td>{{ $row['clave'] }}</td><td>{{ $row['descripcion'] }}</td><td class="text-right">{{ number_format($row['cantidad'], 2) }}</td><td class="text-right">${{ number_format($row['importe_renta'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin productos rentados.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-reportes-diario-tabla titulo="Pagos por forma de pago" icon="heroicon-o-banknotes" :encabezados="['Forma', 'Cantidad', 'Total']">
                @forelse($reporte['pagos'] as $row)
                    <tr><td>{{ $row['forma_pago'] }}</td><td class="text-right">{{ $row['cantidad'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-gray-500">Sin pagos recibidos.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Egresos" icon="heroicon-o-arrow-trending-down" :encabezados="['Concepto', 'Forma', 'Importe']">
                @forelse($reporte['egresos'] as $row)
                    <tr><td>{{ $row['concepto'] }}</td><td>{{ $row['metodo_pago'] }}</td><td class="text-right">${{ number_format($row['importe'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-gray-500">Sin egresos.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas a crédito" icon="heroicon-o-credit-card" :encabezados="['Tipo', 'Folio', 'Cliente', 'Total']">
                @forelse($reporte['notas_credito'] as $row)
                    <tr><td>{{ $row['tipo'] }}</td><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas a crédito.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>
        </div>
    </div>
</x-filament-panels::page>

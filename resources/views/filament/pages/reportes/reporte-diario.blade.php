<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php($reporte = $this->reporte)
    @php($resumen = $reporte['resumen'])

    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Corte operativo</p>
                <h2 class="mt-1 text-xl font-semibold text-gray-950">{{ \Carbon\Carbon::parse($reporte['fecha'])->format('d/m/Y') }}</h2>
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
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['label' => 'Notas de renta', 'value' => $resumen['notas_renta']['cantidad'], 'detail' => '$' . number_format($resumen['notas_renta']['total'], 2)],
                ['label' => 'Notas de venta', 'value' => $resumen['notas_venta']['cantidad'], 'detail' => '$' . number_format($resumen['notas_venta']['total'], 2)],
                ['label' => 'Notas de envío', 'value' => $resumen['notas_envio']['cantidad'], 'detail' => 'documentos'],
                ['label' => 'Notas de devolución', 'value' => $resumen['notas_devolucion']['cantidad'], 'detail' => number_format($resumen['notas_devolucion']['unidades'], 2) . ' unidades'],
                ['label' => 'Notas a crédito', 'value' => $resumen['notas_credito']['cantidad'], 'detail' => '$' . number_format($resumen['notas_credito']['total'], 2)],
                ['label' => 'Productos vendidos', 'value' => number_format($resumen['productos_vendidos']['cantidad'], 2), 'detail' => '$' . number_format($resumen['productos_vendidos']['total'], 2)],
                ['label' => 'Productos rentados', 'value' => number_format($resumen['productos_rentados']['cantidad'], 2), 'detail' => '$' . number_format($resumen['productos_rentados']['total'], 2)],
                ['label' => 'Pagos recibidos', 'value' => $resumen['pagos']['cantidad'], 'detail' => '$' . number_format($resumen['pagos']['total'], 2)],
                ['label' => 'Egresos', 'value' => $resumen['egresos']['cantidad'], 'detail' => '$' . number_format($resumen['egresos']['total'], 2)],
            ] as $tarjeta)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">{{ $tarjeta['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">{{ $tarjeta['value'] }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ $tarjeta['detail'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-reportes-diario-tabla titulo="Notas de renta" :encabezados="['Folio', 'Cliente', 'Total', 'Estatus']">
                @forelse($reporte['rentas'] as $row)
                    <tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de renta.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas de venta" :encabezados="['Folio', 'Cliente', 'Total', 'Estatus']">
                @forelse($reporte['ventas'] as $row)
                    <tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de venta.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas de envío" :encabezados="['Tipo', 'Folio', 'Cliente', 'Estatus']">
                @forelse($reporte['envios'] as $row)
                    <tr><td>{{ $row['tipo'] }}</td><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de envío.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas de devolución" :encabezados="['Folio', 'Cliente', 'Unidades', 'Estatus']">
                @forelse($reporte['devoluciones'] as $row)
                    <tr><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">{{ number_format($row['unidades'], 2) }}</td><td>{{ $row['estatus'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas de devolución.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-reportes-diario-tabla titulo="Productos vendidos" :encabezados="['Clave', 'Producto', 'Cantidad', 'Total']">
                @forelse($reporte['productos_vendidos'] as $row)
                    <tr><td>{{ $row['clave'] }}</td><td>{{ $row['descripcion'] }}</td><td class="text-right">{{ number_format($row['cantidad'], 2) }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin productos vendidos.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Productos rentados" :encabezados="['Clave', 'Producto', 'Cantidad', 'Importe renta']">
                @forelse($reporte['productos_rentados'] as $row)
                    <tr><td>{{ $row['clave'] }}</td><td>{{ $row['descripcion'] }}</td><td class="text-right">{{ number_format($row['cantidad'], 2) }}</td><td class="text-right">${{ number_format($row['importe_renta'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin productos rentados.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-reportes-diario-tabla titulo="Pagos por forma de pago" :encabezados="['Forma', 'Cantidad', 'Total']">
                @forelse($reporte['pagos'] as $row)
                    <tr><td>{{ $row['forma_pago'] }}</td><td class="text-right">{{ $row['cantidad'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-gray-500">Sin pagos recibidos.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Egresos" :encabezados="['Concepto', 'Forma', 'Importe']">
                @forelse($reporte['egresos'] as $row)
                    <tr><td>{{ $row['concepto'] }}</td><td>{{ $row['metodo_pago'] }}</td><td class="text-right">${{ number_format($row['importe'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-gray-500">Sin egresos.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>

            <x-reportes-diario-tabla titulo="Notas a crédito" :encabezados="['Tipo', 'Folio', 'Cliente', 'Total']">
                @forelse($reporte['notas_credito'] as $row)
                    <tr><td>{{ $row['tipo'] }}</td><td>{{ $row['folio'] }}</td><td>{{ $row['cliente'] }}</td><td class="text-right">${{ number_format($row['total'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-500">Sin notas a crédito.</td></tr>
                @endforelse
            </x-reportes-diario-tabla>
        </div>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                <select wire:model.live="cliente_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->clientes as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nota origen</label>
                <select wire:model.live="nota_origen_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todas --</option>
                    @foreach($this->notasOrigen as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Direccion de obra</label>
                <select wire:model.live="direccion_entrega_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todas --</option>
                    @foreach($this->direcciones as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3">
            <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">
                Exportar PDF
            </x-filament::button>
            <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                Exportar Excel (XLSX)
            </x-filament::button>
        </div>

        @forelse($this->grupos as $index => $grupo)
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <h2 class="text-lg font-bold text-primary-700">Concentrado de madera enviada al cliente x obra - Grupo {{ $index + 1 }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-3 text-sm">
                    <p><span class="font-semibold">Cliente:</span> {{ $grupo['resumen']['cliente'] }}</p>
                    <p><span class="font-semibold">Nota Origen:</span> {{ $grupo['resumen']['nota_origen'] }}</p>
                    <p><span class="font-semibold">Tel:</span> {{ $grupo['resumen']['telefono'] }}</p>
                    <p><span class="font-semibold">Direccion de Obra:</span> {{ $grupo['resumen']['direccion_obra'] }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#dfe8d8]">
                            <tr>
                                <th class="px-3 py-2 text-left">clave</th>
                                <th class="px-3 py-2 text-left">producto</th>
                                <th class="px-3 py-2 text-right">Cantidad Enviada</th>
                                <th class="px-3 py-2 text-right">Canti. Devuelta</th>
                                <th class="px-3 py-2 text-right">Cant. Pend. X devolver</th>
                                <th class="px-3 py-2 text-right">Importe Enviado</th>
                                <th class="px-3 py-2 text-right">Importe Devuelto</th>
                                <th class="px-3 py-2 text-right">Importe x Cobrar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($grupo['filas'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-semibold">{{ $row['clave'] }}</td>
                                    <td class="px-3 py-2">{{ $row['producto'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['cantidad_enviada'], 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['cantidad_devuelta'], 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['cantidad_pendiente'], 2) }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($row['importe_enviado'], 2) }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($row['importe_devuelto'], 2) }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($row['importe_cobrar'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-100 font-semibold">
                                <td class="px-3 py-2">TOTAL</td>
                                <td class="px-3 py-2"></td>
                                <td class="px-3 py-2 text-right">{{ number_format($grupo['totales']['cantidad_enviada'], 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($grupo['totales']['cantidad_devuelta'], 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($grupo['totales']['cantidad_pendiente'], 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($grupo['totales']['importe_enviado'], 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($grupo['totales']['importe_devuelto'], 2) }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($grupo['totales']['importe_cobrar'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl p-6 text-center text-gray-500 shadow border border-gray-200">
                Sin datos para los filtros seleccionados.
            </div>
        @endforelse

        @if(count($this->grupos) > 0)
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <h3 class="text-base font-bold text-gray-800 mb-3">Total General</h3>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-sm">
                    <div><span class="font-semibold">Cant. Enviada:</span> {{ number_format($this->totalGeneral['cantidad_enviada'], 2) }}</div>
                    <div><span class="font-semibold">Cant. Devuelta:</span> {{ number_format($this->totalGeneral['cantidad_devuelta'], 2) }}</div>
                    <div><span class="font-semibold">Cant. Pendiente:</span> {{ number_format($this->totalGeneral['cantidad_pendiente'], 2) }}</div>
                    <div><span class="font-semibold">Imp. Enviado:</span> ${{ number_format($this->totalGeneral['importe_enviado'], 2) }}</div>
                    <div><span class="font-semibold">Imp. Devuelto:</span> ${{ number_format($this->totalGeneral['importe_devuelto'], 2) }}</div>
                    <div><span class="font-semibold">Imp. x Cobrar:</span> ${{ number_format($this->totalGeneral['importe_cobrar'], 2) }}</div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

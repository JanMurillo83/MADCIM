<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                <input type="date" wire:model.live="fecha_inicio"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                <input type="date" wire:model.live="fecha_fin"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                <select wire:model.live="cliente_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->clientes as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()?->isAdmin())
<div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sucursal</label>
                <select wire:model.live="sucursal_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todas --</option>
                    @foreach($this->sucursales as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
@endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                <select wire:model.live="usuario_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->usuarios as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estatus</label>
                <select wire:model.live="estatus"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Todos --</option>
                    @foreach($this->estatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3">
            <x-filament::button wire:click="actualizarReporte" color="gray" icon="heroicon-o-arrow-path">
                Actualizar reporte
            </x-filament::button>
            <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-o-document-arrow-down">
                Exportar PDF
            </x-filament::button>
            <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-table-cells">
                Exportar Excel (CSV)
            </x-filament::button>
            <x-report-preview
                title="Vista previa de control de depósitos"
                subtitle="Periodo: {{ $fecha_inicio }} al {{ $fecha_fin }}"
                :preview-view="'exports.reportes.control-depositos-pdf'"
                :preview-data="['depositos' => $this->depositos, 'totals' => ['total_nota' => $this->totalNota, 'deposito' => $this->totalDeposito, 'devuelto' => $this->totalDepositoDevuelto, 'pendiente' => $this->totalDepositoPendiente], 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]"
            />
        </div>

        <div class="bg-white rounded-xl shadow border border-amber-200 overflow-hidden">
            <div class="px-4 py-3 bg-amber-50 border-b border-amber-200">
                <h2 class="font-semibold text-amber-900">Depósitos pendientes de cierre de obra</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-amber-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Cliente</th>
                            <th class="px-4 py-2 text-left">Obra</th>
                            <th class="px-4 py-2 text-right">Depósito acumulado</th>
                            <th class="px-4 py-2 text-right">A devolver</th>
                            <th class="px-4 py-2 text-left">Estatus</th>
                            <th class="px-4 py-2 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100">
                        @forelse($this->cierresPendientes as $cierre)
                            <tr>
                                <td class="px-4 py-2">{{ $cierre->cliente?->nombre ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $cierre->direccionEntrega?->nombre_direccion ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $cierre->deposito_acumulado, 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $cierre->deposito_a_devolver, 2) }}</td>
                                <td class="px-4 py-2">{{ $cierre->estatus }}</td>
                                <td class="px-4 py-2 text-right">
                                    <x-filament::button wire:click="abrirVerificacionDeposito({{ $cierre->id }})" size="sm" color="warning">
                                        Procesar egreso
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No hay depósitos pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div
            x-data
            x-show="$wire.cierreVerificacionId !== null"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.outside="$wire.cancelarVerificacionDeposito()">
                <h2 class="text-lg font-semibold text-gray-900">Verificar devolución</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Capture el Folio de INE del cliente para autorizar el depósito a devolver.
                </p>
                <label class="mt-4 block text-sm font-medium text-gray-700" for="folio-ine-deposito">Folio de INE</label>
                <input
                    id="folio-ine-deposito"
                    type="text"
                    wire:model.defer="folioIne"
                    wire:keydown.enter="procesarDeposito"
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    autocomplete="off"
                />
                @error('folioIne')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
                <div class="mt-5 flex justify-end gap-2">
                    <x-filament::button wire:click="cancelarVerificacionDeposito" color="gray">Cancelar</x-filament::button>
                    <x-filament::button wire:click="procesarDeposito" color="warning">Verificar y procesar</x-filament::button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600">Nota</th>
                            <th class="px-4 py-2 text-left text-gray-600">Sucursal</th>
                            <th class="px-4 py-2 text-left text-gray-600">Fecha</th>
                            <th class="px-4 py-2 text-right text-gray-600">Total de la nota</th>
                            <th class="px-4 py-2 text-right text-gray-600">Depósito</th>
                            <th class="px-4 py-2 text-right text-gray-600">Devuelto</th>
                            <th class="px-4 py-2 text-right text-gray-600">Pendiente</th>
                            <th class="px-4 py-2 text-left text-gray-600">Estado de pago</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->depositos as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $row['serie_folio'] }}</td>
                                <td class="px-4 py-2">{{ $row['sucursal'] }}</td>
                                <td class="px-4 py-2">{{ $row['fecha_emision'] }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['total_nota'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['deposito'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['deposito_devuelto'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['deposito_pendiente'], 2) }}</td>
                                <td class="px-4 py-2">{{ $row['estado_pago'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="8">Sin resultados para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Total de notas</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalNota, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Depósitos</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalDeposito, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Deposito devuelto</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalDepositoDevuelto, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow border border-gray-200">
                <p class="text-sm text-gray-500">Pendientes</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalDepositoPendiente, 2) }}</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>

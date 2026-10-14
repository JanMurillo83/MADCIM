<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Sucursal</label>
                <select wire:model.live="sucursal_id" @disabled(!auth()->user()?->isAdmin())
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @foreach($this->sucursales as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Buscar producto</label>
                <input type="search" wire:model.live.debounce.300ms="busqueda"
                    placeholder="Buscar por clave o descripción"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600">Clave</th>
                            <th class="px-4 py-2 text-left text-gray-600">Producto</th>
                            <th class="px-4 py-2 text-left text-gray-600">Linea</th>
                            <th class="px-4 py-2 text-left text-gray-600">Grupo</th>
                            <th class="px-4 py-2 text-right text-gray-600">Existencia</th>
                            <th class="px-4 py-2 text-right text-gray-600">Costo</th>
                            <th class="px-4 py-2 text-right text-gray-600">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->items as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row['clave'] }}</td>
                                <td class="px-4 py-2">{{ $row['descripcion'] }}</td>
                                <td class="px-4 py-2">{{ $row['linea'] }}</td>
                                <td class="px-4 py-2">{{ $row['grupo'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['existencia'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['costo'], 2) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($row['valor'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-6 text-center text-gray-500" colspan="7">Sin resultados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Total existencia</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($this->totalExistencia, 2) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Valor de la existencia</p>
                <p class="text-2xl font-bold text-gray-800">${{ number_format($this->totalValor, 2) }}</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>

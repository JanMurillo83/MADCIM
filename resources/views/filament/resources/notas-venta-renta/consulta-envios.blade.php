@vite(['resources/css/app.css', 'resources/js/app.js'])
<div class="space-y-6">
    {{-- Resumen general --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $enviosVigentes->count() }}</div>
            <div class="text-sm text-blue-800 dark:text-blue-300">Envíos Vigentes</div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendientes->count() }}</div>
            <div class="text-sm text-yellow-800 dark:text-yellow-300">Partidas Pendientes</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $enviosDevueltos->count() }}</div>
            <div class="text-sm text-green-800 dark:text-green-300">Envíos Devueltos</div>
        </div>
    </div>

    {{-- Notas de Envío Vigentes --}}
    <div>
        <h3 class="text-lg font-semibold mb-2 text-blue-700 dark:text-blue-400">📦 Notas de Envío Vigentes</h3>
        @if($enviosVigentes->isEmpty())
            <p class="text-sm text-gray-500 italic">No hay notas de envío vigentes.</p>
        @else
            <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <thead class="bg-blue-100 dark:bg-blue-900/40">
                    <tr>
                        <th class="px-3 py-2 text-left">Folio</th>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Estatus</th>
                        <th class="px-3 py-2 text-left">Items</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enviosVigentes as $envio)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-3 py-2 font-medium">{{ $envio->serie ?? '' }}{{ $envio->serie ? '-' : '' }}{{ $envio->folio }}</td>
                        <td class="px-3 py-2">{{ $envio->fecha_emision ? $envio->fecha_emision->format('d/m/Y') : '-' }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($envio->estatus === 'Pendiente') bg-yellow-100 text-yellow-800
                                @elseif($envio->estatus === 'Enviada') bg-blue-100 text-blue-800
                                @elseif($envio->estatus === 'Entregada') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $envio->estatus }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <ul class="list-disc list-inside">
                                @foreach($envio->partidas as $partida)
                                    @php
                                        $devuelta = (float)($partida->cantidad_devuelta ?? 0);
                                        $pendienteDev = (float)$partida->cantidad - $devuelta;
                                    @endphp
                                    <li>{{ $partida->descripcion ?? ($partida->producto->descripcion ?? 'Item') }} — Cant: {{ $partida->cantidad }} | Devuelto: {{ $devuelta }} | Pendiente: {{ $pendienteDev }}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Partidas Pendientes de Envío --}}
    <div>
        <h3 class="text-lg font-semibold mb-2 text-yellow-700 dark:text-yellow-400">⏳ Pendientes de Agregar a Notas de Envío</h3>
        @if($pendientes->isEmpty())
            <p class="text-sm text-gray-500 italic">Todas las partidas han sido enviadas.</p>
        @else
            <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <thead class="bg-yellow-100 dark:bg-yellow-900/40">
                    <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-center">Cant. Original</th>
                        <th class="px-3 py-2 text-center">Cant. Enviada</th>
                        <th class="px-3 py-2 text-center">Cant. Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendientes as $item)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-3 py-2 font-medium">{{ $item['descripcion'] }}</td>
                        <td class="px-3 py-2 text-center">{{ $item['cantidad_original'] }}</td>
                        <td class="px-3 py-2 text-center">{{ $item['cantidad_enviada'] }}</td>
                        <td class="px-3 py-2 text-center font-bold text-yellow-600">{{ $item['cantidad_pendiente'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Notas de Envío Devueltas --}}
    <div>
        <h3 class="text-lg font-semibold mb-2 text-green-700 dark:text-green-400">✅ Notas de Envío Devueltas</h3>
        @if($enviosDevueltos->isEmpty())
            <p class="text-sm text-gray-500 italic">No hay notas de envío devueltas.</p>
        @else
            <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <thead class="bg-green-100 dark:bg-green-900/40">
                    <tr>
                        <th class="px-3 py-2 text-left">Folio</th>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Estatus</th>
                        <th class="px-3 py-2 text-left">Items</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enviosDevueltos as $envio)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-3 py-2 font-medium">{{ $envio->serie ?? '' }}{{ $envio->serie ? '-' : '' }}{{ $envio->folio }}</td>
                        <td class="px-3 py-2">{{ $envio->fecha_emision ? $envio->fecha_emision->format('d/m/Y') : '-' }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                Devuelto
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <ul class="list-disc list-inside">
                                @foreach($envio->partidas as $partida)
                                    <li>{{ $partida->descripcion ?? ($partida->producto->descripcion ?? 'Item') }} — Cant: {{ $partida->cantidad }} | Devuelto: {{ (float)($partida->cantidad_devuelta ?? 0) }}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

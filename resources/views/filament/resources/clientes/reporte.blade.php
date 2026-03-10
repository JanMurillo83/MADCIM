@vite(['resources/css/app.css', 'resources/js/app.js'])

<div class="space-y-8">
    <div>
        <h3 class="text-base font-semibold !text-gray-900 dark:!text-gray-100">Datos generales</h3>
        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Clave:</span> {{ $cliente->clave }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Nombre:</span> {{ $cliente->nombre }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">RFC:</span> {{ $cliente->rfc }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Regimen:</span> {{ $cliente->regimen }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Telefono:</span> {{ $cliente->telefono }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Correo:</span> {{ $cliente->correo }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Contacto:</span> {{ $cliente->contacto }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Dias credito:</span> {{ $cliente->dias_credito }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Saldo:</span> ${{ number_format((float)$cliente->saldo, 2) }}</div>
            <div class="!text-gray-900 dark:!text-gray-100"><span class="font-medium">Direccion:</span> {{ $cliente->calle }} {{ $cliente->exterior }} {{ $cliente->interior }}, {{ $cliente->colonia }}, {{ $cliente->municipio }}, {{ $cliente->estado }}, {{ $cliente->pais }}</div>
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold !text-gray-900 dark:!text-gray-100">Direcciones de entrega</h3>
        @if($cliente->direccionesEntrega->isEmpty())
            <p class="mt-2 text-sm !text-gray-900 dark:!text-gray-100">No hay direcciones registradas.</p>
        @else
            <table class="mt-3 w-full text-sm text-left rtl:text-right !text-gray-900 dark:!text-gray-100">
                <thead class="bg-neutral-secondary-soft border-b border-default">
                    <tr>
                        <th class="px-6 py-2 font-medium">Nombre</th>
                        <th class="px-6 py-2 font-medium">Direccion</th>
                        <th class="px-6 py-2 font-medium">Contacto</th>
                        <th class="px-6 py-2 font-medium text-center">Activa</th>
                        <th class="px-6 py-2 font-medium text-center">Principal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($cliente->direccionesEntrega as $dir)
                        <tr>
                            <td class="px-6 py-3">{{ $dir->nombre_direccion }}</td>
                            <td class="px-6 py-3">
                                {{ $dir->calle }} {{ $dir->numero_exterior }} {{ $dir->numero_interior }},
                                {{ $dir->colonia }}, {{ $dir->municipio }}, {{ $dir->estado }}, {{ $dir->pais }}
                                CP {{ $dir->codigo_postal }}
                            </td>
                            <td class="px-6 py-3">{{ $dir->contacto_nombre }} {{ $dir->contacto_telefono }}</td>
                            <td class="px-6 py-3 text-center">{{ $dir->activa ? 'Si' : 'No' }}</td>
                            <td class="px-6 py-3 text-center">{{ $dir->es_principal ? 'Si' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>
        <h3 class="text-base font-semibold !text-gray-900 dark:!text-gray-100">Notas de venta (venta)</h3>
        @if($notasVenta->isEmpty())
            <p class="mt-2 text-sm !text-gray-900 dark:!text-gray-100">No hay notas de venta registradas.</p>
        @else
            <table class="mt-3 w-full text-sm text-left rtl:text-right !text-gray-900 dark:!text-gray-100">
                <thead class="bg-neutral-secondary-soft border-b border-default">
                    <tr>
                        <th class="px-6 py-2 font-medium">Folio</th>
                        <th class="px-6 py-2 font-medium">Fecha</th>
                        <th class="px-6 py-2 font-medium text-right">Total</th>
                        <th class="px-6 py-2 font-medium text-right">Pagado</th>
                        <th class="px-6 py-2 font-medium text-right">Saldo</th>
                        <th class="px-6 py-2 font-medium">Estatus pago</th>
                        <th class="px-6 py-2 font-medium">Ultimo pago</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($notasVenta as $row)
                        @php
                            $nota = $row['nota'];
                        @endphp
                        <tr>
                            <td class="px-6 py-3">{{ $nota->serie }}-{{ $nota->folio }}</td>
                            <td class="px-6 py-3">{{ $nota->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-6 py-3 text-right">${{ number_format((float)$nota->total, 2) }}</td>
                            <td class="px-6 py-3 text-right">${{ number_format((float)$row['total_pagado'], 2) }}</td>
                            <td class="px-6 py-3 text-right">${{ number_format((float)$row['saldo_pendiente'], 2) }}</td>
                            <td class="px-6 py-3">{{ $nota->estatus ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $row['ultimo_pago']?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>
        <h3 class="text-base font-semibold !text-gray-900 dark:!text-gray-100">Notas de renta</h3>
        @if($notasRenta->isEmpty())
            <p class="mt-2 text-sm !text-gray-900 dark:!text-gray-100">No hay notas de renta registradas.</p>
        @else
            <table class="mt-3 w-full text-sm text-left rtl:text-right !text-gray-900 dark:!text-gray-100">
                <thead class="bg-neutral-secondary-soft border-b border-default">
                    <tr>
                        <th class="px-6 py-2 font-medium">Folio</th>
                        <th class="px-6 py-2 font-medium">Fecha</th>
                        <th class="px-6 py-2 font-medium text-right">Total</th>
                        <th class="px-6 py-2 font-medium text-right">Pagado</th>
                        <th class="px-6 py-2 font-medium text-right">Saldo</th>
                        <th class="px-6 py-2 font-medium">Estatus pago</th>
                        <th class="px-6 py-2 font-medium">Estatus envio</th>
                        <th class="px-6 py-2 font-medium">Estatus renta</th>
                        <th class="px-6 py-2 font-medium">Ultimo pago</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($notasRenta as $row)
                        @php
                            $nota = $row['nota'];
                            $estadoRenta = $row['estado_renta'];
                            $badgeClass = match ($estadoRenta) {
                                'Vigente' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'Vencido' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'Devuelto' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'Sin registros' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-3">{{ $nota->serie }}-{{ $nota->folio }}</td>
                            <td class="px-6 py-3">{{ $nota->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-6 py-3 text-right">${{ number_format((float)$nota->total, 2) }}</td>
                            <td class="px-6 py-3 text-right">${{ number_format((float)$row['total_pagado'], 2) }}</td>
                            <td class="px-6 py-3 text-right">${{ number_format((float)$row['saldo_pendiente'], 2) }}</td>
                            <td class="px-6 py-3">{{ $nota->estatus ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $row['estado_envio'] }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ $estadoRenta }}
                                </span>
                            </td>
                            <td class="px-6 py-3">{{ $row['ultimo_pago']?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>
        <h3 class="text-base font-semibold !text-gray-900 dark:!text-gray-100">Notas rentadas (items activos)</h3>
        @if($notasRentadas->isEmpty())
            <p class="mt-2 text-sm !text-gray-900 dark:!text-gray-100">No hay notas con items activos en renta.</p>
        @else
            <table class="mt-3 w-full text-sm text-left rtl:text-right !text-gray-900 dark:!text-gray-100">
                <thead class="bg-neutral-secondary-soft border-b border-default">
                    <tr>
                        <th class="px-6 py-2 font-medium">Folio</th>
                        <th class="px-6 py-2 font-medium">Fecha</th>
                        <th class="px-6 py-2 font-medium">Estatus renta</th>
                        <th class="px-6 py-2 font-medium">Items activos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($notasRentadas as $row)
                        @php
                            $nota = $row['nota'];
                            $estadoRenta = $row['estado_renta'];
                            $badgeClass = match ($estadoRenta) {
                                'Vigente' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'Vencido' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'Devuelto' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'Sin registros' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-3">{{ $nota->serie }}-{{ $nota->folio }}</td>
                            <td class="px-6 py-3">{{ $nota->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ $estadoRenta }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                @if($row['items']->isEmpty())
                                    <span class="text-xs">-</span>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($row['items'] as $item)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $item }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div>
        <h3 class="text-base font-semibold !text-gray-900 dark:!text-gray-100">Items actualmente rentados</h3>
        @if($itemsEnRenta->isEmpty())
            <p class="mt-2 text-sm !text-gray-900 dark:!text-gray-100">No hay items activos en renta.</p>
        @else
            <table class="mt-3 w-full text-sm text-left rtl:text-right !text-gray-900 dark:!text-gray-100">
                <thead class="bg-neutral-secondary-soft border-b border-default">
                    <tr>
                        <th class="px-6 py-2 font-medium">Nota</th>
                        <th class="px-6 py-2 font-medium">Producto</th>
                        <th class="px-6 py-2 font-medium text-center">Cantidad</th>
                        <th class="px-6 py-2 font-medium text-center">Devuelto</th>
                        <th class="px-6 py-2 font-medium text-center">Pendiente</th>
                        <th class="px-6 py-2 font-medium">Estado</th>
                        <th class="px-6 py-2 font-medium">Fecha vencimiento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($itemsEnRenta as $item)
                        @php
                            $pendiente = (float)$item->cantidad - (float)($item->cantidad_devuelta ?? 0);
                        @endphp
                        <tr>
                            <td class="px-6 py-3">{{ $item->notaVentaRenta?->serie }}-{{ $item->notaVentaRenta?->folio }}</td>
                            <td class="px-6 py-3">{{ $item->producto?->descripcion ?? 'Item' }}</td>
                            <td class="px-6 py-3 text-center">{{ $item->cantidad }}</td>
                            <td class="px-6 py-3 text-center">{{ (float)($item->cantidad_devuelta ?? 0) }}</td>
                            <td class="px-6 py-3 text-center">{{ $pendiente }}</td>
                            <td class="px-6 py-3">{{ $item->estado }}</td>
                            <td class="px-6 py-3">{{ $item->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

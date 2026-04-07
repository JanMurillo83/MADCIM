<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Cliente - {{ $cliente->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 13px; margin: 12px 0 5px; padding: 6px; background: #f0f0f0; }
        h3 { font-size: 12px; margin: 10px 0 4px; }
        h4 { font-size: 11px; margin: 8px 0 4px; }
        .info { text-align: center; font-size: 10px; color: #666; margin-bottom: 12px; }
        .section { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #4a5568; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .text-center { text-align: center; }
        .muted { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Detalle del Cliente</h1>
    <p class="info">Cliente: <strong>{{ $cliente->nombre }}</strong> | Fecha: {{ now()->format('d/m/Y H:i') }}</p>

    @foreach($notasPorFecha as $fechaKey => $notas)
        @php
            $fechaLabel = $fechaKey === 'Sin fecha'
                ? 'Sin fecha'
                : \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaKey)->format('d/m/Y');
        @endphp
        <div class="section">
            <h2>Fecha de nota de renta: {{ $fechaLabel }}</h2>

            @foreach($notas as $nota)
                @php
                    $notaLabel = trim(($nota->serie ?? '') . ($nota->serie ? '-' : '') . ($nota->folio ?? ''));
                @endphp
                <h3>Nota de Renta: {{ $notaLabel }} | {{ $nota->fecha_emision?->format('d/m/Y') ?? '-' }}</h3>

                @if($nota->notasEnvio->isEmpty())
                    <p class="muted">Sin notas de envío asociadas.</p>
                @else
                    @foreach($nota->notasEnvio as $envio)
                        <h4>Nota de Envío: {{ $envio->serie ?? '' }}{{ $envio->serie ? '-' : '' }}{{ $envio->folio }} | {{ $envio->fecha_emision?->format('d/m/Y') ?? '-' }}</h4>

                        @if($envio->partidas->isEmpty())
                            <p class="muted">Sin items en esta nota de envío.</p>
                        @else
                            <table>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad enviada</th>
                                        <th class="text-center">Devuelta</th>
                                        <th class="text-center">Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($envio->partidas as $item)
                                        @php
                                            $devuelta = (float)($item->cantidad_devuelta ?? 0);
                                            $pendiente = (float)$item->cantidad - $devuelta;
                                        @endphp
                                        <tr>
                                            <td>{{ $item->descripcion ?? ($item->producto?->descripcion ?? 'Item') }}</td>
                                            <td class="text-center">{{ (float)$item->cantidad }}</td>
                                            <td class="text-center">{{ $devuelta }}</td>
                                            <td class="text-center">{{ $pendiente }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>
    @endforeach
</body>
</html>

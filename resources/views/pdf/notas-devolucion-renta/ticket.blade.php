<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota Devolucion Renta</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .title { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 8px; }
        .block { margin-bottom: 8px; }
        .row { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 4px 2px; text-align: left; }
        th { font-size: 9px; }
        .right { text-align: right; }
        .muted { color: #666; font-size: 9px; }
    </style>
</head>
<body>
    <div class="title">NOTA DE DEVOLUCION RENTA</div>

    <div class="block">
        <div class="row"><span><strong>Serie/Folio:</strong> {{ $nota->serie }}-{{ $nota->folio }}</span><span><strong>Fecha:</strong> {{ optional($nota->fecha_emision)->format('d/m/Y') }}</span></div>
        <div><strong>Cliente:</strong> {{ $nota->cliente->nombre ?? 'N/A' }}</div>
        <div><strong>Nota origen (renta):</strong> {{ $nota->notaOrigen ? (($nota->notaOrigen->serie ?? '') . $nota->notaOrigen->folio) : 'N/A' }}</div>
    </div>

    <div class="block">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Prog.</th>
                    <th class="right">Rec.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nota->partidas as $partida)
                    <tr>
                        <td>{{ $partida->descripcion ?? ($partida->producto->descripcion ?? 'Item') }}</td>
                        <td class="right">{{ number_format((float) $partida->cantidad_programada, 2) }}</td>
                        <td class="right">{{ number_format((float) $partida->cantidad_recogida, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(!empty($nota->observaciones))
        <div class="block">
            <strong>Observaciones:</strong><br>
            {{ $nota->observaciones }}
        </div>
    @endif

    <div class="muted">Documento de recoleccion basado en Nota de Renta.</div>
</body>
</html>

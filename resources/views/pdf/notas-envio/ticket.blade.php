<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Envío {{ $notaEnvio->serie }}-{{ $notaEnvio->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            margin: 0;
        }
        body {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 14px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm 2mm 5mm 2mm;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-section {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            flex-wrap: wrap;
            gap: 2px;
        }
        .info-row-block {
            flex-direction: column;
            align-items: flex-start;
        }
        .label {
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .items-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        .item-row {
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        .item-desc {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        .firma-section {
            margin-top: 20px;
            text-align: center;
        }
        .firma-linea {
            border-top: 1px solid #000;
            margin: 20px auto 5px;
            width: 80%;
        }
        .firma-label {
            font-size: 11px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 12px;
            margin-left: 0;
            margin-right: 0;
            max-width: 100%;
        }
        .legend {
            margin-top: 6px;
            font-size: 11px;
            text-align: justify;
            text-justify: inter-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">MADCIM</div>
        <div>NOTA DE ENVIO</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Folio:</span>
            <span>{{ $notaEnvio->serie }}-{{ $notaEnvio->folio }}</span>
        </div>
        <div class="info-row">
            <span class="label">Fecha:</span>
            <span>{{ $notaEnvio->fecha_emision ? $notaEnvio->fecha_emision->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
        </div>
        @if($notaEnvio->notaVentaRenta)
        <div class="info-row">
            <span class="label">Nota Renta:</span>
            <span>{{ $notaEnvio->notaVentaRenta->serie }}-{{ $notaEnvio->notaVentaRenta->folio }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="label">Cliente:</span>
            <span>{{ $notaEnvio->cliente->nombre ?? 'N/A' }}</span>
        </div>
        @if($notaEnvio->cliente && $notaEnvio->cliente->telefono)
        <div class="info-row">
            <span class="label">Telefono:</span>
            <span>{{ $notaEnvio->cliente->telefono }}</span>
        </div>
        @endif
        @if($notaEnvio->direccionEntrega)
        <div class="info-row info-row-block">
            <span class="label">Direccion de entrega:</span>
            <span>{{ $notaEnvio->direccionEntrega->direccion_completa ?? ($notaEnvio->direccionEntrega->nombre_direccion ?? 'N/A') }}</span>
        </div>
        @endif
    </div>

    <div class="items-table">
        <div class="items-header">
            <span>CONCEPTO</span>
            <span>CANT.</span>
        </div>
        @foreach($notaEnvio->partidas as $partida)
        <div class="item-row">
            <div class="item-desc">{{ $partida->producto->descripcion ?? $partida->descripcion }}</div>
            <div class="item-details">
                <span>{{ number_format($partida->cantidad, 2) }} pzas</span>
                <span>{{ number_format($partida->cantidad, 2) }}</span>
            </div>
            @if($partida->observaciones && $partida->observaciones !== $partida->descripcion)
            <div style="font-size: 11px;">{{ $partida->observaciones }}</div>
            @endif
        </div>
        @endforeach
    </div>

    @if($notaEnvio->observaciones)
    <div class="info-section">
        <div class="label">Observaciones:</div>
        <div>{{ $notaEnvio->observaciones }}</div>
    </div>
    @endif

    <div class="firma-section">
        <div class="firma-linea"></div>
        <div class="firma-label">Entrego (Chofer)</div>

        <div class="firma-linea" style="margin-top: 30px;"></div>
        <div class="firma-label">Recibio (Cliente)</div>
    </div>

    <div class="footer">
        <div>Verifique el material al momento de la entrega.</div>
        <div class="legend">
            Este documento ampara la entrega del material enlistado. Cualquier diferencia debe reportarse al momento de recibir.
        </div>
    </div>
</body>
</html>

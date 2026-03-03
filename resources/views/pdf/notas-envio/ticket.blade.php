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
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 16px;
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
        }
        .label {
            font-weight: bold;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-top: 10px;
            margin-bottom: 5px;
            border-bottom: 2px solid #000;
            padding-bottom: 3px;
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
            font-size: 11px;
        }
        .direccion-section {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
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
            font-size: 10px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">MADCIM</div>
        <div>NOTA DE ENVÍO</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Folio:</span>
            <span>{{ $notaEnvio->serie }}-{{ $notaEnvio->folio }}</span>
        </div>
        <div class="info-row">
            <span class="label">Fecha:</span>
            <span>{{ $notaEnvio->fecha_emision ? $notaEnvio->fecha_emision->format('d/m/Y') : now()->format('d/m/Y') }}</span>
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
            <span class="label">Teléfono:</span>
            <span>{{ $notaEnvio->cliente->telefono }}</span>
        </div>
        @endif
    </div>

    @if($notaEnvio->direccionEntrega)
    <div class="direccion-section">
        <div class="section-title">DIRECCIÓN DE ENTREGA</div>
        <div style="margin-top: 5px;">
            <div><span class="label">{{ $notaEnvio->direccionEntrega->nombre_direccion }}</span></div>
            @if($notaEnvio->direccionEntrega->direccion_completa)
            <div>{{ $notaEnvio->direccionEntrega->direccion_completa }}</div>
            @endif
            @if($notaEnvio->direccionEntrega->referencia)
            <div style="font-size: 9px;">Ref: {{ $notaEnvio->direccionEntrega->referencia }}</div>
            @endif
            @if($notaEnvio->direccionEntrega->contacto)
            <div style="font-size: 9px;">Contacto: {{ $notaEnvio->direccionEntrega->contacto }}</div>
            @endif
            @if($notaEnvio->direccionEntrega->telefono)
            <div style="font-size: 9px;">Tel: {{ $notaEnvio->direccionEntrega->telefono }}</div>
            @endif
        </div>
    </div>
    @endif

    <div class="items-table">
        <div class="section-title">MATERIAL A ENTREGAR</div>
        <div class="items-header">
            <span>CANT.</span>
            <span>DESCRIPCIÓN</span>
        </div>
        @foreach($notaEnvio->partidas as $partida)
        <div class="item-row">
            <div class="item-desc">{{ $partida->producto->descripcion ?? $partida->descripcion }}</div>
            <div class="item-details">
                <span>Cantidad: {{ number_format($partida->cantidad, 0) }}</span>
            </div>
            @if($partida->observaciones && $partida->observaciones !== $partida->descripcion)
            <div style="font-size: 8px; color: #666;">{{ $partida->observaciones }}</div>
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
        <div class="firma-label">Entregó (Chofer)</div>

        <div class="firma-linea" style="margin-top: 30px;"></div>
        <div class="firma-label">Recibió (Cliente)</div>
    </div>

    <div class="footer">
        <div>Verifique el material al momento de la entrega.</div>
        <div style="margin-top: 5px; font-size: 8px;">
            Documento generado el {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>

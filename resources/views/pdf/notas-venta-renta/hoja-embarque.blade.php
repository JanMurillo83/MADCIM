<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Embarque - {{ $notaVenta->serie }}-{{ $notaVenta->folio }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #333;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9pt;
            color: #666;
            line-height: 1.4;
        }
        .document-title {
            font-size: 18pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .document-number {
            font-size: 14pt;
            color: #666;
        }
        .document-date {
            font-size: 10pt;
            color: #666;
            margin-top: 5px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 10px;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
        }
        .info-title {
            font-weight: bold;
            font-size: 12pt;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 5px;
        }
        .info-row {
            margin: 6px 0;
            line-height: 1.5;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead {
            background: #2c3e50;
            color: white;
        }
        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }
        .items-table th.text-right,
        .items-table td.text-right {
            text-align: right;
        }
        .items-table th.text-center,
        .items-table td.text-center {
            text-align: center;
        }
        .items-table tbody tr {
            border-bottom: 1px solid #dee2e6;
        }
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .items-table td {
            padding: 8px;
            font-size: 10pt;
        }
        .section-title {
            font-weight: bold;
            font-size: 13pt;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 5px;
        }
        .observaciones-box {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            min-height: 60px;
        }
        .observaciones-box strong {
            color: #856404;
        }
        .firmas-section {
            display: table;
            width: 100%;
            margin-top: 60px;
        }
        .firma-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 20px;
        }
        .firma-spacer {
            display: table-cell;
            width: 10%;
        }
        .firma-linea {
            border-top: 1px solid #333;
            margin-top: 80px;
            padding-top: 8px;
        }
        .firma-titulo {
            font-weight: bold;
            font-size: 11pt;
            color: #2c3e50;
        }
        .firma-subtitulo {
            font-size: 9pt;
            color: #666;
            margin-top: 3px;
        }
        .footer {
            clear: both;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="company-name">MADCIM</div>
            <div class="company-info">
                Renta y Venta de Cimbra<br>
            </div>
        </div>
        <div class="header-right">
            <div class="document-title">HOJA DE EMBARQUE</div>
            <div class="document-number">{{ $notaVenta->serie }}-{{ $notaVenta->folio }}</div>
            <div class="document-date">Fecha: {{ $notaVenta->fecha_emision->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-left">
            <div class="info-box">
                <div class="info-title">DATOS DEL CLIENTE</div>
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    {{ $notaVenta->cliente->nombre ?? 'N/A' }}
                </div>
                @if($notaVenta->cliente && $notaVenta->cliente->telefono)
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    {{ $notaVenta->cliente->telefono }}
                </div>
                @endif
            </div>
        </div>
        <div class="info-right">
            <div class="info-box">
                <div class="info-title">DIRECCIÓN DE ENTREGA</div>
                @if($notaVenta->direccionEntrega)
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    {{ $notaVenta->direccionEntrega->nombre_direccion }}
                </div>
                <div class="info-row">
                    <span class="info-label">Dirección:</span>
                    {{ $notaVenta->direccionEntrega->direccion_completa }}
                </div>
                @if($notaVenta->direccionEntrega->referencias)
                <div class="info-row">
                    <span class="info-label">Referencias:</span>
                    {{ $notaVenta->direccionEntrega->referencias }}
                </div>
                @endif
                @if($notaVenta->direccionEntrega->contacto_nombre)
                <div class="info-row">
                    <span class="info-label">Contacto:</span>
                    {{ $notaVenta->direccionEntrega->contacto_nombre }}
                    @if($notaVenta->direccionEntrega->contacto_telefono)
                        - {{ $notaVenta->direccionEntrega->contacto_telefono }}
                    @endif
                </div>
                @endif
                @else
                <div class="info-row">Sin dirección de entrega asignada</div>
                @endif
            </div>
        </div>
    </div>

    <div class="section-title">MATERIAL A ENTREGAR</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 10%;" class="text-center">CANT.</th>
                <th style="width: 35%;">PRODUCTO</th>
                <th style="width: 12%;" class="text-center">DÍAS RENTA</th>
                <th style="width: 14%;" class="text-center">FECHA RENTA</th>
                <th style="width: 14%;" class="text-center">VENCIMIENTO</th>
                <th style="width: 10%;">OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $index => $registro)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $registro->cantidad }}</td>
                <td>{{ $registro->producto?->descripcion ?? $registro->observaciones ?? '-' }}</td>
                <td class="text-center">{{ $registro->dias_renta }}</td>
                <td class="text-center">{{ $registro->fecha_renta?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-center" style="font-weight: bold;">{{ $registro->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $registro->observaciones ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">No hay registros de renta para esta nota.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="observaciones-box">
        <strong>NOTA IMPORTANTE:</strong> Favor de verificar que el material entregado corresponda con lo descrito en esta hoja de embarque.
        Cualquier diferencia deberá reportarse al momento de la entrega.
    </div>

    <div class="firmas-section">
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-titulo">ENTREGÓ (Chofer)</div>
                <div class="firma-subtitulo">Nombre y Firma</div>
            </div>
        </div>
        <div class="firma-spacer"></div>
        <div class="firma-box">
            <div class="firma-linea">
                <div class="firma-titulo">RECIBIÓ (Cliente)</div>
                <div class="firma-subtitulo">Nombre y Firma de Conformidad</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }} | MADCIM - Renta y Venta de Cimbra</p>
    </div>
</body>
</html>

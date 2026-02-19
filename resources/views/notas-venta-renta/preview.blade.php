<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - Nota Venta Renta {{ $notaVenta->serie }}-{{ $notaVenta->folio }}</title>

    <!-- html2media Scripts -->
    <script src="{{ asset('js/html2media/jspdf-script.js') }}"></script>
    <script src="{{ asset('js/html2media/html2canvas-pro-script.js') }}"></script>
    <script src="{{ asset('js/html2media/html2media-script.js') }}"></script>
    <script src="{{ asset('js/html2media/html2media.js') }}"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
        }

        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toolbar h1 {
            font-size: 20px;
            font-weight: normal;
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .preview-container {
            margin-top: 80px;
            padding: 30px;
            display: flex;
            justify-content: center;
        }

        .ticket {
            background: white;
            width: 80mm;
            padding: 5mm;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            font-size: 11px;
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
            font-size: 10px;
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
            font-size: 10px;
        }

        .item-row {
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }

        .item-desc {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 10px;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            margin-top: 15px;
            margin-bottom: 5px;
            border-bottom: 2px solid #000;
            padding-bottom: 3px;
        }

        .rental-items {
            margin-bottom: 10px;
        }

        .rental-item {
            padding: 3px 0;
            font-size: 9px;
            border-bottom: 1px dotted #ddd;
        }

        .totals {
            margin-top: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 10px;
        }

        .total-label {
            font-weight: bold;
        }

        .deposito-row {
            background: #f0f0f0;
            padding: 2px 5px;
            margin: 2px -5px;
        }

        .grand-total {
            font-size: 12px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 9px;
        }

        .text-right {
            text-align: right;
        }

        @media print {
            .toolbar {
                display: none;
            }
            .preview-container {
                margin-top: 0;
                padding: 0;
            }
            body {
                background: white;
            }
            .ticket {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1>Vista Previa - Nota de Venta Renta {{ $notaVenta->serie }}-{{ $notaVenta->folio }}</h1>
        <div class="toolbar-actions">
            <button class="btn btn-primary" onclick="printDocument()">
                🖨️ Imprimir
            </button>
            <a href="{{ route('filament.admin.pages.ayuda-page') }}" class="btn btn-secondary">
                ← Volver a Lista
            </a>
        </div>
    </div>

    <div class="preview-container">
        <div class="ticket" id="ticket">
            <div class="header">
                <div class="company-name">MADCIM</div>
                <div>NOTA DE VENTA RENTA</div>
            </div>

            <div class="info-section">
                <div class="info-row">
                    <span class="label">Folio:</span>
                    <span>{{ $notaVenta->serie }}-{{ $notaVenta->folio }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Fecha:</span>
                    <span>{{ $notaVenta->fecha_emision->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Cliente:</span>
                    <span>{{ $notaVenta->cliente->nombre ?? 'N/A' }}</span>
                </div>
                @if($notaVenta->direccionEntrega)
                <div class="info-row">
                    <span class="label">Entrega:</span>
                    <span>
                        @if($notaVenta->direccionEntrega->nombre_direccion)
                            {{ $notaVenta->direccionEntrega->nombre_direccion }} —
                        @endif
                        {{ $notaVenta->direccionEntrega->direccion_completa }}
                    </span>
                </div>
                @if($notaVenta->direccionEntrega->contacto_nombre || $notaVenta->direccionEntrega->contacto_telefono)
                <div class="info-row">
                    <span class="label">Contacto:</span>
                    <span>
                        {{ $notaVenta->direccionEntrega->contacto_nombre }}
                        @if($notaVenta->direccionEntrega->contacto_telefono)
                            — {{ $notaVenta->direccionEntrega->contacto_telefono }}
                        @endif
                    </span>
                </div>
                @endif
                @endif
                @if($notaVenta->cliente && $notaVenta->cliente->telefono)
                <div class="info-row">
                    <span class="label">Teléfono:</span>
                    <span>{{ $notaVenta->cliente->telefono }}</span>
                </div>
                @endif
                @if($notaVenta->dias_renta)
                <div class="info-row">
                    <span class="label">Días Renta:</span>
                    <span>{{ $notaVenta->dias_renta }}</span>
                </div>
                @endif
            </div>

            <div class="items-table">
                <div class="items-header">
                    <span>CONCEPTO</span>
                    <span>TOTAL</span>
                </div>
                @foreach($notaVenta->partidas as $partida)
                <div class="item-row">
                    <div class="item-desc">{{ $partida->descripcion }}</div>
                    <div class="item-details">
                        <span>{{ number_format($partida->cantidad,2) }} x ${{ number_format($partida->valor_unitario * 1.16, 2) }}</span>
                        <span>${{ number_format($partida->valor_unitario * 1.16, 2) }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            @if($notaVenta->registrosRenta && $notaVenta->registrosRenta->count() > 0)
            <div class="rental-items">
                <div class="section-title">DETALLE DE ITEMS EN RENTA</div>
                @foreach($notaVenta->registrosRenta as $registro)
                <div class="rental-item">
                    <div style="font-weight: bold;">{{ $registro->observaciones }}</div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Cantidad: {{ $registro->cantidad }}</span>
                        <span>Días: {{ $registro->dias_renta }}</span>
                    </div>
                    <div style="font-size: 8px; color: #666;">
                        Vence: {{ $registro->fecha_vencimiento->format('d/m/Y') }}
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <div class="totals">
                <div class="total-row">
                    <span class="total-label">Total de Renta:</span>
                    <span>${{ number_format($notaVenta->subtotal * 1.16, 2) }}</span>
                </div>
                <!--<div class="total-row">
                    <span class="total-label">IVA (16%):</span>
                    <span>${{ number_format($notaVenta->impuestos_total, 2) }}</span>
                </div>-->
                <div class="total-row deposito-row">
                    <span class="total-label">Depósito:</span>
                    <span>${{ number_format($notaVenta->deposito, 2) }}</span>
                </div>
                <div class="total-row grand-total">
                    <span class="total-label">TOTAL:</span>
                    <span>${{ number_format($notaVenta->total, 2) }}</span>
                </div>
            </div>

            <div class="footer">
                <div>¡Gracias por su preferencia!</div>
                <div style="margin-top: 5px; font-size: 8px;">
                    Favor de verificar la devolución de materiales en la fecha indicada
                </div>
            </div>
        </div>
    </div>

    <script>
        function printDocument() {
            window.print();

            // Redirigir a AyudaPage después de que se cierre el diálogo de impresión
            setTimeout(function() {
                window.location.href = "{{ route('filament.admin.pages.ayuda-page') }}";
            }, 1000);
        }

        function downloadPDF() {
            const element = document.getElementById('ticket');
            const opt = {
                margin: 5,
                filename: 'nota-venta-renta-{{ $notaVenta->serie }}-{{ $notaVenta->folio }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: [80, 297], orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                // Redirigir a AyudaPage después de descargar
                setTimeout(function() {
                    window.location.href = "{{ route('filament.admin.pages.ayuda-page') }}";
                }, 500);
            });
        }
    </script>
</body>
</html>

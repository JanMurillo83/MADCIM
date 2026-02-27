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
            padding: 5mm 7mm 5mm 5mm;
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
        .legend {
            margin-top: 6px;
            font-size: 7px;
            text-align: justify;
            text-justify: inter-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .text-right {
            text-align: right;
        }

        @media print {
            .toolbar {
                display: none;
            }
            #pagoModal {
                display: none !important;
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
            <a href="{{ route('filament.admin.resources.notas-venta-renta.notas-venta-rentas.index') }}" class="btn btn-secondary">
                ← Volver a Lista
            </a>
        </div>
    </div>

    <div class="preview-container">
        <div class="ticket" id="ticket">
            <div class="header">
                <div class="company-name">MADCIM</div>
                <div>NOTA DE RENTA</div>
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
                @if($notaVenta->cliente && $notaVenta->cliente->telefono)
                <div class="info-row">
                    <span class="label">Teléfono:</span>
                    <span>{{ $notaVenta->cliente->telefono }}</span>
                </div>
                @endif
                @if($notaVenta->direccionEntrega)
                <div class="info-row info-row-block">
                    <span class="label">Direccion de entrega:</span>
                    <span>{{ $notaVenta->direccionEntrega->direccion_completa }}</span>
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
                        <span>{{ number_format($partida->cantidad, 2) }} x ${{ number_format($partida->valor_unitario, 2) }}</span>
                        <span>${{ number_format($partida->total, 2) }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="totals">
                @php
                    $impuestosTotal = $notaVenta->impuestos_total ?? 0;
                    $subtotalConImpuestos = ($notaVenta->subtotal ?? 0) + $impuestosTotal;
                @endphp
                <div class="total-row">
                    <span class="total-label">Subtotal Partidas:</span>
                    <span>${{ number_format($subtotalConImpuestos, 2) }}</span>
                </div>
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
                <div class="legend">
                    Recibi de MADERERIA MADCIM la MADERA o EQUIPO aqui especificada (o) en calidad de ARRENDAMIENTO por el tiempo especificado en este documento. Asi mismo me obligo a cuidarla (o) y devolverla (o) en buen estado en tiempo y forma, de lo contrario en caso de EXTRAVIARLA (o) o NO DEVOLVERLA (o) me OBLIGO a pagar el monto en dinero que cubra el valor de la MADERA o EQUIPO NO DEVUELTO.
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Pago de Contado --}}
    @if(strtolower($notaVenta->condicion_pago ?? '') === 'contado' && $notaVenta->saldo_pendiente > 0)
    <div id="pagoModal" style="display:flex; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:30px; width:450px; max-width:95%; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <h2 style="margin:0 0 20px; font-size:20px; text-align:center; color:#2c3e50;">💰 Registro de Pago — Contado</h2>
            <p style="text-align:center; color:#666; margin-bottom:20px;">Nota: {{ $notaVenta->serie }}-{{ $notaVenta->folio }}</p>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#333;">Total de la Nota</label>
                <input type="text" id="pagoTotal" value="${{ number_format($notaVenta->total, 2) }}" readonly
                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; background:#f5f5f5; font-size:18px; font-weight:bold; text-align:right;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#333;">Método de Pago</label>
                <select id="pagoMetodo" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#333;">Pago Recibido</label>
                <input type="number" id="pagoRecibido" step="0.01" min="0" value="{{ $notaVenta->total }}"
                    oninput="calcularCambio()"
                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:18px; text-align:right;">
            </div>

            <div id="cambioCont" style="margin-bottom:20px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; color:#333;">Cambio</label>
                <input type="text" id="pagoCambio" value="$0.00" readonly
                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; background:#e8f5e9; font-size:18px; font-weight:bold; text-align:right; color:#2e7d32;">
            </div>

            <div id="pagoError" style="display:none; color:#c62828; background:#ffebee; padding:10px; border-radius:6px; margin-bottom:15px; text-align:center;"></div>

            <div style="display:flex; gap:10px;">
                <button onclick="procesarPago()" id="btnPagar"
                    style="flex:1; padding:12px; background:#27ae60; color:#fff; border:none; border-radius:6px; font-size:16px; font-weight:bold; cursor:pointer;">
                    ✅ Registrar Pago
                </button>
                <button onclick="omitirPago()"
                    style="flex:1; padding:12px; background:#95a5a6; color:#fff; border:none; border-radius:6px; font-size:16px; cursor:pointer;">
                    Omitir
                </button>
            </div>
        </div>
    </div>
    @endif

    <script>
        var totalNota = {{ (float) $notaVenta->total }};

        function calcularCambio() {
            var recibido = parseFloat(document.getElementById('pagoRecibido').value) || 0;
            var cambio = recibido - totalNota;
            document.getElementById('pagoCambio').value = '$' + (cambio >= 0 ? cambio.toFixed(2) : '0.00');

            var metodo = document.getElementById('pagoMetodo') ? document.getElementById('pagoMetodo').value : 'Efectivo';
            var cambioCont = document.getElementById('cambioCont');
            if (cambioCont) {
                cambioCont.style.display = (metodo === 'Efectivo') ? 'block' : 'none';
            }
        }

        if (document.getElementById('pagoMetodo')) {
            document.getElementById('pagoMetodo').addEventListener('change', function() {
                calcularCambio();
                if (this.value !== 'Efectivo') {
                    document.getElementById('pagoRecibido').value = totalNota.toFixed(2);
                    calcularCambio();
                }
            });
        }

        function procesarPago() {
            var recibido = parseFloat(document.getElementById('pagoRecibido').value) || 0;
            var metodo = document.getElementById('pagoMetodo') ? document.getElementById('pagoMetodo').value : 'Efectivo';

            if (recibido < totalNota && metodo === 'Efectivo') {
                document.getElementById('pagoError').style.display = 'block';
                document.getElementById('pagoError').textContent = 'El pago recibido no puede ser menor al total de la nota.';
                return;
            }

            document.getElementById('btnPagar').disabled = true;
            document.getElementById('btnPagar').textContent = 'Procesando...';
            document.getElementById('pagoError').style.display = 'none';

            fetch("{{ route('notas-venta-renta.registrar-pago', $notaVenta->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    importe: totalNota,
                    metodo_pago: metodo,
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('pagoModal').style.display = 'none';
                } else {
                    document.getElementById('pagoError').style.display = 'block';
                    document.getElementById('pagoError').textContent = data.message || 'Error al registrar el pago.';
                    document.getElementById('btnPagar').disabled = false;
                    document.getElementById('btnPagar').textContent = '✅ Registrar Pago';
                }
            })
            .catch(function(err) {
                document.getElementById('pagoError').style.display = 'block';
                document.getElementById('pagoError').textContent = 'Error de conexión: ' + err.message;
                document.getElementById('btnPagar').disabled = false;
                document.getElementById('btnPagar').textContent = '✅ Registrar Pago';
            });
        }

        function omitirPago() {
            document.getElementById('pagoModal').style.display = 'none';
        }

        function printDocument() {
            window.print();
            setTimeout(function() {
                window.location.href = "{{ route('filament.admin.resources.notas-venta-renta.notas-venta-rentas.index') }}";
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
                setTimeout(function() {
                    window.location.href = "{{ route('filament.admin.resources.notas-venta-renta.notas-venta-rentas.index') }}";
                }, 500);
            });
        }

        if (document.getElementById('pagoRecibido')) {
            calcularCambio();
        }
    </script>
</body>
</html>

<x-filament-panels::page>
    <x-filament-actions::modals />

    <style>
        .cobros-shell { display: grid; gap: 20px; }
        .cobros-heading { display: flex; justify-content: space-between; align-items: end; gap: 24px; }
        .cobros-heading h2 { margin: 0; font-size: 22px; font-weight: 700; color: #111827; }
        .cobros-heading p { margin: 5px 0 0; color: #6b7280; font-size: 13px; }
        .cobros-total { text-align: right; white-space: nowrap; }
        .cobros-total span { display: block; color: #6b7280; font-size: 12px; }
        .cobros-total strong { display: block; margin-top: 3px; color: #111827; font-size: 21px; }
        .cobros-tabs { display: flex; gap: 4px; border-bottom: 1px solid #d1d5db; }
        .cobros-tab { border: 0; border-bottom: 3px solid transparent; background: transparent; color: #6b7280; cursor: pointer; padding: 10px 16px; font-size: 14px; font-weight: 600; }
        .cobros-tab.active { border-bottom-color: #139043; color: #139043; }
        .cobros-card { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / 7%); }
        .cobros-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .cobros-table th { background: #f3f4f6; color: #4b5563; font-size: 11px; letter-spacing: .04em; padding: 12px 14px; text-align: left; text-transform: uppercase; }
        .cobros-table td { border-top: 1px solid #eef0f2; color: #1f2937; font-size: 13px; padding: 13px 14px; vertical-align: middle; }
        .cobros-table th:nth-child(1), .cobros-table td:nth-child(1) { width: 9%; }
        .cobros-table th:nth-child(2), .cobros-table td:nth-child(2) { width: 12%; }
        .cobros-table th:nth-child(3), .cobros-table td:nth-child(3) { width: 12%; }
        .cobros-table th:nth-child(4), .cobros-table td:nth-child(4) { width: 25%; }
        .cobros-table th:nth-child(5), .cobros-table td:nth-child(5) { width: 13%; text-align: right; }
        .cobros-table th:nth-child(6), .cobros-table td:nth-child(6) { width: 15%; text-align: right; }
        .cobros-table th:nth-child(7), .cobros-table td:nth-child(7) { width: 14%; text-align: right; }
        .cobros-actions { display: flex; justify-content: flex-end; gap: 7px; }
        .cobros-empty { color: #6b7280 !important; padding: 32px !important; text-align: center !important; }
        @media (max-width: 900px) { .cobros-heading { align-items: start; flex-direction: column; } .cobros-card { overflow-x: auto; } .cobros-table { min-width: 850px; } }
    </style>

    <div
        x-data
        x-on:abrir-ticket-pago.window="window.open($event.detail.url, '_blank')"
        class="cobros-shell"
    >
        <div class="cobros-heading">
            <div>
                <h2>Notas pendientes de pago</h2>
                <p>Registra pagos de renta y venta desde una sola consulta.</p>
            </div>
            <div class="cobros-total">
                <span>Saldo pendiente visible</span>
                <strong>${{ number_format($this->notasDeLaPestana()->sum('saldo'), 2) }}</strong>
            </div>
        </div>

        <div class="cobros-tabs">
            <button type="button" class="cobros-tab {{ $pestana === 'hoy' ? 'active' : '' }}" wire:click="$set('pestana', 'hoy')">Trabajo de hoy</button>
            <button type="button" class="cobros-tab {{ $pestana === 'anteriores' ? 'active' : '' }}" wire:click="$set('pestana', 'anteriores')">Cuentas por cobrar</button>
        </div>

        <div class="cobros-card">
            <div class="overflow-x-auto">
                <table class="cobros-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Nota</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Saldo pendiente</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->notasDeLaPestana() as $nota)
                            <tr>
                                <td>{{ $nota['tipo_label'] }}</td>
                                <td><strong>{{ $nota['folio'] }}</strong></td>
                                <td>{{ $nota['fecha'] }}</td>
                                <td>{{ $nota['cliente'] }}</td>
                                <td>${{ number_format($nota['total'], 2) }}</td>
                                <td><strong>${{ number_format($nota['saldo'], 2) }}</strong></td>
                                <td>
                                    <div class="cobros-actions">
                                        <x-filament::button
                                            size="sm"
                                            color="success"
                                            icon="heroicon-o-banknotes"
                                            wire:click="abrirPago('{{ $nota['tipo'] }}', {{ $nota['id'] }})"
                                        >
                                            Pagar
                                        </x-filament::button>
                                        <x-filament::button
                                            tag="a"
                                            size="sm"
                                            color="gray"
                                            icon="heroicon-o-printer"
                                            href="{{ $nota['tipo'] === 'notas_venta_renta' ? route('notas-venta-renta.pdf.ticket', $nota['id']) : route('notas-venta-venta.pdf.ticket', $nota['id']) }}"
                                            target="_blank"
                                        >
                                            Imprimir
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="cobros-empty">No hay notas pendientes en esta pestaña.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

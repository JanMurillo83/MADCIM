<?php

namespace App\Filament\Resources\NotasVentaRenta\Tables;

use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Services\CierreDevolucionRentaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class NotasVentaRentaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serie')
                    ->label('Nota Origen')
                    ->searchable(
                        query: fn($query, $searchTerm) => $query->where('serie', 'like', "%{$searchTerm}%")
                            ->orWhere('folio', 'like', "%{$searchTerm}%"),
                    )
                ->getStateUsing(fn ($record) => $record->serie.$record->folio),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_emision')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('deposito')
                    ->label('Depósito')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable(),
                TextColumn::make('saldo_pendiente')
                    ->label('Saldo Pendiente')
                    ->numeric(decimalPlaces: 2,thousandsSeparator: ',')
                    ->prefix('$')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                TextColumn::make('estatus_pago')
                    ->label('Estatus Pago')
                    ->badge()
                    ->getStateUsing(function (NotasVentaRenta $record) {
                        if ($record->estatus === 'Cancelada') return 'Cancelada';
                        if ((float)$record->saldo_pendiente <= 0) return 'Pagada';
                        return 'Crédito';
                    })
                    ->colors([
                        'success' => 'Pagada',
                        'warning' => 'Crédito',
                        'danger' => 'Cancelada',
                    ]),
                TextColumn::make('estatus_envio')
                    ->label('Estatus Envío')
                    ->badge()
                    ->getStateUsing(function (NotasVentaRenta $record) {
                        $partidas = $record->partidas;
                        if ($partidas->isEmpty()) return 'Sin partidas';

                        $envios = NotaEnvio::where('nota_venta_renta_id', $record->id)->get();
                        if ($envios->isEmpty()) return 'Pendiente.';

                        // Calcular cantidades enviadas
                        $totalOriginal = 0;
                        $totalEnviado = 0;
                        foreach ($partidas as $partida) {
                            $totalOriginal += (float)$partida->cantidad;
                            $enviado = NotaEnvioPartida::whereHas('notaEnvio', function ($q) use ($record) {
                                $q->where('nota_venta_renta_id', $record->id);
                            })->where('producto_id', $partida->item)->sum('cantidad');
                            $totalEnviado += (float)$enviado;
                        }

                        if ($totalEnviado <= 0) return 'Pendiente.';

                        $envioCompleto = $totalEnviado >= $totalOriginal;

                        // Verificar estatus de entrega de las notas de envío
                        $totalEnvios = $envios->count();
                        $entregadas = $envios->where('estatus', 'Entregada')->count();

                        if ($envioCompleto && $entregadas >= $totalEnvios) return 'Entregada';
                        if ($entregadas > 0 && $entregadas < $totalEnvios) return 'Entregada Parcial';
                        if ($envioCompleto) return 'Pendiente';
                        return 'Envío Parcial';
                    })
                    ->colors([
                        'danger' => 'Pendiente.',
                        'warning' => 'Envío Parcial',
                        'info' => 'Pendiente',
                        'primary' => 'Entregada Parcial',
                        'success' => 'Entregada',
                        'gray' => 'Sin partidas',
                    ]),
                TextColumn::make('estatus_renta')
                    ->label('Estatus Renta')
                    ->badge()
                    ->getStateUsing(function (NotasVentaRenta $record) {
                        $envios = NotaEnvio::where('nota_venta_renta_id', $record->id)->get();
                        if ($envios->isEmpty()) return 'Vigente';

                        $todasDevueltas = $envios->every(fn ($e) => $e->estado_renta === 'Devuelta');
                        return $todasDevueltas ? 'Devuelta' : 'Vigente';
                    })
                    ->colors([
                        'success' => 'Vigente',
                        'gray' => 'Devuelta',
                    ]),
                TextColumn::make('uso_cfdi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('forma_pago')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metodo_pago')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('regimen_fiscal_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rfc_emisor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rfc_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('razon_social_receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cfdi_uuid')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('documentoOrigen.folio')
                    ->label('Documento origen')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Consultar')
                        ->modalWidth('full'),
                    Action::make('devolucion')
                        ->label('Registrar Devolución')
                        ->visible(false)
                        ->icon('fas-undo')
                        ->color('success')
                        //->visible(fn ($record) => $record->estatus === 'Activa' || $record->estatus === 'Pagada')
                        ->url(fn ($record) => route('notas-venta-renta.devolucion', $record->id)),
                    Action::make('imprimir_ticket')
                        ->label('Imprimir Ticket')
                        ->icon('fas-receipt')
                        ->color('info')
                        ->url(fn ($record) => route('notas-venta-renta.pdf.ticket', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('imprimir_carta')
                        ->label('Imprimir Carta')
                        ->icon('fas-file-pdf')
                        ->color('info')
                        ->url(fn ($record) => route('notas-venta-renta.pdf.carta', $record->id))
                        ->openUrlInNewTab(),
                    Action::make('registrar_pago')
                        ->label('Registrar pago')
                        ->icon('fas-dollar-sign')
                        ->color('success')
                        ->modalHeading(fn ($record) => "Registrar pago — {$record->serie}-{$record->folio}")
                        ->visible(false)
                        ->form([
                            \Filament\Forms\Components\DatePicker::make('fecha_pago')->label('Fecha de pago')->default(now())->required(),
                            \Filament\Forms\Components\Select::make('metodo_pago')->label('Método de pago')->options([
                                '01' => '01 - Efectivo',
                                '03' => '03 - Transferencia electrónica de fondos',
                                '04' => '04 - Tarjeta de crédito',
                                '28' => '28 - Tarjeta de débito',
                                '02' => '02 - Cheque nominativo',
                            ])->required()->live(),
                            \Filament\Forms\Components\TextInput::make('importe')->label('Importe')->numeric()->required()->default(fn($record) => (float) $record->saldo_pendiente),
                            \Filament\Forms\Components\TextInput::make('referencia')->label('Referencia')->maxLength(255),
                        ])
                        ->action(function ($record, array $data) {
                            $userId = Auth::id();
                            $cajaId = null;
                            if (($data['metodo_pago'] ?? null) === '01') {
                                // Buscar caja abierta del usuario
                                $cajaId = \App\Models\Caja::where('estatus', 'Abierta')
                                    ->where('usuario_apertura_id', $userId)
                                    ->value('id');
                                if (!$cajaId) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Caja no disponible')
                                        ->body('No tienes una caja abierta. Abre una caja para registrar pagos en efectivo.')
                                        ->danger()->send();
                                    return;
                                }
                            }
                            \App\Models\Pagos::create([
                                'documento_tipo' => 'notas_venta_renta',
                                'documento_id' => $record->id,
                                'cliente_id' => $record->cliente_id,
                                'fecha_pago' => $data['fecha_pago'] ?? now(),
                                'forma_pago' => $data['metodo_pago'],
                                'importe' => (float) $data['importe'],
                                'referencia' => $data['referencia'] ?? null,
                                'user_id' => $userId,
                                'caja_id' => $cajaId,
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Pago registrado')
                                ->body('Se registró el pago correctamente.')
                                ->success()->send();
                        }),
                    Action::make('consultar_envios')
                        ->label('Consultar Envíos')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Consulta de Envíos — Nota ' . $record->serie . '-' . $record->folio)
                        ->modalWidth('7xl')
                        ->modalContent(function ($record) {
                            $notaId = $record->id;

                            // Notas de envío vinculadas
                            $todosEnvios = NotaEnvio::where('nota_venta_renta_id', $notaId)
                                ->with(['partidas.producto'])
                                ->get();

                            // Vigentes: verificar desde nota_envio_partidas
                            $envioPartidasCount = NotaEnvioPartida::whereHas('notaEnvio', function ($q) use ($notaId) {
                                $q->where('nota_venta_renta_id', $notaId);
                            })->count();
                            $envioPartidasNoDevueltas = NotaEnvioPartida::whereHas('notaEnvio', function ($q) use ($notaId) {
                                $q->where('nota_venta_renta_id', $notaId);
                            })->where('estado', '!=', 'Devuelto')->count();
                            $todosRegistrosDevueltos = $envioPartidasCount > 0 && $envioPartidasNoDevueltas === 0;

                            if ($todosRegistrosDevueltos) {
                                $enviosVigentes = collect();
                                $enviosDevueltos = $todosEnvios;
                            } else {
                                $enviosVigentes = $todosEnvios;
                                $enviosDevueltos = collect();
                            }

                            // Partidas pendientes de envío
                            $partidas = $record->partidas;
                            $pendientes = collect();
                            foreach ($partidas as $partida) {
                                $yaEnviado = NotaEnvioPartida::whereHas('notaEnvio', function ($q) use ($notaId) {
                                    $q->where('nota_venta_renta_id', $notaId);
                                })->where('producto_id', $partida->item)->sum('cantidad');

                                $pendiente = (float)$partida->cantidad - (float)$yaEnviado;
                                if ($pendiente > 0) {
                                    $pendientes->push([
                                        'descripcion' => $partida->descripcion,
                                        'cantidad_original' => (float)$partida->cantidad,
                                        'cantidad_enviada' => (float)$yaEnviado,
                                        'cantidad_pendiente' => $pendiente,
                                    ]);
                                }
                            }

                            return view('filament.resources.notas-venta-renta.consulta-envios', [
                                'enviosVigentes' => $enviosVigentes,
                                'pendientes' => $pendientes,
                                'enviosDevueltos' => $enviosDevueltos,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar'),
                    Action::make('cerrar_devolucion_renta')
                        ->label('Cerrar Devolución')
                        ->icon('heroicon-o-check-circle')
                        ->color('danger')
                        ->visible(function (NotasVentaRenta $record) {
                            return $record->estatus !== 'Devuelta'
                                && $record->notasEnvio()->exists();
                        })
                        ->modalHeading(fn (NotasVentaRenta $record) => 'Cierre de Devolución - Nota ' . $record->serie . '-' . $record->folio)
                        ->modalWidth('7xl')
                        ->modalSubmitActionLabel('Confirmar Cierre de Devolución')
                        ->form(function (NotasVentaRenta $record): array {
                            $resumenData = app(CierreDevolucionRentaService::class)->obtenerResumen($record);
                            $totales = $resumenData['totales'];
                            $resumenRows = [];

                            foreach ($resumenData['rows'] as $row) {
                                $resumenRows[] = $row['producto']
                                    . ': Faltante=' . number_format((float) $row['faltante'], 2)
                                    . ' x $' . number_format((float) $row['precio_unitario'], 2)
                                    . ' = $' . number_format((float) $row['subtotal'], 2)
                                    . ' + IVA $' . number_format((float) $row['iva'], 2)
                                    . ' = $' . number_format((float) $row['total'], 2);
                            }

                            $resumen = empty($resumenRows)
                                ? 'No hay faltantes por cobrar en esta renta.'
                                : implode("\n", $resumenRows);

                            $resumen .= "\n\n--- Resumen Consolidado de la Renta ---";
                            $resumen .= "\nDepósito: $" . number_format((float) $totales['deposito'], 2);
                            $resumen .= "\nSubtotal faltantes: $" . number_format((float) $totales['subtotal_faltantes'], 2);
                            $resumen .= "\nIVA faltantes: $" . number_format((float) $totales['iva_faltantes'], 2);
                            $resumen .= "\nTotal faltantes: $" . number_format((float) $totales['total_faltantes'], 2);
                            $resumen .= "\nDepósito aplicado a faltantes: $" . number_format((float) $totales['deposito_aplicado'], 2);
                            $resumen .= "\nSaldo por cobrar al cliente: $" . number_format((float) $totales['saldo_por_cobrar'], 2);
                            $resumen .= "\nDepósito a devolver: $" . number_format((float) $totales['deposito_devolver'], 2);

                            return [
                                Placeholder::make('resumen')
                                    ->label('Resumen de Devolución')
                                    ->content($resumen),
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(3),
                            ];
                        })
                        ->action(function (NotasVentaRenta $record, array $data): void {
                            $resultado = app(CierreDevolucionRentaService::class)
                                ->cerrar($record, $data['observaciones'] ?? null, Auth::id());

                            $totales = $resultado['resumen']['totales'];

                            if (!empty($resultado['already_closed'])) {
                                Notification::make()
                                    ->title('Renta ya cerrada')
                                    ->body('La nota de renta ya había sido cerrada previamente.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $mensaje = 'Cierre de devolución consolidado procesado. ';
                            if (!empty($resultado['nota_venta_venta_id'])) {
                                $mensaje .= 'Se generó Nota de Venta por faltantes: $' . number_format((float) $totales['total_faltantes'], 2) . '. ';
                                $mensaje .= 'Depósito aplicado: $' . number_format((float) $totales['deposito_aplicado'], 2) . '. ';
                                $mensaje .= 'Saldo por cobrar: $' . number_format((float) $totales['saldo_por_cobrar'], 2) . '. ';
                            }

                            $mensaje .= 'Depósito devuelto: $' . number_format((float) $totales['deposito_devolver'], 2);

                            if ((float) $totales['deposito_devolver'] > 0 && empty($resultado['caja_usada'])) {
                                $mensaje .= ' (No se encontró caja abierta para registrar el egreso)';
                            }

                            Notification::make()
                                ->title('Cierre de devolución procesado')
                                ->body($mensaje)
                                ->success()
                                ->persistent()
                                ->send();
                        })
                        ->after(function (NotasVentaRenta $record, array $data): void {
                            session(['cierre_devolucion_observaciones_nvr_' . $record->id => $data['observaciones'] ?? null]);
                        })
                        ->successRedirectUrl(fn (NotasVentaRenta $record) => route('notas-venta-renta.cierre-devolucion-ticket', $record->id)),
                    Action::make('cancelar')
                        ->label('Cancelar Nota')
                        ->icon('fas-times-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar Nota de Venta Renta')
                        ->modalDescription('¿Estás seguro de que deseas cancelar esta nota de venta? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, cancelar')
                        ->visible(fn ($record) => $record->estatus === 'Activa')
                        ->action(function ($record) {
                            $record->update(['estatus' => 'Cancelada']);

                            Notification::make()
                                ->title('Nota cancelada')
                                ->body("La nota {$record->serie}-{$record->folio} ha sido cancelada exitosamente.")
                                ->success()
                                ->send();
                        }),
                ])
            ], RecordActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make()
                    ->createAnother(false)
                    ->label('Nuevo')
                    ->icon('fas-circle-plus')
                    ->modalWidth('full')
                    ->modalSubmitAction(function ($action) {
                        $action->icon('fas-floppy-disk');
                        $action->label('Guardar');
                        $action->extraAttributes(['style' => 'width: 150px !important;']);
                        $action->color('success');
                        return $action;
                    })->modalCancelAction(function ($action) {
                        $action->icon('fas-ban');
                        $action->label('Cancelar');
                        $action->extraAttributes(['style' => 'width: 150px !important;']);
                        $action->color('danger');
                        return $action;
                    })->after(function ($record) {
                        $record->update([
                            'estatus' => 'Activa',
                            'saldo_pendiente' => $record->total
                        ]);
                    })
                    ->successRedirectUrl(fn ($record) => route('notas-venta-renta.preview', $record->id)),
            ], HeaderActionsPosition::Bottom);
    }
}

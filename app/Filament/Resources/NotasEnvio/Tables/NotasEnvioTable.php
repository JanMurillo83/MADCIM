<?php

namespace App\Filament\Resources\NotasEnvio\Tables;

use App\Models\NotaEnvio;
use App\Models\MovimientoInventario;
use App\Models\NotaEnvioPartida;
use App\Models\RegistroRenta;
use App\Services\InventarioMovimientoService;
use App\Services\CierreDevolucionRentaService;
use App\Services\RenovacionRentaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class NotasEnvioTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('ver_detalle')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('id')
                    ->label('Folio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable()
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()?->isAdmin() ?? false),
                TextColumn::make('documento_origen')
                    ->label('Documento Origen')
                    ->getStateUsing(function (NotaEnvio $record) {
                        if ($record->nota_venta_renta_id) {
                            $nota = $record->notaVentaRenta;
                            return $nota ? 'NR ' . ($nota->serie ?? '') . $nota->folio : '-';
                        }
                        return '-';
                    })
                    ->searchable(query: fn ($query, $searchTerm) => $query),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('inicio_vigencia')
                    ->label('Inicio de Vigencia')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('vencimientos_renta')
                    ->label('Vencimientos')
                    ->state(function (NotaEnvio $record): string {
                        $vencimientos = $record->partidas
                            ->filter(fn (NotaEnvioPartida $partida): bool => (bool) $partida->fecha_vencimiento)
                            ->map(fn (NotaEnvioPartida $partida): string => $partida->fecha_vencimiento->format('d/m/Y'))
                            ->unique()
                            ->values();

                        return $vencimientos->isEmpty() ? '-' : $vencimientos->implode(', ');
                    })
                    ->wrap()
                    ->sortable(false),
                TextColumn::make('dias_vencimiento')
                    ->label('Dias Vencimiento')
                    ->state(function (NotaEnvio $record): string {
                        $partidas = $record->partidas;
                        $contenidoDevuelto = $record->estado_renta === 'Devuelta'
                            || ($partidas->isNotEmpty() && $partidas->every(
                                fn (NotaEnvioPartida $partida): bool => (float) ($partida->cantidad_devuelta ?? 0) >= (float) $partida->cantidad,
                            ));

                        if ($contenidoDevuelto) {
                            return 'Devuelta';
                        }

                        $vencimientos = $partidas
                            ->filter(fn (NotaEnvioPartida $partida): bool => (bool) $partida->fecha_vencimiento)
                            ->map(fn (NotaEnvioPartida $partida): int => Carbon::today()->diffInDays($partida->fecha_vencimiento, false));

                        if ($vencimientos->isEmpty()) {
                            return '-';
                        }

                        $dias = $vencimientos->min();

                        if ($dias > 0) {
                            return 'Faltan ' . $dias . ' dias';
                        }

                        if ($dias < 0) {
                            return 'Vencido hace ' . abs($dias) . ' dias';
                        }

                        return 'Vence hoy';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'Devuelta') {
                            return 'gray';
                        }

                        if (str_starts_with($state, 'Faltan')) {
                            return 'success';
                        }

                        if (str_starts_with($state, 'Vencido')) {
                            return 'danger';
                        }

                        return 'warning';
                    }),
                TextColumn::make('estatus')
                    ->label('Estatus de Envío')
                    ->badge()
                    ->colors([
                        'warning' => 'Pendiente',
                        'info' => 'En Tránsito',
                        'success' => 'Entregada',
                        'danger' => 'Cancelada',
                        'gray' => 'Devuelta',
                    ])->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make()
            ], HeaderActionsPosition::Bottom)
            ->recordActions([
                ActionGroup::make([
                Action::make('imprimir_ticket')
                    ->label('Imprimir Ticket')
                    ->icon('fas-receipt')
                    ->color('info')
                    ->url(fn (NotaEnvio $record) => route('notas-envio.pdf.ticket', $record->id))
                    ->openUrlInNewTab(),
                Action::make('ver_detalle')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (NotaEnvio $record) => 'Detalle de Productos en Renta - Envío Folio ' . $record->folio)
                    ->modalWidth('7xl')
                    ->modalContent(function (NotaEnvio $record) {
                        $items = $record->partidas()->with('producto')->get();
                        return view('filament.resources.notas-rentadas.detalle-items', [
                            'items' => $items,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                /*Action::make('marcar_en_transito')
                    ->label('Marcar En Tránsito')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn (NotaEnvio $record) => $record->estatus === 'Pendiente')
                    ->requiresConfirmation()
                    ->modalHeading(fn (NotaEnvio $record) => 'Marcar En Tránsito - Envío Folio ' . $record->folio)
                    ->modalDescription('¿Confirma que este envío está en tránsito?')
                    ->modalSubmitActionLabel('Confirmar')
                    ->action(function (NotaEnvio $record) {
                        $record->update(['estatus' => 'Pendiente']);
                        Notification::make()
                            ->title('Estatus actualizado')
                            ->body('El envío Folio ' . $record->folio . ' ha sido marcado como En Tránsito.')
                            ->success()
                            ->send();
                    }),*/
                Action::make('marcar_entregada')
                    ->label('Marcar Entregada')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (NotaEnvio $record) => in_array($record->estatus, ['Enviada', 'Pendiente', 'En Tránsito']))
                    ->requiresConfirmation()
                    ->modalHeading(fn (NotaEnvio $record) => 'Marcar Entregada - Envío Folio ' . $record->folio)
                    ->modalDescription('¿Confirma que este envío ha sido entregado?')
                    ->modalSubmitActionLabel('Confirmar')
                    ->action(function (NotaEnvio $record) {
                        $record->update(['estatus' => 'Entregada']);
                        Notification::make()
                            ->title('Estatus actualizado')
                            ->body('El envío Folio ' . $record->folio . ' ha sido marcado como Entregada.')
                            ->success()
                            ->send();
                    }),
                Action::make('crear_nota_devolucion')
                    ->label('Devolución')
                    ->icon('heroicon-o-document-plus')
                    ->color('info')
                    ->visible(function (NotaEnvio $record) {
                        return (bool) $record->nota_venta_renta_id
                            && $record->estatus === 'Entregada'
                            && $record->partidas()->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')->exists();
                    })
                    ->url(fn (NotaEnvio $record) => route('notas-venta-renta.devolucion', $record->nota_venta_renta_id))
                    ->openUrlInNewTab(),
                Action::make('devolver')
                    ->label('Devolución Parcial')
                    ->visible(false)
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(false)
                    ->modalHeading(fn (NotaEnvio $record) => 'Devolución Parcial - Envío Folio ' . $record->folio)
                    ->modalDescription('Registre las cantidades que se devuelven en esta entrega parcial.')
                    ->modalWidth('7xl')
                    ->modalSubmitActionLabel('Registrar Devolución Parcial')
                    ->form(function (NotaEnvio $record): array {
                        $nota = $record->notaVentaRenta;
                        if (!$nota) return [];
                        $items = $record->partidas()
                            ->where('estado', '!=', 'Devuelto')
                            ->with('producto')
                            ->get();

                        $fields = [];
                        $fields[] = Section::make('Material a devolver')
                            ->description('Ingrese la cantidad que se devuelve en esta entrega. Puede hacer múltiples devoluciones parciales antes del cierre.')
                            ->schema(
                                $items->flatMap(function ($item) {
                                    $precioVenta = $item->producto ? (float)$item->producto->precio_venta : 0;
                                    $pendiente = (float)$item->cantidad - (float)$item->cantidad_devuelta;
                                    return [
                                        Placeholder::make('desc_' . $item->id)
                                            ->label($item->producto ? $item->producto->descripcion : ($item->observaciones ?? 'Producto'))
                                            ->content('Rentado: ' . $item->cantidad . ' | Ya devuelto: ' . (float)$item->cantidad_devuelta . ' | Pendiente: ' . $pendiente . ' | P.V. unit: $' . number_format($precioVenta, 2))
                                            ->columnSpan(2),
                                        Hidden::make('item_id_' . $item->id)
                                            ->default($item->id),
                                        TextInput::make('cantidad_devuelta_' . $item->id)
                                            ->label('Cantidad a devolver ahora')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue($pendiente)
                                            ->default(0)
                                            ->required()
                                            ->columnSpan(1),
                                    ];
                                })->toArray()
                            )->columns(3);

                        $fields[] = Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3);

                        return $fields;
                    })
                    ->action(function (NotaEnvio $record, array $data) {
                        if ($record->estatus !== 'Entregada') {
                            Notification::make()
                                ->title('Envío no entregado')
                                ->body('No se puede registrar una devolución parcial hasta marcar la Nota de Envío como Entregada.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $nota = $record->notaVentaRenta;
                        if (!$nota) return;
                        $items = $record->partidas()
                            ->where('estado', '!=', 'Devuelto')
                            ->with('producto')
                            ->get();

                        $totalDevueltoAhora = 0;
                        foreach ($items as $item) {
                            $cantidadAhora = (float)($data['cantidad_devuelta_' . $item->id] ?? 0);
                            if ($cantidadAhora > 0) {
                                $nuevaCantidadDevuelta = (float)$item->cantidad_devuelta + $cantidadAhora;
                                $estado = $nuevaCantidadDevuelta >= (float)$item->cantidad ? 'Devuelto' : 'Activo';
                                $item->update([
                                    'cantidad_devuelta' => $nuevaCantidadDevuelta,
                                    'estado' => $estado,
                                ]);
                                $totalDevueltoAhora += $cantidadAhora;
                            }
                        }

                        if ($totalDevueltoAhora == 0) {
                            Notification::make()
                                ->title('Sin cambios')
                                ->body('No se registró ninguna devolución.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $pendientes = $record->partidas()
                            ->where('estado', '!=', 'Devuelto')
                            ->count();

                        $mensaje = 'Devolución parcial registrada exitosamente.';
                        if ($pendientes === 0) {
                            $mensaje .= ' Todos los items han sido devueltos. Puede proceder al cierre de devolución.';
                        } else {
                            $mensaje .= ' Quedan ' . $pendientes . ' producto(s) con material pendiente por devolver.';
                        }

                        Notification::make()
                            ->title('Devolución parcial registrada')
                            ->body($mensaje)
                            ->success()
                            ->send();
                    }),
                Action::make('cerrar_devolucion')
                    ->label('Cerrar Devolución')
                    ->icon('heroicon-o-check-circle')
                    ->color('danger')
                    ->visible(false)
                    ->modalHeading(fn (NotaEnvio $record) => 'Cierre de Devolución - Envío Folio ' . $record->folio)
                    ->modalWidth('7xl')
                    ->modalSubmitActionLabel('Confirmar Cierre de Devolución')
                    ->form(function (NotaEnvio $record): array {
                        $nota = $record->notaVentaRenta;
                        if (!$nota) return [];
                        $resumenData = app(CierreDevolucionRentaService::class)->obtenerResumen($nota);
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
                    ->action(function (NotaEnvio $record, array $data) {
                        $nota = $record->notaVentaRenta;
                        if (!$nota) return;
                        $servicioCierre = app(CierreDevolucionRentaService::class);
                        $resultado = $nota->direccion_entrega_id
                            ? $servicioCierre->cerrarPorObra(
                                $nota->cliente_id,
                                $nota->direccion_entrega_id,
                                $data['observaciones'] ?? null,
                                Auth::id(),
                                sucursalId: $nota->sucursal_id,
                            )
                            : $servicioCierre->cerrar($nota, $data['observaciones'] ?? null, Auth::id());

                        $totales = $resultado['resumen']['totales'];
                        session(['cierre_devolucion_resumen_nvr_' . $resultado['nota_id'] => $resultado['resumen']]);

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

                        // Guardar observaciones en sesión para el ticket
                        session(['cierre_devolucion_observaciones_' . $record->id => $data['observaciones'] ?? null]);

                        Notification::make()
                            ->title('Cierre de devolución procesado')
                            ->body($mensaje)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('cancelar')
                    ->label('Cancelar Envío')
                    ->icon('fas-times-circle')
                    ->color('danger')
                    ->visible(fn (NotaEnvio $record): bool => $record->estatus !== 'Cancelada'
                        && !$record->partidas()->where(function ($query): void {
                            $query->where('cantidad_devuelta', '>', 0)
                                ->orWhereHas('registroRenta', fn ($registro) => $registro->where('cantidad_devuelta', '>', 0));
                        })->exists())
                    ->requiresConfirmation()
                    ->modalHeading(fn (NotaEnvio $record) => 'Cancelar Envío Folio ' . $record->folio)
                    ->modalDescription('¿Estás seguro de cancelar esta nota de envío? Se revertirán sus movimientos de inventario y se retirarán de rentas los registros asociados a este envío.')
                    ->modalSubmitActionLabel('Sí, cancelar')
                    ->action(function (NotaEnvio $record) {
                        if ($record->estatus === 'Cancelada') {
                            return;
                        }

                        $referencia = $record->serie . $record->folio;

                        $cancelada = \Illuminate\Support\Facades\DB::transaction(function () use ($record, $referencia): bool {
                            $partidaIds = $record->partidas()->lockForUpdate()->pluck('id');
                            $registros = RegistroRenta::query()
                                ->whereIn('nota_envio_partida_id', $partidaIds)
                                ->lockForUpdate()
                                ->get();

                            if ($registros->contains(fn (RegistroRenta $registro): bool => (float) ($registro->cantidad_devuelta ?? 0) > 0)) {
                                return false;
                            }

                            // Revertir movimientos de inventario generados por este envío
                            $movimientos = MovimientoInventario::query()
                                ->where('documento_referencia', $referencia)
                                ->where('tipo', 'salida')
                                ->get();

                            foreach ($movimientos as $movimiento) {
                                InventarioMovimientoService::entrada(
                                    productoId: $movimiento->producto_id,
                                    cantidad: (float) $movimiento->cantidad,
                                    motivo: "Cancelación de envío {$referencia}",
                                    documentoReferencia: $referencia
                                );
                            }

                            // El enum de registro_rentas no admite el estado Cancelado.
                            // Como este envío no tuvo devoluciones, se eliminan sus registros
                            // de renta; las partidas y la nota cancelada conservan la trazabilidad.
                            $registros->each(fn (RegistroRenta $registro) => $registro->delete());
                            $record->partidas()->update(['estado' => 'Cancelada']);

                            $record->update(['estatus' => 'Cancelada']);

                            return true;
                        });

                        if (! $cancelada) {
                            Notification::make()
                                ->title('No se puede cancelar el envío')
                                ->body('El envío ya tiene devoluciones registradas. Revise el detalle de las partidas antes de continuar.')
                                ->warning()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Envío cancelado')
                            ->body("El envío {$referencia} ha sido cancelado y se revirtieron los movimientos de inventario.")
                            ->success()
                            ->send();
                    }),
                Action::make('renovar')
                    ->label('Extender renta')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->visible(function (NotaEnvio $record): bool {
                        $nota = $record->notaVentaRenta;

                        return $nota?->estatus === 'Activa'
                            && $record->estatus !== 'Cancelada'
                            && $record->partidas()->whereHas('registroRenta', fn ($query) => $query
                                ->where('estado', 'Activo')
                                ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)'))
                                ->exists();
                    })
                    ->modalHeading(fn (NotaEnvio $record): string => 'Extender renta - Envío ' . $record->folio)
                    ->modalDescription('Capture los días adicionales y confirme el importe del cobro. Se actualizará el vencimiento; no se agregará depósito ni se moverá inventario.')
                    ->modalSubmitActionLabel('Extender renta')
                    ->form(fn (NotaEnvio $record): array => [
                        TextInput::make('dias')
                            ->label('Días a extender')
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, NotaEnvio $record): void {
                                $set('importe', app(RenovacionRentaService::class)->importeSugerido($record, max(1, (int) $state)));
                            })
                            ->required(),
                        TextInput::make('importe')
                            ->label('Importe del cobro pendiente')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('$')
                            ->default(fn (): float => app(RenovacionRentaService::class)->importeSugerido($record, 1))
                            ->required()
                            ->helperText('Importe sugerido según tarifa y días. Puede corregirlo; incluye IVA cuando corresponda y no agrega depósito.'),
                    ])
                    ->action(function (NotaEnvio $record, array $data): void {
                        try {
                            $cobro = app(RenovacionRentaService::class)->renovar(
                                $record,
                                (int) $data['dias'],
                                (float) $data['importe'],
                                Auth::id(),
                            );

                            Notification::make()
                                ->title('Renta extendida')
                                ->body('Se actualizó el vencimiento y se generó el cobro pendiente ' . $cobro->serie . '-' . $cobro->folio . ' por $' . number_format((float) $cobro->total, 2) . '.')
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\Illuminate\Validation\ValidationException $exception) {
                            Notification::make()
                                ->title('No se pudo extender la renta')
                                ->body(collect($exception->errors())->flatten()->unique()->implode(' '))
                                ->danger()
                                ->persistent()
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);
                            Notification::make()
                                ->title('No se pudo extender la renta')
                                ->body('La operación se revirtió. Revise el registro de errores antes de reintentar.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                ])
            ],RecordActionsPosition::BeforeColumns);
    }
}

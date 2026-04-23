<?php

namespace App\Filament\Resources\NotasEnvio\Tables;

use App\Filament\Resources\NotasDevolucionRenta\NotasDevolucionRentaResource;
use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Models\NotaVentaRentaPartidas;
use App\Models\RegistroRenta;
use App\Services\CierreDevolucionRentaService;
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
            ->columns([
                TextColumn::make('id')
                    ->label('Folio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('documento_origen')
                    ->label('Documento Origen')
                    ->getStateUsing(function (NotaEnvio $record) {
                        if ($record->nota_venta_renta_id) {
                            $nota = $record->notaVentaRenta;
                            return $nota ? 'NR ' . ($nota->serie ?? '') . $nota->folio : '-';
                        }
                        if ($record->nota_venta_venta_id) {
                            $nota = $record->notaVentaVenta;
                            return $nota ? 'NVV ' . ($nota->serie ?? '') . $nota->folio : '-';
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
                TextColumn::make('fecha_vencimiento')
                    ->label('Fecha Vencimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('dias_vencimiento')
                    ->label('Dias Vencimiento')
                    ->state(function (NotaEnvio $record): string {
                        if (!$record->fecha_vencimiento) {
                            return '-';
                        }

                        $dias = Carbon::today()->diffInDays(Carbon::parse($record->fecha_vencimiento), false);

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
                TextColumn::make('estado_renta')
                    ->label('Estado Renta')
                    ->badge()
                    ->colors([
                        'warning' => 'Pendiente',
                        'info' => 'Parcial',
                        'success' => 'Devuelta',
                        'gray' => 'Vigente',
                        'danger' => 'Vencido',
                    ])
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make()
            ], HeaderActionsPosition::Bottom)
            ->recordActions([
                ActionGroup::make([
                Action::make('ver_detalle')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (NotaEnvio $record) => 'Detalle de Items en Renta - Envío Folio ' . $record->folio)
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
                    ->visible(fn (NotaEnvio $record) => in_array($record->estatus, ['Pendiente', 'En Tránsito']))
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
                    ->label('Crear Nota Devolucion')
                    ->icon('heroicon-o-document-plus')
                    ->color('info')
                    ->visible(function (NotaEnvio $record) {
                        return (bool) $record->nota_venta_renta_id
                            && $record->partidas()->whereRaw('cantidad_devuelta < cantidad')->exists();
                    })
                    ->url(fn (NotaEnvio $record) => NotasDevolucionRentaResource::getUrl('create', ['nota_venta_renta_id' => $record->nota_venta_renta_id]))
                    ->openUrlInNewTab(),
                Action::make('devolver')
                    ->label('Devolución Parcial')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(function (NotaEnvio $record) {
                        $nota = $record->notaVentaRenta;
                        if (!$nota) return false;
                        return $record->estado_renta !== 'Devuelta' && $record->partidas()->where('estado', '!=', 'Devuelto')->exists();
                    })
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
                                            ->label($item->producto ? $item->producto->descripcion : ($item->observaciones ?? 'Item'))
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
                            $mensaje .= ' Quedan ' . $pendientes . ' item(s) con material pendiente por devolver.';
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
                        $resultado = app(CierreDevolucionRentaService::class)
                            ->cerrar($nota, $data['observaciones'] ?? null, Auth::id());

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

                        // Guardar observaciones en sesión para el ticket
                        session(['cierre_devolucion_observaciones_' . $record->id => $data['observaciones'] ?? null]);

                        Notification::make()
                            ->title('Cierre de devolución procesado')
                            ->body($mensaje)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('renovar')
                    ->label('Renovar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(function (NotaEnvio $record) {
                        $nota = $record->notaVentaRenta;
                        if (!$nota) return false;
                        return $nota->estatus !== 'Devuelta';
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (NotaEnvio $record) => 'Renovar Renta - Envío Folio ' . $record->folio)
                    ->modalDescription(function (NotaEnvio $record) {
                        $nota = $record->notaVentaRenta;
                        $deposito = $nota ? (float)$nota->deposito : 0;
                        return 'Se marcará la nota actual como Devuelta y se generará una nueva nota con los mismos datos. Depósito: $' . number_format($deposito, 2);
                    })
                    ->modalSubmitActionLabel('Renovar Renta')
                    ->action(function (NotaEnvio $record) {
                        $nota = $record->notaVentaRenta;
                        if (!$nota) return;
                        $deposito = (float)$nota->deposito;

                        // 1. Marcar registros de renta originales como Devueltos
                        RegistroRenta::where('nota_venta_renta_id', $nota->id)
                            ->update(['estado' => 'Devuelto']);

                        // 2. Marcar nota original como Devuelta
                        $nota->update(['estatus' => 'Devuelta']);

                        // 3. Crear nueva nota con los mismos datos
                        $nuevoFolio = (NotasVentaRenta::where('serie', $nota->serie)->selectRaw('MAX(CAST(folio AS UNSIGNED)) as max_folio')->value('max_folio') ?? 0) + 1;

                        $nuevaNota = NotasVentaRenta::create([
                            'cliente_id' => $nota->cliente_id,
                            'direccion_entrega_id' => $nota->direccion_entrega_id,
                            'serie' => $nota->serie,
                            'folio' => $nuevoFolio,
                            'fecha_emision' => now(),
                            'dias_renta' => $nota->dias_renta,
                            'fecha_vencimiento' => now()->addDays($nota->dias_renta ?? 30),
                            'moneda' => $nota->moneda ?? 'MXN',
                            'tipo_cambio' => $nota->tipo_cambio ?? 1,
                            'deposito' => $nota->deposito,
                            'subtotal' => $nota->subtotal,
                            'impuestos_total' => $nota->impuestos_total,
                            'total' => $nota->total,
                            'saldo_pendiente' => $nota->saldo_pendiente,
                            'estatus' => 'Activa',
                            'uso_cfdi' => $nota->uso_cfdi,
                            'forma_pago' => $nota->forma_pago,
                            'metodo_pago' => $nota->metodo_pago,
                            'regimen_fiscal_receptor' => $nota->regimen_fiscal_receptor,
                            'rfc_emisor' => $nota->rfc_emisor,
                            'rfc_receptor' => $nota->rfc_receptor,
                            'razon_social_receptor' => $nota->razon_social_receptor,
                            'documento_origen_id' => $nota->id,
                        ]);

                        // 4. Copiar partidas de la nota original
                        foreach ($nota->partidas as $partida) {
                            NotaVentaRentaPartidas::create([
                                'nota_venta_renta_id' => $nuevaNota->id,
                                'cantidad' => $partida->cantidad,
                                'item' => $partida->item,
                                'descripcion' => $partida->descripcion,
                                'valor_unitario' => $partida->valor_unitario,
                                'subtotal' => $partida->subtotal,
                                'impuestos' => $partida->impuestos,
                                'total' => $partida->total,
                                'producto_id' => $partida->producto_id,
                            ]);
                        }

                        // 5. Copiar registros de renta a la nueva nota
                        $registrosOriginales = RegistroRenta::where('nota_venta_renta_id', $nota->id)->get();
                        $cliente = $nota->cliente;

                        foreach ($registrosOriginales as $reg) {
                            RegistroRenta::create([
                                'nota_venta_renta_id' => $nuevaNota->id,
                                'cliente_id' => $reg->cliente_id,
                                'cliente_nombre' => $reg->cliente_nombre,
                                'cliente_contacto' => $reg->cliente_contacto,
                                'cliente_telefono' => $reg->cliente_telefono,
                                'cliente_direccion' => $reg->cliente_direccion,
                                'producto_id' => $reg->producto_id,
                                'cantidad' => $reg->cantidad,
                                'dias_renta' => $reg->dias_renta,
                                'fecha_renta' => now(),
                                'fecha_vencimiento' => now()->addDays($reg->dias_renta ?? 30),
                                'importe_renta' => $reg->importe_renta,
                                'importe_deposito' => $reg->importe_deposito,
                                'estado' => 'Activo',
                                'observaciones' => $reg->observaciones,
                            ]);
                        }

                        // 6. Crear nueva nota de envío vinculada a la nueva nota
                        $nuevoFolioEnvio = (NotaEnvio::max('folio') ?? 0) + 1;
                        $nuevaNotaEnvio = NotaEnvio::create([
                            'serie' => $record->serie ?? 'NE',
                            'folio' => $nuevoFolioEnvio,
                            'nota_venta_renta_id' => $nuevaNota->id,
                            'cliente_id' => $record->cliente_id,
                            'direccion_entrega_id' => $record->direccion_entrega_id,
                            'fecha_emision' => now(),
                            'dias_renta' => $record->dias_renta,
                            'fecha_vencimiento' => now()->addDays($record->dias_renta ?? 30),
                            'observaciones' => $record->observaciones,
                            'estatus' => 'Pendiente',
                            'user_id' => Auth::id(),
                        ]);

                        // Copiar partidas de envío
                        foreach ($record->partidas as $partida) {
                            NotaEnvioPartida::create([
                                'nota_envio_id' => $nuevaNotaEnvio->id,
                                'producto_id' => $partida->producto_id,
                                'descripcion' => $partida->descripcion,
                                'cantidad' => $partida->cantidad,
                                'observaciones' => $partida->observaciones,
                            ]);
                        }

                        // 7. Movimientos en caja (efecto 0)
                        $cajaAbierta = Caja::where('estatus', 'Abierta')->first();
                        $sinCaja = false;

                        if ($cajaAbierta && $deposito > 0) {
                            // Egreso: devolución depósito original
                            CajaMovimiento::create([
                                'caja_id' => $cajaAbierta->id,
                                'tipo' => 'Egreso',
                                'fuente' => 'Devolución depósito renta',
                                'metodo_pago' => 'Efectivo',
                                'importe' => $deposito,
                                'referencia' => 'Renovación Envío ' . $record->folio . ' - Devolución depósito NVR ' . $nota->serie . '-' . $nota->folio,
                                'user_id' => Auth::id(),
                                'fecha' => now(),
                                'movimentable_type' => NotasVentaRenta::class,
                                'movimentable_id' => $nota->id,
                            ]);

                            // Ingreso: depósito nueva nota
                            CajaMovimiento::create([
                                'caja_id' => $cajaAbierta->id,
                                'tipo' => 'Ingreso',
                                'fuente' => 'Depósito renta renovación',
                                'metodo_pago' => 'Efectivo',
                                'importe' => $deposito,
                                'referencia' => 'Renovación Envío ' . $nuevaNotaEnvio->folio . ' - Depósito NVR ' . $nuevaNota->serie . '-' . $nuevaNota->folio,
                                'user_id' => Auth::id(),
                                'fecha' => now(),
                                'movimentable_type' => NotasVentaRenta::class,
                                'movimentable_id' => $nuevaNota->id,
                            ]);

                            // Actualizar totales de caja
                            $eg = $cajaAbierta->movimientos()->where('tipo', 'Egreso')->where('metodo_pago', 'Efectivo')->sum('importe');
                            $cajaAbierta->update(['total_egresos_cash' => $eg]);
                        } elseif ($deposito > 0) {
                            $sinCaja = true;
                        }

                        $mensaje = 'Renta renovada. Nueva nota: ' . $nuevaNota->serie . '-' . $nuevaNota->folio . '. Nueva nota envío: ' . $nuevaNotaEnvio->folio;
                        if ($sinCaja) {
                            $mensaje .= ' (⚠ No se encontró caja abierta para registrar movimientos)';
                        }

                        Notification::make()
                            ->title('Renta renovada exitosamente')
                            ->body($mensaje)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                ])
            ],RecordActionsPosition::BeforeColumns);
    }
}

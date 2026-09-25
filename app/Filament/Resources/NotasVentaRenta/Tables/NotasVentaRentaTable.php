<?php

namespace App\Filament\Resources\NotasVentaRenta\Tables;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Services\CierreDevolucionRentaService;
use App\Enums\TipoNotaRenta;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

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
                    ->getStateUsing(fn (NotasVentaRenta $record): float => $record->estatus === 'Cancelada'
                        ? 0.0
                        : (float) $record->saldo_pendiente)
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
                        if ($record->estatus === 'Cancelada') {
                            return 'Cancelada';
                        }

                        $partidas = $record->esMaderaM2()
                            ? $record->desgloseM2->map(fn ($fila) => (object) [
                                'item' => $fila->producto_id,
                                'cantidad' => $fila->cantidad,
                            ])
                            : $record->partidas;
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
                        $enviadas = $envios->where('estatus', 'Enviada')->count();

                        if ($envioCompleto && $entregadas >= $totalEnvios) return 'Entregada';
                        if ($entregadas > 0 && $entregadas < $totalEnvios) return 'Entregada Parcial';
                        if ($envioCompleto && $enviadas >= $totalEnvios) return 'Enviada';
                        if ($enviadas > 0) return 'Envío Parcial';
                        return 'Envío Parcial';
                    })
                    ->getStateUsing(fn (NotasVentaRenta $record): string => self::estatusEnvioPorPartidas($record))
                    ->colors([
                        'danger' => 'Cancelada',
                        'warning' => 'Envío Parcial',
                        'info' => 'Enviada',
                        'gray' => 'Pendiente.',
                        'primary' => 'Entregada Parcial',
                        'success' => 'Entregada',
                        'secondary' => 'Sin partidas',
                    ]),
                TextColumn::make('estatus_renta')
                    ->label('Estatus Renta')
                    ->badge()
                    ->getStateUsing(function (NotasVentaRenta $record) {
                        if ($record->estatus === 'Cancelada') {
                            return 'Cancelada';
                        }

                        $envios = NotaEnvio::where('nota_venta_renta_id', $record->id)->get();
                        if ($envios->isEmpty()) return 'Vigente';

                        $todasDevueltas = $envios->every(fn ($e) => $e->estado_renta === 'Devuelta');
                        return $todasDevueltas ? 'Devuelta' : 'Vigente';
                    })
                    ->colors([
                        'success' => 'Vigente',
                        'gray' => 'Devuelta',
                        'danger' => 'Cancelada',
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
                            $partidas = $record->esMaderaM2()
                                ? $record->desgloseM2->map(fn ($fila) => (object) [
                                    'item' => $fila->producto_id,
                                    'cantidad' => $fila->cantidad,
                                    'descripcion' => $fila->descripcion ?: $fila->producto?->descripcion,
                                ])
                                : $record->partidas;
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
                        ->label('Vista previa de cierre')
                        ->icon('heroicon-o-check-circle')
                        ->color('danger')
                        ->visible(function (NotasVentaRenta $record) {
                            return !in_array($record->estatus, ['Devuelta', 'Vendida'], true)
                                && $record->notasEnvio()->exists();
                        })
                        ->modalHeading(fn (NotasVentaRenta $record) => 'Vista previa del cierre - Nota ' . $record->serie . '-' . $record->folio)
                        ->modalWidth('7xl')
                        ->modalSubmitActionLabel('Confirmar y procesar cierre')
                        ->form(function (NotasVentaRenta $record): array {
                            $resumenData = $record->direccion_entrega_id
                                ? app(CierreDevolucionRentaService::class)->obtenerResumenPorObra($record)
                                : app(CierreDevolucionRentaService::class)->obtenerResumen($record);
                            $totales = $resumenData['totales'];
                            $money = static fn (float $value): string => '$' . number_format($value, 2);
                            $detalle = '';

                            foreach ($resumenData['rows'] as $row) {
                                $detalle .= '<div style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">'
                                    . '<strong>' . e($row['producto']) . '</strong>'
                                    . '<div>Faltante: ' . number_format((float) $row['faltante'], 2)
                                    . ' | Precio unitario: ' . $money((float) ($row['precio_unitario'] ?? 0))
                                    . ' | Importe: ' . $money((float) $row['total']) . '</div>'
                                    . '</div>';
                            }

                            if ($detalle === '') {
                                $detalle = '<div style="padding: 8px 0;">No hay faltantes por cobrar en esta renta.</div>';
                            }

                            $resumen = new HtmlString(
                                '<div style="line-height: 1.6; white-space: normal;">'
                                . '<div style="font-weight: 700; margin-bottom: 8px;">Detalle de faltantes</div>'
                                . $detalle
                                . '<div style="font-weight: 700; margin: 14px 0 8px;">Resumen de la renta</div>'
                                . '<div style="display: grid; gap: 4px;">'
                                . '<div><strong>Total de renta:</strong> ' . $money((float) ($totales['total_renta'] ?? 0)) . '</div>'
                                . '<div><strong>Depósito recibido:</strong> ' . $money((float) ($totales['deposito'] ?? 0)) . '</div>'
                                . '<div><strong>Total faltantes:</strong> ' . $money((float) ($totales['total_faltantes'] ?? 0)) . '</div>'
                                . '<div><strong>Depósito aplicado:</strong> ' . $money((float) ($totales['deposito_aplicado'] ?? 0)) . '</div>'
                                . '<div><strong>Saldo por cobrar:</strong> ' . $money((float) ($totales['saldo_por_cobrar'] ?? 0)) . '</div>'
                                . '<div><strong>Depósito a devolver:</strong> ' . $money((float) ($totales['deposito_devolver'] ?? 0)) . '</div>'
                                . '</div></div>'
                            );

                            return [
                                Placeholder::make('resumen')
                                    ->label('Resumen de Devolución')
                                    ->content($resumen),
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(3),
                                Select::make('modo_cierre')
                                    ->label('Resolución de la renta')
                                    ->options([
                                        'devolucion' => 'Recibir devolución y aplicar depósito/faltantes',
                                        'venta_madera' => 'Cliente se queda con la madera y se genera venta',
                                    ])
                                    ->default('devolucion')
                                    ->required()
                                    ->visible(fn () => $record->tieneMadera()),
                            ];
                        })
                        ->action(function (NotasVentaRenta $record, array $data): void {
                            $servicioCierre = app(CierreDevolucionRentaService::class);
                            $resultado = $record->direccion_entrega_id
                                ? $servicioCierre->cerrarPorObra(
                                    $record->cliente_id,
                                    $record->direccion_entrega_id,
                                    $data['observaciones'] ?? null,
                                    Auth::id(),
                                    $data['modo_cierre'] ?? 'devolucion',
                                )
                                : $servicioCierre->cerrar(
                                    $record,
                                    $data['observaciones'] ?? null,
                                    Auth::id(),
                                    $data['modo_cierre'] ?? 'devolucion',
                                    false,
                                );

                            $totales = $resultado['resumen']['totales'];
                            session(['cierre_devolucion_resumen_nvr_' . $record->id => $resultado['resumen']]);
                            if (!empty($resultado['nota_id']) && (int) $resultado['nota_id'] !== (int) $record->id) {
                                session(['cierre_devolucion_resumen_nvr_' . $resultado['nota_id'] => $resultado['resumen']]);
                            }

                            if (!empty($resultado['already_closed'])) {
                                Notification::make()
                                    ->title('Renta ya cerrada')
                                    ->body('La nota de renta ya había sido cerrada previamente.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $mensaje = 'Cierre de devolución consolidado procesado. ';
                            if (($resultado['modo'] ?? null) === 'venta_madera') {
                                $mensaje = 'La madera se convirtió en venta y la NR quedó marcada como Vendida. ';
                            }
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
                            DB::transaction(function () use ($record): void {
                                $record->update([
                                    'estatus' => 'Cancelada',
                                    'saldo_pendiente' => 0,
                                ]);
                                $record->notasEnvio()->update(['estatus' => 'Cancelada']);
                            });

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

    private static function estatusEnvioPorPartidas(NotasVentaRenta $record): string
    {
        if ($record->estatus === 'Cancelada') {
            return 'Cancelada';
        }

        $partidas = $record->partidas;
        if ($partidas->isEmpty()) {
            return 'Sin partidas';
        }

        $envios = NotaEnvio::query()
            ->where('nota_venta_renta_id', $record->id)
            ->get();
        if ($envios->isEmpty()) {
            return 'Pendiente.';
        }

        $envioPartidas = NotaEnvioPartida::query()
            ->with('producto')
            ->whereHas('notaEnvio', fn ($query) => $query->where('nota_venta_renta_id', $record->id))
            ->get();
        $tieneEnviado = false;
        $tienePendiente = false;

        foreach ($partidas as $partida) {
            $tipo = TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $record->tipo_nota_renta ?? 'equipo')
                ?? TipoNotaRenta::Equipo;

            if ($tipo->esMaderaM2()) {
                $objetivoM2 = (float) ($partida->metros_m2 ?? 0);
                $cubiertoM2 = $envioPartidas
                    ->filter(fn ($envioPartida): bool =>
                        (int) $envioPartida->nota_venta_renta_partida_id === (int) $partida->id
                        || (float) ($envioPartida->producto?->m2_cubre ?? 0) > 0
                    )
                    ->sum(fn ($envioPartida): float =>
                        (float) $envioPartida->cantidad * (float) ($envioPartida->producto?->m2_cubre ?? 0)
                    );

                $tieneEnviado = $tieneEnviado || $cubiertoM2 > 0;
                if ($objetivoM2 > 0 && $cubiertoM2 < ($objetivoM2 - 1.0)) {
                    $tienePendiente = true;
                }

                continue;
            }

            $enviado = $envioPartidas
                ->filter(fn ($envioPartida): bool =>
                    (int) $envioPartida->nota_venta_renta_partida_id === (int) $partida->id
                    || ((int) $partida->item === (int) $envioPartida->producto_id && !$envioPartida->nota_venta_renta_partida_id)
                )
                ->sum(fn ($envioPartida): float => (float) $envioPartida->cantidad);

            $tieneEnviado = $tieneEnviado || $enviado > 0;
            if ($enviado < (float) $partida->cantidad) {
                $tienePendiente = true;
            }
        }

        if (!$tieneEnviado) {
            return 'Pendiente.';
        }

        $totalEnvios = $envios->count();
        $entregadas = $envios->where('estatus', 'Entregada')->count();
        $todosEnviados = $envios->every(fn ($envio): bool => in_array($envio->estatus, ['Enviada', 'Entregada'], true));

        if ($tienePendiente) {
            return $entregadas > 0 ? 'Entregada Parcial' : 'Envío Parcial';
        }

        if ($entregadas >= $totalEnvios) {
            return 'Entregada';
        }

        return $todosEnviados ? 'Enviada' : 'Envío Parcial';
    }
}

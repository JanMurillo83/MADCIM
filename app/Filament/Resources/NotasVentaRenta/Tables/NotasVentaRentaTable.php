<?php

namespace App\Filament\Resources\NotasVentaRenta\Tables;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Models\RegistroRenta;
use App\Services\AmpliacionVigenciaRentaService;
use App\Enums\TipoNotaRenta;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\NotasVentaRenta\NotasVentaRentaResource;

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
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable()
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()?->isAdmin() ?? false),
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

                        if ($record->estatus === 'Devuelta') {
                            return 'Devuelta';
                        }

                        if ($record->estatus === 'Vendida') {
                            return 'Vendida';
                        }

                        $tieneRentaVencida = RegistroRenta::query()
                            ->where('nota_venta_renta_id', $record->id)
                            ->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')
                            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
                            ->exists();

                        if ($tieneRentaVencida) {
                            return 'Vencida';
                        }

                        $envios = NotaEnvio::where('nota_venta_renta_id', $record->id)->get();
                        if ($envios->isEmpty()) return 'Vigente';

                        $todasDevueltas = $envios->every(fn ($e) => $e->estado_renta === 'Devuelta');
                        return $todasDevueltas ? 'Devuelta' : 'Vigente';
                    })
                    ->colors([
                        'success' => 'Vigente',
                        'gray' => 'Devuelta',
                        'warning' => 'Vendida',
                        'danger' => ['Vencida', 'Cancelada'],
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
                    Action::make('ampliar_vigencia')
                        ->label('Ampliar Vigencia')
                        ->icon('heroicon-o-calendar-days')
                        ->color('warning')
                        ->visible(fn (NotasVentaRenta $record): bool => $record->estatus === 'Activa')
                        ->modalHeading(fn (NotasVentaRenta $record): string => 'Ampliar vigencia - Nota ' . $record->serie . '-' . $record->folio)
                        ->modalDescription('La renovación generará una Nota de Venta a crédito. Para madera puede aplicar tarifa 0%, 50% o regular; no se agrega depósito.')
                        ->modalWidth('5xl')
                        ->form(function (NotasVentaRenta $record): array {
                            $record->loadMissing(['partidas.producto', 'desgloseM2.producto']);
                            $components = [];
                            $esM2 = $record->partidas->isEmpty()
                                ? (TipoNotaRenta::tryFrom($record->tipo_nota_renta ?? '')?->esMaderaM2() ?? false)
                                : $record->partidas->contains(fn ($partida) => TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $record->tipo_nota_renta ?? '')?->esMaderaM2() ?? false);
                            $partidasNota = $record->partidas;

                            foreach ($partidasNota as $index => $partida) {
                                $tipo = TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $record->tipo_nota_renta ?? 'equipo')
                                    ?? TipoNotaRenta::Equipo;
                                $components[] = Placeholder::make("partida_{$index}_descripcion")
                                    ->label($tipo->label())
                                    ->content($partida->descripcion ?: $partida->producto?->descripcion ?: 'Partida ' . ($index + 1))
                                    ->columnSpan($tipo->esEquipo() ? 2 : 1);

                                if ($tipo->esEquipo()) {
                                    $registrosActivos = RegistroRenta::query()
                                        ->where('nota_venta_renta_id', $record->id)
                                        ->whereHas('notaEnvioPartida', fn ($query) => $query->where('nota_venta_renta_partida_id', $partida->id))
                                        ->where('estado', 'Activo')
                                        ->whereNotNull('fecha_vencimiento')
                                        ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                                        ->get();
                                    if ($registrosActivos->isEmpty()) {
                                        continue;
                                    }
                                }

                                if ($tipo->esMaderaM2()) {
                                    continue;
                                } elseif ($tipo->esMaderaPieza()) {
                                    if (!$esM2) {
                                        $enRenta = RegistroRenta::query()
                                            ->where('nota_venta_renta_id', $record->id)
                                            ->where('producto_id', $partida->item)
                                            ->where('estado', 'Activo')
                                            ->whereNotNull('fecha_vencimiento')
                                            ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                                            ->get()
                                            ->sum(fn (RegistroRenta $registro): float => (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0));
                                        $components[] = Placeholder::make("madera_pieza_{$partida->id}_disponible")
                                            ->label('Piezas activas')
                                            ->content(number_format((float) $enRenta, 2));
                                        $components[] = TextInput::make("partidas.{$partida->id}.cantidad")
                                            ->label('Piezas a renovar')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue($enRenta)
                                            ->default((float) $enRenta)
                                            ->required();
                                    }
                                    $components[] = Select::make("partidas.{$partida->id}.tarifa")
                                        ->label('Tarifa')
                                        ->options(['0' => 'Precio 0%', '50' => 'Precio 50%', '100' => 'Precio regular'])
                                        ->default('100')
                                        ->required();
                                } else {
                                    $registrosActivos = RegistroRenta::query()
                                        ->where('nota_venta_renta_id', $record->id)
                                        ->whereHas('notaEnvioPartida', fn ($query) => $query->where('nota_venta_renta_partida_id', $partida->id))
                                        ->where('estado', 'Activo')
                                        ->whereNotNull('fecha_vencimiento')
                                        ->whereRaw('cantidad > COALESCE(cantidad_devuelta, 0)')
                                        ->get();
                                    $diasDisponibles = max(1, (int) ($partida->dias_renta ?? $record->dias_renta ?? 1));
                                    $components[] = Placeholder::make("partida_{$index}_vencimiento")
                                        ->label('Vencimiento actual')
                                        ->content((string) ($registrosActivos->max('fecha_vencimiento')?->format('d/m/Y') ?? $partida->fecha_vencimiento?->format('d/m/Y') ?? '-'));
                                    $components[] = TextInput::make("partidas.{$partida->id}.dias_ampliacion")
                                        ->label('Días a ampliar')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->minValue(1)
                                        ->default($diasDisponibles)
                                        ->required();
                                    $components[] = Placeholder::make("partida_{$index}_dias")
                                        ->label('Importe estimado por periodo')
                                        ->content((string) max(1, (int) ($partida->dias_renta ?? $record->dias_renta ?? 1)) . ' días');
                                    $components[] = Placeholder::make("partida_{$index}_importe")
                                        ->label('Importe estimado por días')
                                        ->content(function (\Filament\Schemas\Components\Utilities\Get $get) use ($partida, $registrosActivos): string {
                                            $precio = match ($partida->tipo_renta ?? 'dia') {
                                                'semana' => (float) ($partida->producto?->precio_renta_semana ?? 0),
                                                'mes' => (float) ($partida->producto?->precio_renta_mes ?? 0),
                                                default => (float) ($partida->producto?->precio_renta_dia ?? 0),
                                            };
                                            $diasIniciales = max(1, (int) ($partida->dias_renta ?? 1));
                                            $diasAmpliacion = max(1, (int) ($get("partidas.{$partida->id}.dias_ampliacion") ?? $diasIniciales));
                                            $cantidad = $registrosActivos->sum(fn (RegistroRenta $registro): float => (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0));
                                            $baseDiaria = $precio * max(1, (int) ($partida->duracion_renta ?? 1)) / $diasIniciales;
                                            return '$' . number_format($baseDiaria * $diasAmpliacion * $cantidad, 2);
                                        });
                                }
                            }

                            if ($esM2) {
                                $filasM2 = $record->desgloseM2;
                                if ($filasM2->isEmpty() && $record->partidas->isNotEmpty()) {
                                    $filasM2 = $record->partidas
                                        ->filter(fn ($partida) => TipoNotaRenta::tryFrom($partida->tipo_nota_renta ?? $record->tipo_nota_renta ?? '')?->esMaderaM2())
                                        ->map(function ($partida) {
                                            return (object) [
                                                'id' => 'partida_' . $partida->id,
                                                'partida_id' => $partida->id,
                                                'descripcion' => $partida->descripcion,
                                                'm2_total' => $partida->metros_m2,
                                                'producto' => $partida->producto,
                                            ];
                                        });
                                }
                                foreach ($filasM2 as $fila) {
                                    $components[] = Placeholder::make("m2_{$fila->id}_descripcion")
                                        ->label($fila->descripcion ?: $fila->producto?->descripcion ?: 'Madera por M2')
                                        ->content(number_format((float) $fila->m2_total, 2) . ' M² actuales')
                                        ->columnSpan(2);
                                    $components[] = TextInput::make("m2.{$fila->id}.metros_m2")
                                        ->label('M² a renovar')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default((float) $fila->m2_total)
                                        ->required();
                                    $components[] = Select::make("m2.{$fila->id}.tarifa")
                                        ->label('Tarifa')
                                        ->options(['0' => 'Precio 0%', '50' => 'Precio 50%', '100' => 'Precio regular'])
                                        ->default('100')
                                        ->required();
                                }
                            }

                            return $components ?: [
                                Placeholder::make('sin_partidas_ampliables')
                                    ->label('Sin partidas ampliables')
                                    ->content('No hay rentas activas enviadas para ampliar.'),
                            ];
                        })
                        ->action(function (NotasVentaRenta $record, array $data): void {
                            try {
                                $partidas = [];
                                foreach ($data['partidas'] ?? [] as $id => $values) {
                                    $tipo = TipoNotaRenta::tryFrom($record->partidas->firstWhere('id', (int) $id)?->tipo_nota_renta ?? $record->tipo_nota_renta ?? 'equipo');
                                    if (!$tipo?->esEquipo() && !$tipo?->esMaderaPieza()) {
                                        continue;
                                    }
                                    $partidas[] = ['partida_id' => (int) $id, ...$values];
                                }
                                foreach ($data['m2'] ?? [] as $id => $values) {
                                    if (str_starts_with((string) $id, 'partida_')) {
                                        $partidas[] = ['partida_id' => (int) substr((string) $id, 8), ...$values];
                                        continue;
                                    }
                                    $fila = $record->desgloseM2()->whereKey($id)->first();
                                    if ($fila) {
                                        $partidas[] = ['fila_m2_id' => (int) $id, ...$values];
                                    }
                                }


                                $notaVenta = app(AmpliacionVigenciaRentaService::class)
                                    ->ampliar($record, $partidas, Auth::id());

                                Notification::make()
                                    ->title('Vigencia ampliada')
                                    ->body('Se generó la Nota de Venta ' . $notaVenta->serie . '-' . $notaVenta->folio . ' por $' . number_format((float) $notaVenta->total, 2) . ' a crédito.')
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } catch (\Illuminate\Validation\ValidationException $exception) {
                                Notification::make()
                                    ->title('No se pudo ampliar la vigencia')
                                    ->body(collect($exception->errors())->flatten()->implode(' '))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            } catch (\Throwable $exception) {
                                report($exception);
                                Notification::make()
                                    ->title('No se pudo ampliar la vigencia')
                                    ->body('Ocurrió un error al generar la Nota de Venta de renovación.')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
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
                Action::make('nuevo')
                    ->label('Nuevo')
                    ->icon('fas-circle-plus')
                    ->url(fn (): string => NotasVentaRentaResource::getUrl('create')),
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

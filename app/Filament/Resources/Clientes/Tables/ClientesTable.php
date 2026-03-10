<?php

namespace App\Filament\Resources\Clientes\Tables;

use App\Models\SatRegimenFiscal;
use App\Services\ClientesImportService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use App\Models\ClienteDireccionEntrega;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Models\RegistroRenta;
use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid as FormGrid;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Components\Checkbox as FormCheckbox;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave')
                    ->searchable(),
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('rfc')
                    ->searchable(),
                TextColumn::make('regimen')
                    ->label('Régimen fiscal')
                    ->formatStateUsing(function (?string $state): ?string {
                        if ($state === null || $state === '') {
                            return $state;
                        }

                        static $map = null;
                        $map ??= SatRegimenFiscal::query()->pluck('descripcion', 'clave')->all();

                        $descripcion = $map[$state] ?? null;

                        return $descripcion ? "{$state} - {$descripcion}" : $state;
                    })
                    ->searchable(),
                TextColumn::make('codigo')
                    ->searchable(),
                TextColumn::make('calle')
                    ->searchable(),
                TextColumn::make('exterior')
                    ->searchable(),
                TextColumn::make('interior')
                    ->searchable(),
                TextColumn::make('colonia')
                    ->searchable(),
                TextColumn::make('municipio')
                    ->searchable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('pais')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->searchable(),
                TextColumn::make('correo')
                    ->searchable(),
                TextColumn::make('descuento')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lista')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('contacto')
                    ->searchable(),
                TextColumn::make('dias_credito')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('saldo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    \Filament\Actions\Action::make('direccionesEntrega')
                        ->label('Direcciones de Entrega')
                        ->icon('fas-truck-loading')
                        ->modalHeading(fn ($record) => "Direcciones de Entrega - {$record->nombre}")
                        ->modalWidth('7xl')
                        ->fillForm(fn ($record) => [
                            'direcciones' => $record->direccionesEntrega->toArray(),
                        ])
                        ->form([
                            Repeater::make('direcciones')
                                ->relationship('direccionesEntrega')
                                ->collapsed()
                                ->label(false)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            FormTextInput::make('nombre_direccion')
                                                ->label('Nombre de la Dirección')
                                                ->placeholder('Ej: Oficina Principal, Almacén 2')
                                                ->required()
                                                ->maxLength(255),
                                            FormCheckbox::make('activa')
                                                ->label('Activa')
                                                ->default(true),
                                            FormCheckbox::make('es_principal')
                                                ->label('Dirección Principal'),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            FormTextInput::make('calle')
                                                ->label('Calle')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('numero_exterior')
                                                ->label('Número Exterior')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('numero_interior')
                                                ->label('Número Interior')
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            FormTextInput::make('colonia')
                                                ->label('Colonia')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('municipio')
                                                ->label('Municipio')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('estado')
                                                ->label('Estado')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            FormTextInput::make('codigo_postal')
                                                ->label('Código Postal')
                                                ->required()
                                                ->maxLength(255),
                                            FormTextInput::make('pais')
                                                ->label('País')
                                                ->default('México')
                                                ->required()
                                                ->maxLength(255),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            FormTextInput::make('contacto_nombre')
                                                ->label('Nombre del Contacto')
                                                ->maxLength(255),
                                            FormTextInput::make('contacto_telefono')
                                                ->label('Teléfono del Contacto')
                                                ->tel()
                                                ->maxLength(255),
                                        ]),
                                    Textarea::make('referencias')
                                        ->label('Referencias')
                                        ->rows(2)
                                        ->maxLength(65535),
                                ])
                                ->addActionLabel('Añadir Dirección')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['nombre_direccion'] ?? null),
                        ])
                        ->action(function ($record, array $data) {
                            // La relación se maneja automáticamente por el Repeater con relationship('direccionesEntrega')
                            Notification::make()
                                ->title('Direcciones actualizadas')
                                ->success()
                                ->send();
                        })
                        ->modalSubmitActionLabel('Guardar cambios'),
                    Action::make('reporteCliente')
                        ->label('Reporte Cliente')
                        ->icon('heroicon-o-clipboard-document')
                        ->color('info')
                        ->modalHeading(fn ($record) => "Reporte del Cliente - {$record->nombre}")
                        ->modalWidth('7xl')
                        ->modalContent(function ($record) {
                            $cliente = $record->load('direccionesEntrega');

                            $notasVenta = NotasVentaVenta::query()
                                ->where('cliente_id', $record->id)
                                ->orderByDesc('fecha_emision')
                                ->get();

                            $pagosVenta = collect();
                            if ($notasVenta->isNotEmpty()) {
                                $pagosVenta = Pagos::query()
                                    ->selectRaw('documento_id, SUM(importe) as total_pagado, MAX(fecha_pago) as ultimo_pago')
                                    ->where('documento_tipo', 'notas_venta_venta')
                                    ->whereIn('documento_id', $notasVenta->pluck('id'))
                                    ->groupBy('documento_id')
                                    ->get()
                                    ->keyBy('documento_id');
                            }

                            $notasVentaData = $notasVenta->map(function ($nota) use ($pagosVenta) {
                                $pagos = $pagosVenta->get($nota->id);
                                $totalPagado = (float)($pagos->total_pagado ?? 0);
                                $ultimoPago = $pagos?->ultimo_pago ? Carbon::parse($pagos->ultimo_pago) : null;
                                $saldo = $nota->saldo_pendiente;
                                if ($saldo === null) {
                                    $saldo = (float)$nota->total - $totalPagado;
                                }
                                if ($saldo < 0) {
                                    $saldo = 0;
                                }

                                return [
                                    'nota' => $nota,
                                    'total_pagado' => $totalPagado,
                                    'saldo_pendiente' => (float)$saldo,
                                    'ultimo_pago' => $ultimoPago,
                                ];
                            });

                            $notasRenta = NotasVentaRenta::query()
                                ->with(['notasEnvio', 'registrosRenta', 'registrosRenta.producto'])
                                ->where('cliente_id', $record->id)
                                ->orderByDesc('fecha_emision')
                                ->get();

                            $pagosRenta = collect();
                            if ($notasRenta->isNotEmpty()) {
                                $pagosRenta = Pagos::query()
                                    ->selectRaw('documento_id, SUM(importe) as total_pagado, MAX(fecha_pago) as ultimo_pago')
                                    ->where('documento_tipo', 'notas_venta_renta')
                                    ->whereIn('documento_id', $notasRenta->pluck('id'))
                                    ->groupBy('documento_id')
                                    ->get()
                                    ->keyBy('documento_id');
                            }

                            $estadoRenta = function (NotasVentaRenta $nota): string {
                                $registros = $nota->registrosRenta;
                                if ($registros->isEmpty()) {
                                    return 'Sin registros';
                                }
                                $todosDevueltos = $registros->every(fn ($r) => $r->estado === 'Devuelto');
                                if ($todosDevueltos) {
                                    return 'Devuelto';
                                }
                                $fechaVencimiento = $nota->fecha_vencimiento
                                    ?? ($nota->fecha_emision ? Carbon::parse($nota->fecha_emision)->addDays($nota->dias_renta ?? 30) : null);
                                if ($fechaVencimiento && Carbon::parse($fechaVencimiento)->lt(Carbon::today())) {
                                    return 'Vencido';
                                }
                                return 'Vigente';
                            };

                            $notasRentaData = $notasRenta->map(function ($nota) use ($pagosRenta, $estadoRenta) {
                                $pagos = $pagosRenta->get($nota->id);
                                $totalPagado = (float)($pagos->total_pagado ?? 0);
                                $ultimoPago = $pagos?->ultimo_pago ? Carbon::parse($pagos->ultimo_pago) : null;
                                $saldo = $nota->saldo_pendiente;
                                if ($saldo === null) {
                                    $saldo = (float)$nota->total - $totalPagado;
                                }
                                if ($saldo < 0) {
                                    $saldo = 0;
                                }
                                $ultimoEnvio = $nota->notasEnvio
                                    ->sortByDesc(function ($envio) {
                                        return $envio->fecha_emision ?? $envio->created_at;
                                    })
                                    ->first();
                                $estadoEnvio = $ultimoEnvio?->estatus ?? 'Sin envíos';

                                return [
                                    'nota' => $nota,
                                    'estado_envio' => $estadoEnvio,
                                    'estado_renta' => $estadoRenta($nota),
                                    'total_pagado' => $totalPagado,
                                    'saldo_pendiente' => (float)$saldo,
                                    'ultimo_pago' => $ultimoPago,
                                ];
                            });

                            $itemsEnRenta = RegistroRenta::query()
                                ->with(['producto', 'notaVentaRenta'])
                                ->where('cliente_id', $record->id)
                                ->where(function ($query) {
                                    $query->where('estado', '!=', 'Devuelto')
                                        ->orWhereColumn('cantidad_devuelta', '<', 'cantidad');
                                })
                                ->orderByDesc('fecha_renta')
                                ->get();

                            $notasRentadasData = $notasRenta
                                ->filter(function (NotasVentaRenta $nota) {
                                    return $nota->registrosRenta->contains(function ($registro) {
                                        $pendiente = (float)$registro->cantidad - (float)($registro->cantidad_devuelta ?? 0);
                                        return $registro->estado !== 'Devuelto' && $pendiente > 0;
                                    });
                                })
                                ->map(function (NotasVentaRenta $nota) use ($estadoRenta) {
                                    $items = $nota->registrosRenta
                                        ->filter(function ($registro) {
                                            $pendiente = (float)$registro->cantidad - (float)($registro->cantidad_devuelta ?? 0);
                                            return $registro->estado !== 'Devuelto' && $pendiente > 0;
                                        })
                                        ->map(function ($registro) {
                                            $pendiente = (float)$registro->cantidad - (float)($registro->cantidad_devuelta ?? 0);
                                            $descripcion = $registro->producto?->descripcion ?? 'Item';
                                            return "{$descripcion} x{$pendiente}";
                                        })
                                        ->values();

                                    return [
                                        'nota' => $nota,
                                        'estado_renta' => $estadoRenta($nota),
                                        'items' => $items,
                                    ];
                                })
                                ->values();

                            return view('filament.resources.clientes.reporte', [
                                'cliente' => $cliente,
                                'notasVenta' => $notasVentaData,
                                'notasRenta' => $notasRentaData,
                                'notasRentadas' => $notasRentadasData,
                                'itemsEnRenta' => $itemsEnRenta,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar'),
                ]),
            ],RecordActionsPosition::BeforeColumns)
            ->headerActions([
                \Filament\Actions\Action::make('descargarLayout')
                    ->label('Descargar layout')
                    ->icon('fas-file-arrow-down')
                    ->action(function () {
                        $headers = ClientesImportService::HEADERS;
                        $csv = implode(',', $headers) . "\n";

                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'clientes_layout.csv', ['Content-Type' => 'text/csv']);
                    }),
                \Filament\Actions\Action::make('importar')
                    ->label('Importar')
                    ->icon('fas-file-import')
                    ->modalHeading('Importar clientes')
                    ->form([
                        FileUpload::make('archivo')
                            ->label('Archivo Excel/CSV')
                            ->required()
                            ->acceptedFileTypes([
                                'text/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->disk('local')
                            ->directory('imports/clientes')
                            ->preserveFilenames(),
                    ])
                    ->action(function (array $data) {
                        $importer = app(ClientesImportService::class);
                        $disk = Storage::disk('local');
                        $path = $disk->path($data['archivo']);

                        try {
                            [$insertados, $actualizados] = $importer->importFromPath($path);
                            Notification::make()
                                ->title('Importacion completa')
                                ->body("Insertados: {$insertados}. Actualizados: {$actualizados}.")
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Error al importar')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        } finally {
                            $disk->delete($data['archivo']);
                        }
                    }),
                CreateAction::make()
                    ->createAnother(false)
                    ->label('Nuevo')
                    ->icon('fas-circle-plus')
                    ->modalWidth('7xl')
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
                    }),
            ],HeaderActionsPosition::Bottom);
    }
}

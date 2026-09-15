<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Schemas;

use App\Models\DocumentoSerie;
use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
use App\Models\RegistroRenta;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class NotasDevolucionRentaForm
{
    /**
     * @return array{cliente_id: int|null, partidas: array<int, array<string, mixed>>}
     */
    public static function obtenerDatosIniciales(?int $clienteId, ?int $direccionEntregaId): array
    {
        if (!$clienteId || !$direccionEntregaId) {
            return ['cliente_id' => null, 'partidas' => []];
        }

        return [
            'cliente_id' => $clienteId,
            'direccion_entrega_id' => $direccionEntregaId,
            'partidas' => self::obtenerPartidasPendientes($clienteId, $direccionEntregaId),
        ];
    }

    private static function cargarPartidas(?int $clienteId, ?int $direccionEntregaId, Set $set): void
    {
        $datos = self::obtenerDatosIniciales($clienteId, $direccionEntregaId);
        $set('partidas', $datos['partidas']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function obtenerPartidasPendientes(?int $clienteId, ?int $direccionEntregaId): array
    {
        if (!$clienteId || !$direccionEntregaId) {
            return [];
        }

        $registros = RegistroRenta::query()
            ->with('producto')
            ->where('cliente_id', $clienteId)
            ->whereHas('notaVentaRenta', fn ($query) => $query->where('direccion_entrega_id', $direccionEntregaId))
            ->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')
            ->whereDoesntHave('producto', fn ($query) => $query->where('clave', 'SRENTA-M2'))
            ->get()
            ->groupBy('producto_id');

        $partidas = [];
        foreach ($registros as $productoId => $registrosProducto) {
            $registro = $registrosProducto->first();
            $enviada = (float) $registrosProducto->sum('cantidad');
            $devuelta = (float) $registrosProducto->sum('cantidad_devuelta');
            $pendiente = $enviada - $devuelta;
            if ($pendiente <= 0) {
                continue;
            }

            $partidas[] = [
                'producto_id' => $productoId,
                'descripcion' => $registro->producto?->descripcion ?? 'Producto',
                'cantidad_enviada' => $enviada,
                'cantidad_devuelta' => $devuelta,
                'cantidad_a_devolver' => 0,
                'cantidad_aplicada' => 0,
            ];
        }

        return $partidas;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Encabezado')
                    ->schema([
                        Select::make('serie')
                            ->required()
                            ->options(function () {
                                $series = DocumentoSerie::query()
                                    ->where('documento_tipo', 'notas_devolucion_renta')
                                    ->orderBy('serie')
                                    ->get();

                                if ($series->isEmpty()) {
                                    DocumentoSerie::create([
                                        'documento_tipo' => 'notas_devolucion_renta',
                                        'serie' => 'NDR',
                                        'descripcion' => 'Serie por defecto',
                                        'ultimo_folio' => 0,
                                    ]);

                                    $series = DocumentoSerie::query()
                                        ->where('documento_tipo', 'notas_devolucion_renta')
                                        ->orderBy('serie')
                                        ->get();
                                }

                                return $series->mapWithKeys(fn (DocumentoSerie $serie) => [$serie->serie => $serie->label()])->all();
                            })
                            ->default('NDR')
                            ->searchable()
                            ->preload(),
                        TextInput::make('folio')
                            ->readOnly()
                            ->helperText('Se asigna al guardar.'),
                        TextInput::make('folio_interno')
                            ->label('Folio interno')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Referencia interna'),
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->required()
                            ->options(fn () => Clientes::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                            ->disabledOn('edit')
                            ->live()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('direccion_entrega_id', null);
                                $set('partidas', []);
                            }),
                        Select::make('direccion_entrega_id')
                            ->label('Obra')
                            ->required()
                            ->options(function (callable $get) {
                                $clienteId = (int) ($get('cliente_id') ?: 0);
                                if (!$clienteId) {
                                    return [];
                                }

                                return ClienteDireccionEntrega::query()
                                    ->where('cliente_id', $clienteId)
                                    ->where('activa', true)
                                    ->whereHas('cliente.notasVentaRenta', function ($query) {
                                        $query->whereHas('registrosRenta', function ($registroQuery) {
                                            $registroQuery->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad');
                                        });
                                    })
                                    ->orderBy('nombre_direccion')
                                    ->get()
                                    ->mapWithKeys(fn (ClienteDireccionEntrega $direccion) => [
                                        $direccion->id => $direccion->nombre_direccion . ' - ' . $direccion->direccion_completa,
                                    ])
                                    ->all();
                            })
                            ->disabled(fn (callable $get): bool => !$get('cliente_id'))
                            ->live()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, Set $set, callable $get) {
                                self::cargarPartidas((int) ($get('cliente_id') ?: 0), (int) ($state ?: 0), $set);
                            }),
                        TextInput::make('estatus')
                            ->default('Pendiente')
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('observaciones')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)->columnSpanFull(),
                Section::make('Productos rentados en la obra')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('partidas')
                            ->relationship()
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->compact()
                            ->table([
                                Repeater\TableColumn::make('Producto'),
                                Repeater\TableColumn::make('Cantidad enviada'),
                                Repeater\TableColumn::make('Cantidad devuelta'),
                                Repeater\TableColumn::make('Devolución'),
                            ])
                            ->schema([
                                Hidden::make('producto_id'),
                                Hidden::make('cantidad_aplicada')
                                    ->default(0),
                                TextInput::make('descripcion')
                                    ->label('Item')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TextInput::make('cantidad_enviada')
                                    ->label('Cantidad enviada')
                                    ->numeric()
                                    ->readOnly(),
                                TextInput::make('cantidad_devuelta')
                                    ->label('Cantidad devuelta')
                                    ->numeric()
                                    ->readOnly(),
                                TextInput::make('cantidad_a_devolver')
                                    ->label('Devolución')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(fn (callable $get): float => max(0, (float) $get('cantidad_enviada') - (float) $get('cantidad_devuelta')))
                                    ->dehydrated()
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Schemas;

use App\Models\DocumentoSerie;
use App\Models\NotaEnvio;
use App\Models\NotasVentaRenta;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class NotasDevolucionRentaForm
{
    private static function cargarPartidasPendientes(?int $notaEnvioId, Set $set): void
    {
        if (!$notaEnvioId) {
            $set('partidas', []);
            return;
        }

        $notaEnvio = NotaEnvio::query()
            ->with(['partidas', 'cliente'])
            ->find($notaEnvioId);

        if (!$notaEnvio) {
            $set('partidas', []);
            return;
        }

        $set('cliente_id', $notaEnvio->cliente_id);

        $partidas = [];
        foreach ($notaEnvio->partidas as $partidaEnvio) {
            $pendiente = (float) $partidaEnvio->cantidad - (float) $partidaEnvio->cantidad_devuelta;
            if ($pendiente <= 0) {
                continue;
            }

            $partidas[] = [
                'nota_envio_partida_id' => $partidaEnvio->id,
                'producto_id' => $partidaEnvio->producto_id,
                'descripcion' => $partidaEnvio->descripcion,
                'cantidad_programada' => $pendiente,
                'cantidad_recogida' => 0,
                'cantidad_aplicada' => 0,
                'observaciones' => $partidaEnvio->observaciones,
            ];
        }

        $set('partidas', $partidas);
    }

    public static function configure(Schema $schema): Schema
    {
        $notaEnvioDefault = request()->integer('nota_envio_id') ?: null;
        $notaOrigenDefault = null;

        if ($notaEnvioDefault) {
            $notaOrigenDefault = NotaEnvio::query()
                ->whereKey($notaEnvioDefault)
                ->value('nota_venta_renta_id');
        }

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
                        DatePicker::make('fecha_emision')
                            ->required()
                            ->default(now()->format('Y-m-d')),
                        Select::make('nota_venta_renta_id')
                            ->label('Nota Origen (Nota de Renta)')
                            ->required()
                            ->default($notaOrigenDefault)
                            ->disabledOn('edit')
                            ->options(function () {
                                return NotasVentaRenta::query()
                                    ->with('cliente')
                                    ->whereHas('notasEnvio', function ($query) {
                                        $query->whereHas('partidas', function ($q) {
                                            $q->whereRaw('cantidad_devuelta < cantidad');
                                        });
                                    })
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(function (NotasVentaRenta $nota) {
                                        $cliente = $nota->cliente?->nombre ?? 'Sin cliente';
                                        return [$nota->id => 'NVR ' . ($nota->serie ?? '') . $nota->folio . ' - ' . $cliente];
                                    })
                                    ->all();
                            })
                            ->live()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('nota_envio_id', null);
                                $set('cliente_id', null);
                                $set('partidas', []);

                                if (!$state) {
                                    return;
                                }

                                $clienteId = NotasVentaRenta::query()->whereKey((int) $state)->value('cliente_id');
                                $set('cliente_id', $clienteId);
                            }),
                        Select::make('nota_envio_id')
                            ->label('Nota de envio origen')
                            ->required()
                            ->disabledOn('edit')
                            ->default($notaEnvioDefault)
                            ->options(function (Get $get) {
                                $notaOrigenId = $get('nota_venta_renta_id');

                                if (!$notaOrigenId) {
                                    return [];
                                }

                                return NotaEnvio::query()
                                    ->with('cliente')
                                    ->where('nota_venta_renta_id', $notaOrigenId)
                                    ->whereHas('partidas', function ($query) {
                                        $query->whereRaw('cantidad_devuelta < cantidad');
                                    })
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(function (NotaEnvio $notaEnvio) {
                                        $cliente = $notaEnvio->cliente?->nombre ?? 'Sin cliente';
                                        return [$notaEnvio->id => 'Folio ' . $notaEnvio->folio . ' - ' . $cliente];
                                    })
                                    ->all();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                self::cargarPartidasPendientes($state ? (int) $state : null, $set);
                            })
                            ->searchable()
                            ->preload(),
                        Select::make('cliente_id')
                            ->relationship('cliente', 'nombre')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('estatus')
                            ->default('Pendiente')
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('observaciones')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)->columnSpanFull(),
                Section::make('Partidas a recoger')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('partidas')
                            ->relationship()
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->schema([
                                Hidden::make('nota_envio_partida_id'),
                                Hidden::make('producto_id'),
                                TextInput::make('descripcion')
                                    ->label('Item')
                                    ->readOnly()
                                    ->columnSpan(4),
                                TextInput::make('cantidad_programada')
                                    ->label('Cant. programada')
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2),
                                TextInput::make('cantidad_recogida')
                                    ->label('Cant. recogida real')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->columnSpan(2),
                                Hidden::make('cantidad_aplicada')
                                    ->default(0),
                                TextInput::make('observaciones')
                                    ->columnSpan(4),
                            ])
                            ->columns(8)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

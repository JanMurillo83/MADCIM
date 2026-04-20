<?php

namespace App\Filament\Resources\NotasDevolucionRenta\Schemas;

use App\Models\DocumentoSerie;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use Filament\Forms\Components\DatePicker;
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
    private static function cargarPartidasPendientesDeNVR(?int $notaVentaRentaId, Set $set): void
    {
        if (!$notaVentaRentaId) {
            $set('partidas', []);
            return;
        }

        $envioIds = NotaEnvio::query()
            ->where('nota_venta_renta_id', $notaVentaRentaId)
            ->pluck('id');

        if ($envioIds->isEmpty()) {
            $set('partidas', []);
            return;
        }

        $partidasEnvio = NotaEnvioPartida::query()
            ->with('notaEnvio')
            ->whereIn('nota_envio_id', $envioIds)
            ->whereRaw('cantidad_devuelta < cantidad')
            ->whereDoesntHave('producto', fn ($q) => $q->where('clave', 'SRENTA-M2'))
            ->get();

        $partidas = [];
        foreach ($partidasEnvio as $partidaEnvio) {
            $pendiente = (float) $partidaEnvio->cantidad - (float) $partidaEnvio->cantidad_devuelta;
            if ($pendiente <= 0) {
                continue;
            }

            $folioEnvio = $partidaEnvio->notaEnvio?->folio ?? '';
            $descripcionItem = $partidaEnvio->descripcion;

            $partidas[] = [
                'nota_envio_partida_id' => $partidaEnvio->id,
                'producto_id' => $partidaEnvio->producto_id,
                'descripcion' => $descripcionItem,
                'nota_envio_folio' => $folioEnvio,
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
        $notaOrigenDefault = request()->integer('nota_venta_renta_id') ?: null;

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
                                $set('cliente_id', null);
                                $set('partidas', []);

                                if (!$state) {
                                    return;
                                }

                                $clienteId = NotasVentaRenta::query()->whereKey((int) $state)->value('cliente_id');
                                $set('cliente_id', $clienteId);

                                self::cargarPartidasPendientesDeNVR((int) $state, $set);
                            }),
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
                Section::make('Partidas pendientes de devolver')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('partidas')
                            ->relationship()
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->compact()
                            ->table([
                                Repeater\TableColumn::make('Item'),
                                Repeater\TableColumn::make('Envío'),
                                Repeater\TableColumn::make('Programada'),
                                Repeater\TableColumn::make('Recogida'),
                            ])
                            ->schema([
                                Hidden::make('nota_envio_partida_id'),
                                Hidden::make('producto_id'),
                                Hidden::make('cantidad_aplicada')
                                    ->default(0),
                                TextInput::make('descripcion')
                                    ->label('Item')
                                    ->readOnly()
                                    ->columnSpan(2),
                                TextInput::make('nota_envio_folio')
                                    ->label('Envío')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (TextInput $component, $record) {
                                        if ($record && $record->notaEnvioPartida?->notaEnvio) {
                                            $component->state($record->notaEnvioPartida->notaEnvio->folio);
                                        }
                                    })
                                    ->columnSpan(1),
                                TextInput::make('cantidad_programada')
                                    ->label('Programada')
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(1),
                                TextInput::make('cantidad_recogida')
                                    ->label('Recogida')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

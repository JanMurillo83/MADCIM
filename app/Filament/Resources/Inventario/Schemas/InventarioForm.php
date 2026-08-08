<?php

namespace App\Filament\Resources\Inventario\Schemas;

use App\Models\Productos;
use App\Support\Numero;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class InventarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movimiento de Inventario')
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return Productos::query()
                                    ->orderBy('clave')
                                    ->get()
                                    ->mapWithKeys(function (Productos $producto) {
                                        $existencia = Numero::formato($producto->existencia, 2);

                                        return [
                                            $producto->id => $producto->clave . ' - ' . $producto->descripcion . ' | Existencia: ' . $existencia,
                                        ];
                                    })
                                    ->all();
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $producto = Productos::find($get('producto_id'));
                                $set('existencia_antes', $producto?->existencia ?? 0);
                                self::calcularExistenciaDespues($get, $set);
                            })
                            ->disabled(fn (?string $context) => $context === 'edit'),
                        Select::make('tipo')
                            ->label('Tipo de Movimiento')
                            ->required()
                            ->options([
                                'entrada' => 'Entrada',
                                'salida' => 'Salida',
                                'ajuste' => 'Ajuste',
                            ])
                            ->default('entrada')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calcularExistenciaDespues($get, $set);
                            })
                            ->disabled(fn (?string $context) => $context === 'edit'),
                        TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0.0001)
                            ->step(0.0001)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calcularExistenciaDespues($get, $set);
                            })
                            ->disabled(fn (?string $context) => $context === 'edit'),
                        TextInput::make('existencia_antes')
                            ->label('Existencia Antes')
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters(',')
                            ->default(0)
                            ->readOnly(),
                        TextInput::make('existencia_despues')
                            ->label('Existencia Después')
                            ->numeric()
                            ->prefix('$')
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters(',')
                            ->default(0)
                            ->readOnly(),
                        DateTimePicker::make('fecha_movimiento')
                            ->label('Fecha de Movimiento')
                            ->required()
                            ->default(now())
                            ->disabled(fn (?string $context) => $context === 'edit'),
                        TextInput::make('documento_referencia')
                            ->label('Documento de Referencia')
                            ->maxLength(50)
                            ->disabled(fn (?string $context) => $context === 'edit'),
                        Textarea::make('motivo')
                            ->label('Motivo')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->disabled(fn (?string $context) => $context === 'edit'),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    private static function calcularExistenciaDespues(Get $get, Set $set): void
    {
        $tipo = $get('tipo') ?? 'entrada';
        $cantidad = (float) ($get('cantidad') ?? 0);
        $existenciaAntes = (float) ($get('existencia_antes') ?? 0);

        $existenciaDespues = match ($tipo) {
            'entrada' => $existenciaAntes + $cantidad,
            'salida' => $existenciaAntes - $cantidad,
            'ajuste' => $cantidad,
            default => $existenciaAntes,
        };

        $set('existencia_despues', Numero::redondear($existenciaDespues, 4));
    }
}

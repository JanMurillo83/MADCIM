<?php

namespace App\Filament\Resources\CajaMovimientos\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CajaMovimientosForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('caja_id')
                ->label('Caja')
                ->relationship('caja', 'nombre')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'Egreso' => 'Egreso',
                    'Ajuste' => 'Ajuste',
                ])
                ->native(false)
                ->required()
                ->hint('Los ingresos se generan automáticamente desde Pagos en efectivo.'),
            Select::make('metodo_pago')
                ->label('Método de pago')
                ->options([
                    'Efectivo' => 'Efectivo',
                    'Transferencia' => 'Transferencia',
                    'Tarjeta' => 'Tarjeta',
                    'Cheque' => 'Cheque',
                ])
                ->required(),
            TextInput::make('importe')
                ->label('Importe')
                ->numeric()
                ->prefix('MXN $')
                ->required(),
            TextInput::make('referencia')
                ->label('Referencia')
                ->maxLength(255),
            DateTimePicker::make('fecha')
                ->label('Fecha')
                ->seconds(false)
                ->default(now())
                ->required(),
            Textarea::make('observaciones')
                ->label('Observaciones')
                ->columnSpanFull()
                ->rows(3),
        ]);
    }
}

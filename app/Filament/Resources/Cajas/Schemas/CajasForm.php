<?php

namespace App\Filament\Resources\Cajas\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CajasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('nombre')->label('Nombre')->maxLength(255),
            TextInput::make('saldo_inicial_cash')->label('Saldo inicial (efectivo)')->numeric()->minValue(0)->step(0.01),
            Select::make('estatus')->options([
                'Abierta' => 'Abierta',
                'Cerrada' => 'Cerrada',
                'Bloqueada' => 'Bloqueada',
            ])->required(),
            DateTimePicker::make('fecha_apertura')->seconds(false),
            DateTimePicker::make('fecha_cierre')->seconds(false),
            Textarea::make('observaciones_cierre')->columnSpanFull(),
        ]);
    }
}

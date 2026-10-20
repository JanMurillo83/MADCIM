<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Horarios\Schemas;

use App\Models\Sucursal;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HorarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Horario')
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->required()
                        ->searchable(),
                    TimePicker::make('hora_entrada')
                        ->label('Entrada')
                        ->seconds(false)
                        ->required(),
                    TimePicker::make('hora_salida_comida')
                        ->label('Salida a comida')
                        ->seconds(false),
                    TimePicker::make('hora_regreso_comida')
                        ->label('Regreso de comida')
                        ->seconds(false),
                    TimePicker::make('hora_salida')
                        ->label('Salida')
                        ->seconds(false)
                        ->required(),
                    Select::make('dia_descanso')
                        ->label('Día de descanso')
                        ->options([
                            0 => 'Domingo',
                            1 => 'Lunes',
                            2 => 'Martes',
                            3 => 'Miércoles',
                            4 => 'Jueves',
                            5 => 'Viernes',
                            6 => 'Sábado',
                        ])
                        ->required(),
                    Toggle::make('activo')
                        ->label('Activo')
                        ->default(true),
                ])->columns(2),
            Section::make('Políticas de tolerancia')
                ->schema([
                    TextInput::make('tolerancia_entrada')
                        ->label('Tolerancia de entrada (minutos)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(120)
                        ->required()
                        ->default(10),
                    TextInput::make('tolerancia_regreso_comida')
                        ->label('Tolerancia de regreso (minutos)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(120)
                        ->required()
                        ->default(10),
                    TextInput::make('duracion_comida')
                        ->label('Duración de comida (minutos)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(480)
                        ->required()
                        ->default(60),
                ])->columns(3),
        ]);
    }
}

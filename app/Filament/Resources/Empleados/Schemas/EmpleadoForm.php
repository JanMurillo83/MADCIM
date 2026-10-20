<?php

namespace App\Filament\Resources\Empleados\Schemas;

use App\Models\Sucursal;
use App\Models\Horario;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmpleadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación')
                ->schema([
                    TextInput::make('numero_empleado')
                        ->label('Número de empleado')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    TextInput::make('nombre')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('apellido_paterno')
                        ->label('Apellido paterno')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('apellido_materno')
                        ->label('Apellido materno')
                        ->maxLength(255),
                ])->columns(2),
            Section::make('Relación laboral')
                ->schema([
                    Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->required()
                        ->searchable(),
                    Select::make('horario_id')
                        ->label('Horario asignado')
                        ->options(fn (): array => Horario::query()
                            ->where('activo', true)
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn (Horario $horario): array => [
                                $horario->id => "{$horario->nombre} ({$horario->sucursal?->nombre})",
                            ])
                            ->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('departamento')
                        ->maxLength(255),
                    TextInput::make('puesto')
                        ->maxLength(255),
                    DatePicker::make('fecha_ingreso')
                        ->label('Fecha de ingreso')
                        ->required(),
                    TextInput::make('tipo_contrato')
                        ->label('Tipo de contrato')
                        ->maxLength(50),
                    Select::make('estatus')
                        ->options([
                            'activo' => 'Activo',
                            'inactivo' => 'Inactivo',
                            'baja' => 'Baja',
                        ])
                        ->default('activo')
                        ->required(),
                    TextInput::make('sueldo_diario')
                        ->label('Sueldo diario')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('sueldo_semanal')
                        ->label('Sueldo semanal')
                        ->numeric()
                        ->minValue(0),
                ])->columns(2),
        ]);
    }
}

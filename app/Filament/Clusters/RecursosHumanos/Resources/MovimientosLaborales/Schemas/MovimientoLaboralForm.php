<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Schemas;

use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Sucursal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MovimientoLaboralForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Movimiento')->schema([
                Select::make('empleado_id')->label('Empleado')
                    ->options(fn (): array => Empleado::query()->where('estatus', 'activo')
                        ->orderBy('apellido_paterno')->get()->mapWithKeys(fn (Empleado $empleado): array => [
                            $empleado->id => "{$empleado->numero_empleado} - {$empleado->nombre_completo}",
                        ])->all())
                    ->required()->searchable()->preload(),
                DatePicker::make('fecha_movimiento')->label('Fecha')->required(),
                Select::make('tipo')->label('Tipo')->options([
                    'alta' => 'Alta',
                    'promocion' => 'Promoción',
                    'transferencia' => 'Transferencia de sucursal',
                    'cambio_puesto' => 'Cambio de puesto',
                    'cambio_horario' => 'Cambio de horario',
                    'reingreso' => 'Reingreso',
                ])->required(),
                Select::make('sucursal_origen_id')->label('Sucursal origen')
                    ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->searchable(),
                Select::make('sucursal_destino_id')->label('Sucursal destino')
                    ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->searchable(),
                TextInput::make('departamento')->label('Departamento')->maxLength(255),
                TextInput::make('puesto')->label('Puesto')->maxLength(255),
                Select::make('horario_id')->label('Horario')
                    ->options(fn (): array => Horario::query()->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->searchable(),
                Textarea::make('motivo')->required()->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

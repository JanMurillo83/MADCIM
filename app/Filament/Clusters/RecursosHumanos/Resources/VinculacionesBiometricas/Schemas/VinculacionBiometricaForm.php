<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\Schemas;

use App\Models\DispositivoBiometrico;
use App\Models\Empleado;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VinculacionBiometricaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('empleado_id')
                ->label('Empleado')
                ->options(fn (): array => Empleado::query()
                    ->where('estatus', 'activo')
                    ->orderBy('apellido_paterno')
                    ->get()
                    ->mapWithKeys(fn (Empleado $empleado): array => [
                        $empleado->id => "{$empleado->numero_empleado} - {$empleado->nombre_completo}",
                    ])
                    ->all())
                ->searchable()
                ->preload()
                ->required(),
            Select::make('dispositivo_biometrico_id')
                ->label('Dispositivo')
                ->options(fn (): array => DispositivoBiometrico::query()
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('identificador')
                ->label('Identificador en el dispositivo')
                ->required()
                ->maxLength(120)
                ->helperText('Debe coincidir con el PIN/USERID enviado por el equipo.'),
        ]);
    }
}

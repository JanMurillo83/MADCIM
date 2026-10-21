<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Schemas;

use App\Models\Empleado;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HistorialSalarialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cambio salarial')->schema([
                Select::make('empleado_id')->label('Empleado')
                    ->options(fn (): array => Empleado::query()->where('estatus', 'activo')
                        ->orderBy('apellido_paterno')->get()->mapWithKeys(fn (Empleado $empleado): array => [
                            $empleado->id => "{$empleado->numero_empleado} - {$empleado->nombre_completo}",
                        ])->all())
                    ->required()->searchable()->preload(),
                DatePicker::make('fecha_inicio')->label('Vigente desde')->required(),
                DatePicker::make('fecha_fin')->label('Vigente hasta'),
                TextInput::make('sueldo_diario')->label('Sueldo diario')->numeric()->minValue(0)->required(),
                TextInput::make('sueldo_semanal')->label('Sueldo semanal')->numeric()->minValue(0),
                Textarea::make('motivo')->label('Motivo')->required()->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

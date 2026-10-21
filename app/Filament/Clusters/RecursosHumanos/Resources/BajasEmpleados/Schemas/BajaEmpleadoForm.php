<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Schemas;

use App\Models\Empleado;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BajaEmpleadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Baja laboral')->schema([
                Select::make('empleado_id')
                    ->label('Empleado')
                    ->options(fn (): array => Empleado::query()
                        ->where('estatus', 'activo')
                        ->orderBy('apellido_paterno')
                        ->get()
                        ->mapWithKeys(fn (Empleado $empleado): array => [
                            $empleado->id => "{$empleado->numero_empleado} - {$empleado->nombre_completo}",
                        ])->all())
                    ->required()
                    ->searchable()
                    ->preload(),
                DatePicker::make('fecha_baja')
                    ->label('Fecha efectiva')
                    ->required(),
                Select::make('tipo')
                    ->label('Tipo de baja')
                    ->options([
                        'renuncia' => 'Renuncia',
                        'terminacion' => 'Terminación laboral',
                        'abandono' => 'Abandono',
                        'jubilacion' => 'Jubilación',
                        'otro' => 'Otro',
                    ])
                    ->required(),
                Textarea::make('motivo')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('observaciones')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

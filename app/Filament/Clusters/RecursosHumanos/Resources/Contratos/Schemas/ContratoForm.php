<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Schemas;

use App\Models\Empleado;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContratoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del contrato')->schema([
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
                TextInput::make('tipo')
                    ->label('Tipo de contrato')
                    ->required()
                    ->maxLength(60),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->label('Fecha de vencimiento'),
                Select::make('estatus')
                    ->options([
                        'vigente' => 'Vigente',
                        'vencido' => 'Vencido',
                        'cancelado' => 'Cancelado',
                    ])
                    ->default('vigente')
                    ->required(),
                Select::make('decision')
                    ->label('Decisión de renovación')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'renovar' => 'Renovar',
                        'no_renovar' => 'No renovar',
                    ]),
                FileUpload::make('archivo')
                    ->label('Contrato PDF')
                    ->disk('local')
                    ->directory('expedientes/contratos')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->nullable(),
                Textarea::make('observaciones')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

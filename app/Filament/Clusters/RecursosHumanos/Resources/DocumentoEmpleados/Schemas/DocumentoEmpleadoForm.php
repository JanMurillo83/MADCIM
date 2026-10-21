<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DocumentoEmpleados\Schemas;

use App\Models\Empleado;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentoEmpleadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Documento laboral')->schema([
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
                Select::make('tipo')
                    ->label('Tipo de documento')
                    ->options([
                        'identificacion' => 'Identificación',
                        'domicilio' => 'Comprobante de domicilio',
                        'imss' => 'IMSS',
                        'fiscal' => 'Constancia fiscal',
                        'estudios' => 'Estudios',
                        'otro' => 'Otro',
                    ])
                    ->required()
                    ->searchable(),
                TextInput::make('nombre')->label('Nombre o descripción')->required()->maxLength(255),
                FileUpload::make('archivo')
                    ->label('Archivo')
                    ->disk('local')
                    ->directory('expedientes/documentos')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(10240)
                    ->required(),
                DatePicker::make('fecha_emision')->label('Fecha de emisión'),
                DatePicker::make('fecha_vencimiento')->label('Fecha de vencimiento'),
                TextInput::make('dias_alerta')->label('Alertar con días de anticipación')
                    ->numeric()->default(30)->minValue(1)->maxValue(365)->required(),
                Select::make('estatus')
                    ->options([
                        'vigente' => 'Vigente',
                        'vencido' => 'Vencido',
                        'cancelado' => 'Cancelado',
                    ])->default('vigente')->required(),
                Textarea::make('observaciones')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}

<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Schemas;

use App\Enums\TipoIncidencia;
use App\Models\Empleado;
use App\Models\Sucursal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncidenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Incidencia')
                ->schema([
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
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->required()
                        ->searchable(),
                    Select::make('tipo')
                        ->label('Tipo')
                        ->options(TipoIncidencia::options())
                        ->required(),
                    DatePicker::make('fecha_inicio')
                        ->label('Fecha inicial')
                        ->required(),
                    DatePicker::make('fecha_fin')
                        ->label('Fecha final'),
                    TimePicker::make('hora_inicio')
                        ->label('Hora inicial')
                        ->seconds(false),
                    TimePicker::make('hora_fin')
                        ->label('Hora final')
                        ->seconds(false),
                    TextInput::make('minutos')
                        ->label('Minutos')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('dias')
                        ->label('Días')
                        ->numeric()
                        ->minValue(0),
                ])->columns(2),
            Section::make('Justificación')
                ->schema([
                    Textarea::make('motivo')
                        ->label('Motivo')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(3)
                        ->columnSpanFull(),
                    Toggle::make('afecta_nomina')
                        ->label('Descontar en nómina')
                        ->default(false)
                        ->helperText('Para un retardo autorizado, activa esta opción para conservar el descuento de minutos. En permisos o faltas, define si son sin goce de sueldo.'),
                    Hidden::make('estatus')->default('pendiente'),
                    Hidden::make('solicitada_por_id')->default(fn (): ?int => auth()->id()),
                ])->columns(2),
        ]);
    }
}

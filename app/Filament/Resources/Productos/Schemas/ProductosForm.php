<?php

namespace App\Filament\Resources\Productos\Schemas;

use App\Models\Grupos;
use App\Models\Lineas;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;


class ProductosForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('clave')
                    ->required()->columnSpan(2),
                TextInput::make('descripcion')
                    ->required()->columnSpanFull(),
                TextInput::make('m2_cubre')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('costo')
                    ->label('Costo promedio')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('ultimo_costo')
                    ->label('Ultimo costo')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('precio_venta')
                    ->label('Precio de Venta')
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('precio_renta_mes')
                    ->required()
                    ->label('Precio Renta Mensual')
                    ->prefix('$')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('precio_renta_dia')
                    ->required()
                    ->label('Precio Renta Diaria')
                    ->prefix('$')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('precio_renta_semana')
                    ->required()
                    ->label('Precio Renta Semanal')
                    ->prefix('$')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('existencia')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('grupo')
                    ->options(Grupos::all()->pluck('nombre', 'nombre'))
                    ->required(),
                Select::make('linea')
                    ->options(Lineas::all()->pluck('nombre', 'nombre'))
                    ->required(),
                TextInput::make('largo')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('ancho')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                FileUpload::make('imagen')
                    ->disk('public')
                    ->image()
                    ->directory('productos')
                    ->downloadable()
            ])->columns(5);
    }
}

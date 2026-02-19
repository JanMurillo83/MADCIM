<?php

namespace App\Filament\Resources\ClienteDireccionEntregas\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\Clientes;

class ClienteDireccionEntregaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Información del Cliente')
                    ->schema([
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('nombre_direccion')
                            ->label('Nombre de la Dirección')
                            ->placeholder('Ej: Oficina Principal, Almacén 2')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Dirección de Entrega')
                    ->schema([
                        TextInput::make('calle')
                            ->label('Calle')
                            ->required()
                            ->maxLength(255),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('numero_exterior')
                                    ->label('Número Exterior')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('numero_interior')
                                    ->label('Número Interior')
                                    ->maxLength(255),
                            ]),

                        TextInput::make('colonia')
                            ->label('Colonia')
                            ->required()
                            ->maxLength(255),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('municipio')
                                    ->label('Municipio')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('estado')
                                    ->label('Estado')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('codigo_postal')
                                    ->label('Código Postal')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        TextInput::make('pais')
                            ->label('País')
                            ->default('México')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('referencias')
                            ->label('Referencias')
                            ->placeholder('Referencias adicionales para encontrar el lugar')
                            ->rows(3)
                            ->maxLength(65535),
                    ]),

                Section::make('Contacto en el Lugar')
                    ->schema([
                        TextInput::make('contacto_nombre')
                            ->label('Nombre del Contacto')
                            ->maxLength(255),

                        TextInput::make('contacto_telefono')
                            ->label('Teléfono del Contacto')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Configuración')
                    ->schema([
                        Checkbox::make('es_principal')
                            ->label('Dirección Principal')
                            ->helperText('Marcar como dirección de entrega principal'),

                        Checkbox::make('activa')
                            ->label('Activa')
                            ->default(true)
                            ->helperText('Solo las direcciones activas aparecerán en los documentos'),
                    ])
                    ->columns(2),
            ]);
    }
}

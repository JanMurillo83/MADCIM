<?php

namespace App\Filament\Resources\Documentos\Schemas;

use App\Enums\TipoDocumento;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rules\Enum;

class DocumentosForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Encabezado')
                    ->schema([
                        Select::make('tipo')
                            ->options(TipoDocumento::options())
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->rules([new Enum(TipoDocumento::class)]),
                        TextInput::make('serie')
                            ->maxLength(20),
                        TextInput::make('folio')
                            ->maxLength(50),
                        DateTimePicker::make('fecha_emision'),
                        TextInput::make('moneda')
                            ->required()
                            ->default('MXN')
                            ->maxLength(3),
                        TextInput::make('tipo_cambio')
                            ->required()
                            ->numeric()
                            ->default(1.0),
                        TextInput::make('estatus')
                            ->required()
                            ->default('borrador'),
                        Select::make('documento_origen_id')
                            ->label('Documento origen')
                            ->relationship('documentoOrigen', 'folio')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(4),
                Section::make('Totales')
                    ->schema([
                        TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('impuestos_total')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('total')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                    ])
                    ->columns(3),
                Section::make('CFDI')
                    ->schema([
                        TextInput::make('uso_cfdi')
                            ->maxLength(10),
                        TextInput::make('forma_pago')
                            ->maxLength(5),
                        TextInput::make('metodo_pago')
                            ->maxLength(5),
                        TextInput::make('regimen_fiscal_receptor')
                            ->maxLength(5),
                        TextInput::make('rfc_emisor')
                            ->maxLength(13),
                        TextInput::make('rfc_receptor')
                            ->maxLength(13),
                        TextInput::make('razon_social_receptor')
                            ->maxLength(255),
                        TextInput::make('cfdi_uuid')
                            ->maxLength(36),
                    ])
                    ->columns(3),
                Section::make('Partidas')
                    ->schema([
                        Repeater::make('partidas')
                            ->table([
                                Repeater\TableColumn::make('Cantidad'),
                                Repeater\TableColumn::make('Item'),
                                Repeater\TableColumn::make('Descripción'),
                                Repeater\TableColumn::make('Precio'),
                                Repeater\TableColumn::make('Subtotal'),
                                Repeater\TableColumn::make('Impuestos'),
                                Repeater\TableColumn::make('Total')
                            ])
                            ->compact()
                            ->relationship()
                            ->schema([
                                TextInput::make('cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(1),
                                TextInput::make('item')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('descripcion')
                                    ->required()
                                    ->columnSpan(2)
                                    ->maxLength(255),
                                TextInput::make('valor_unitario')
                                    ->numeric()
                                    ->required()
                                    ->default(0.0),
                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->required()
                                    ->default(0.0),
                                TextInput::make('impuestos')
                                    ->numeric()
                                    ->required()
                                    ->default(0.0),
                                TextInput::make('total')
                                    ->numeric()
                                    ->required()
                                    ->default(0.0),
                            ])
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}

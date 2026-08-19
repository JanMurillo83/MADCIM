<?php

namespace App\Filament\Resources\Clientes\Schemas;

use App\Models\Clientes;
use App\Models\SatRegimenFiscal;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ClientesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                ->tabs([
                    Tab::make('Datos Generales')
                    ->schema([
                        TextInput::make('clave')
                            ->required()
                        ->default(fn () => Clientes::all()->count() + 1),
                        TextInput::make('nombre')
                            ->required()->columnSpan(3),
                        TextInput::make('rfc')
                            ->required(),
                        TextInput::make('curp')
                            ->label('CURP')
                            ->maxLength(18)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : strtoupper(trim($state))),
                        FileUpload::make('ine')
                            ->label('INE escaneada')
                            ->disk('local')
                            ->directory('clientes/ine')
                            ->image()
                            ->maxSize(5120)
                            ->openable()
                            ->downloadable(),
                        Select::make('regimen')
                            ->label('Régimen fiscal')
                            ->options(fn () => SatRegimenFiscal::query()
                                ->orderBy('clave')
                                ->get()
                                ->mapWithKeys(fn (SatRegimenFiscal $regimen) => [
                                    $regimen->clave => "{$regimen->clave} - {$regimen->descripcion}",
                                ])
                                ->all())
                            ->preload()
                            ->searchable()
                            ->required(),
                        TextInput::make('telefono')
                            ->tel()
                            ->required(),
                        TextInput::make('correo')
                            ->required(),
                    ])->columnSpanFull()->columns(4),
                    Tab::make('Dirección')
                    ->schema([
                        TextInput::make('calle'),
                        TextInput::make('exterior'),
                        TextInput::make('interior'),
                        TextInput::make('colonia'),
                        TextInput::make('municipio'),
                        TextInput::make('estado'),
                        TextInput::make('pais')->default('MEX'),
                        TextInput::make('codigo')->required(),
                    ])->columnSpanFull()->columns(4),
                    Tab::make('Datos de Venta')
                    ->schema([
                        TextInput::make('descuento')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('lista')
                            ->required()
                            ->numeric()
                            ->default(1),
                        TextInput::make('contacto')
                            ->required(),
                        TextInput::make('dias_credito')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('saldo')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        Hidden::make('estatus_cliente')->default(Clientes::ESTATUS_ACTIVO),
                        \Filament\Forms\Components\Checkbox::make('desbloqueo_discrecional')
                            ->label('Permitir desbloqueo discrecional'),
                    ])->columnSpanFull()->columns(3),
                ])->columnSpanFull()
            ]);
    }
}

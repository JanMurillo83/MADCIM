<?php

namespace App\Filament\Resources\Embarques\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmbarquesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('folio')->label('Folio')->maxLength(50)->disabled(),
            DateTimePicker::make('fecha_programada')->label('Fecha programada')->seconds(false)->required(),
            TextInput::make('vehiculo')->label('Vehículo')->maxLength(100)->required(),
            Select::make('chofer_id')->label('Chofer')->relationship('chofer', 'name')->searchable()->preload()->required(),
            Select::make('cliente_id')->label('Cliente')->relationship('cliente', 'nombre')->searchable()->preload(),
            Select::make('direccion_entrega_id')->label('Dirección de entrega')->options(function (callable $get) {
                $clienteId = $get('cliente_id');
                if (!$clienteId) return [];
                return \App\Models\ClienteDireccionEntrega::where('cliente_id', $clienteId)
                    ->orderBy('es_principal', 'desc')
                    ->orderBy('nombre_direccion')
                    ->get()
                    ->mapWithKeys(fn($d) => [$d->id => ($d->nombre_direccion ? ($d->nombre_direccion.' — ') : '').$d->direccion_completa]);
            })->searchable()->preload(),
            Select::make('estatus')->label('Estatus')->options([
                'Programado' => 'Programado',
                'En ruta' => 'En ruta',
                'Entregado' => 'Entregado',
                'Parcial' => 'Parcial',
                'Cancelado' => 'Cancelado',
            ])->default('Programado')->required(),
            Textarea::make('observaciones')->label('Observaciones')->rows(3)->columnSpanFull(),
        ]);
    }
}

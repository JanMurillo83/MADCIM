<?php

namespace App\Filament\Resources\Configuracion\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class ConfiguracionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('razon_social')
                    ->label('Razon social')
                    ->searchable(),
                TextColumn::make('rfc')
                    ->label('RFC')
                    ->searchable(),
                TextColumn::make('regimen')
                    ->label('Regimen')
                    ->searchable(),
                TextColumn::make('codigo')
                    ->label('Codigo postal')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ], RecordActionsPosition::BeforeColumns);
    }
}

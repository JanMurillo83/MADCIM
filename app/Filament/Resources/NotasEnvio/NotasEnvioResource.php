<?php

namespace App\Filament\Resources\NotasEnvio;

use App\Filament\Resources\NotasEnvio\Pages\CreateNotasEnvio;
use App\Filament\Resources\NotasEnvio\Pages\EditNotasEnvio;
use App\Filament\Resources\NotasEnvio\Pages\ListNotasEnvio;
use App\Filament\Resources\NotasEnvio\Schemas\NotasEnvioForm;
use App\Filament\Resources\NotasEnvio\Tables\NotasEnvioTable;
use App\Models\NotaEnvio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NotasEnvioResource extends Resource
{
    protected static ?string $model = NotaEnvio::class;
    protected static string|BackedEnum|null $navigationIcon = 'fas-truck-loading';

    protected static ?string $navigationLabel = 'Notas de Envío';

    protected static ?string $pluralLabel = 'Notas de Envío';

    //protected static string|null|\UnitEnum $navigationGroup = 'Notas de venta';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return NotasEnvioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotasEnvioTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotasEnvio::route('/'),
            'create' => CreateNotasEnvio::route('/create'),
            'edit' => EditNotasEnvio::route('/{record}/edit'),
        ];
    }
}

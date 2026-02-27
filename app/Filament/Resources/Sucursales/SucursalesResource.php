<?php

namespace App\Filament\Resources\Sucursales;

use App\Filament\Resources\Sucursales\Pages\CreateSucursales;
use App\Filament\Resources\Sucursales\Pages\EditSucursales;
use App\Filament\Resources\Sucursales\Pages\ListSucursales;
use App\Filament\Resources\Sucursales\Schemas\SucursalesForm;
use App\Filament\Resources\Sucursales\Tables\SucursalesTable;
use App\Models\Sucursal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SucursalesResource extends Resource
{
    protected static ?string $model = Sucursal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Sucursales';
    protected static ?string $pluralLabel = 'Sucursales';
    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SucursalesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SucursalesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSucursales::route('/'),
            'create' => CreateSucursales::route('/create'),
            'edit' => EditSucursales::route('/{record}/edit'),
        ];
    }
}

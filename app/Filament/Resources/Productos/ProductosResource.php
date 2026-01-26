<?php

namespace App\Filament\Resources\Productos;

use App\Filament\Resources\Productos\Pages\CreateProductos;
use App\Filament\Resources\Productos\Pages\EditProductos;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\Productos\Schemas\ProductosForm;
use App\Filament\Resources\Productos\Tables\ProductosTable;
use App\Models\Productos;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductosResource extends Resource
{
    protected static ?string $model = Productos::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-warehouse';

    protected static ?string $recordTitleAttribute = 'Producto';

    public static function form(Schema $schema): Schema
    {
        return ProductosForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductos::route('/'),
            //'create' => CreateProductos::route('/create'),
            //'edit' => EditProductos::route('/{record}/edit'),
        ];
    }
}

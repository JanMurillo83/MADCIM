<?php

namespace App\Filament\Resources\OrdenesCompra;

use App\Filament\Resources\OrdenesCompra\Pages\CreateOrdenesCompra;
use App\Filament\Resources\OrdenesCompra\Pages\EditOrdenesCompra;
use App\Filament\Resources\OrdenesCompra\Pages\ListOrdenesCompra;
use App\Filament\Resources\OrdenesCompra\Schemas\OrdenesCompraForm;
use App\Filament\Resources\OrdenesCompra\Tables\OrdenesCompraTable;
use App\Models\OrdenCompra;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class OrdenesCompraResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = OrdenCompra::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-signature';

    protected static ?string $navigationLabel = 'Ordenes de Compra';

    protected static ?string $pluralLabel = 'Ordenes de Compra';

    protected static string|null|\UnitEnum $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return OrdenesCompraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdenesCompraTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdenesCompra::route('/'),
            'create' => CreateOrdenesCompra::route('/create'),
            'edit' => EditOrdenesCompra::route('/{record}/edit'),
        ];
    }
}

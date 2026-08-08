<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Concerns\HasRoleResourceAccess;
use App\Filament\Resources\Inventario\Pages\CreateInventario;
use App\Filament\Resources\Inventario\Pages\EditInventario;
use App\Filament\Resources\Inventario\Pages\ListInventario;
use App\Filament\Resources\Inventario\Schemas\InventarioForm;
use App\Filament\Resources\Inventario\Tables\InventarioTable;
use App\Models\MovimientoInventario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InventarioResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = MovimientoInventario::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $pluralLabel = 'Movimientos de Inventario';

    protected static string|null|\UnitEnum $navigationGroup = 'Catalogos';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return InventarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventarioTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventario::route('/'),
            'create' => CreateInventario::route('/create'),
            'edit' => EditInventario::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Resources\Proveedores;

use App\Filament\Resources\Proveedores\Pages\ListProveedores;
use App\Filament\Resources\Proveedores\Schemas\ProveedoresForm;
use App\Filament\Resources\Proveedores\Tables\ProveedoresTable;
use App\Models\Proveedores;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class ProveedoresResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = Proveedores::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-truck';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $pluralLabel = 'Proveedores';

    protected static string|null|\UnitEnum $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return ProveedoresForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProveedoresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProveedores::route('/'),
        ];
    }
}

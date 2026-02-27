<?php

namespace App\Filament\Resources\RequisicionesCompra;

use App\Filament\Resources\RequisicionesCompra\Pages\CreateRequisicionesCompra;
use App\Filament\Resources\RequisicionesCompra\Pages\EditRequisicionesCompra;
use App\Filament\Resources\RequisicionesCompra\Pages\ListRequisicionesCompra;
use App\Filament\Resources\RequisicionesCompra\Schemas\RequisicionesCompraForm;
use App\Filament\Resources\RequisicionesCompra\Tables\RequisicionesCompraTable;
use App\Models\RequisicionCompra;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class RequisicionesCompraResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = RequisicionCompra::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-clipboard-list';

    protected static ?string $navigationLabel = 'Requisiciones';

    protected static ?string $pluralLabel = 'Requisiciones';

    protected static string|null|\UnitEnum $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return RequisicionesCompraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequisicionesCompraTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequisicionesCompra::route('/'),
            'create' => CreateRequisicionesCompra::route('/create'),
            'edit' => EditRequisicionesCompra::route('/{record}/edit'),
        ];
    }
}

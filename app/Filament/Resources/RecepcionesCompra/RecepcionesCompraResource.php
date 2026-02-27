<?php

namespace App\Filament\Resources\RecepcionesCompra;

use App\Filament\Resources\RecepcionesCompra\Pages\CreateRecepcionesCompra;
use App\Filament\Resources\RecepcionesCompra\Pages\EditRecepcionesCompra;
use App\Filament\Resources\RecepcionesCompra\Pages\ListRecepcionesCompra;
use App\Filament\Resources\RecepcionesCompra\Schemas\RecepcionesCompraForm;
use App\Filament\Resources\RecepcionesCompra\Tables\RecepcionesCompraTable;
use App\Models\RecepcionCompra;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class RecepcionesCompraResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = RecepcionCompra::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-dolly';

    protected static ?string $navigationLabel = 'Recepciones';

    protected static ?string $pluralLabel = 'Recepciones';

    protected static string|null|\UnitEnum $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return RecepcionesCompraForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecepcionesCompraTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecepcionesCompra::route('/'),
            'create' => CreateRecepcionesCompra::route('/create'),
            'edit' => EditRecepcionesCompra::route('/{record}/edit'),
        ];
    }
}

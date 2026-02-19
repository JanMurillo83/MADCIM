<?php

namespace App\Filament\Resources\NotasVentaVenta;

use App\Filament\Resources\NotasVentaVenta\Pages\CreateNotasVentaVenta;
use App\Filament\Resources\NotasVentaVenta\Pages\EditNotasVentaVenta;
use App\Filament\Resources\NotasVentaVenta\Pages\ListNotasVentaVenta;
use App\Filament\Resources\NotasVentaVenta\Schemas\NotasVentaVentaForm;
use App\Filament\Resources\NotasVentaVenta\Tables\NotasVentaVentaTable;
use App\Models\NotasVentaVenta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NotasVentaVentaResource extends Resource
{
    protected static ?string $model = NotasVentaVenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-invoice-dollar';

    protected static ?string $navigationLabel = 'Notas de venta (venta)';

    protected static ?string $pluralLabel = 'Notas de venta (venta)';
    protected static string|null|\UnitEnum $navigationGroup = 'Notas de venta';

    public static function form(Schema $schema): Schema
    {
        return NotasVentaVentaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotasVentaVentaTable::configure($table);
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
            'index' => ListNotasVentaVenta::route('/'),
            'create' => CreateNotasVentaVenta::route('/create'),
            'edit' => EditNotasVentaVenta::route('/{record}/edit'),
        ];
    }
}

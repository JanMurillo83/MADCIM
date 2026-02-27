<?php

namespace App\Filament\Resources\DevolucionesVenta;

use App\Filament\Resources\DevolucionesVenta\Pages\ListDevolucionesVenta;
use App\Filament\Resources\DevolucionesVenta\Schemas\DevolucionesVentaForm;
use App\Filament\Resources\DevolucionesVenta\Tables\DevolucionesVentaTable;
use App\Models\DevolucionesVenta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class DevolucionesVentaResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = DevolucionesVenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-invoice-dollar';

    protected static ?string $navigationLabel = 'Devoluciones (venta)';

    protected static ?string $pluralLabel = 'Devoluciones (venta)';
    protected static string|null|\UnitEnum $navigationGroup = 'Devoluciones';

    public static function form(Schema $schema): Schema
    {
        return DevolucionesVentaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevolucionesVentaTable::configure($table);
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
            'index' => ListDevolucionesVenta::route('/'),
            //'create' => CreateDevolucionesVenta::route('/create'),
            //'edit' => EditDevolucionesVenta::route('/{record}/edit'),
        ];
    }
}

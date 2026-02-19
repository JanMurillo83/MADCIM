<?php

namespace App\Filament\Resources\DevolucionesRenta;

use App\Filament\Resources\DevolucionesRenta\Pages\ListDevolucionesRenta;
use App\Filament\Resources\DevolucionesRenta\Schemas\DevolucionesRentaForm;
use App\Filament\Resources\DevolucionesRenta\Tables\DevolucionesRentaTable;
use App\Models\DevolucionesRenta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class DevolucionesRentaResource extends Resource
{
    protected static ?string $model = DevolucionesRenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-invoice-dollar';

    protected static ?string $navigationLabel = 'Devoluciones (renta)';

    protected static ?string $pluralLabel = 'Devoluciones (renta)';
    protected static string | UnitEnum | null $navigationGroup = 'Devoluciones';

    public static function form(Schema $schema): Schema
    {
        return DevolucionesRentaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevolucionesRentaTable::configure($table);
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
            'index' => ListDevolucionesRenta::route('/'),
            //'create' => CreateDevolucionesRenta::route('/create'),
            //'edit' => EditDevolucionesRenta::route('/{record}/edit'),
        ];
    }
}

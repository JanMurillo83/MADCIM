<?php

namespace App\Filament\Resources\FacturasCfdi;

use App\Filament\Resources\FacturasCfdi\Pages\ListFacturasCfdi;
use App\Filament\Resources\FacturasCfdi\Schemas\FacturasCfdiForm;
use App\Filament\Resources\FacturasCfdi\Tables\FacturasCfdiTable;
use App\Models\FacturasCfdi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class FacturasCfdiResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = FacturasCfdi::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-invoice-dollar';

    protected static ?string $navigationLabel = 'Facturas CFDI';
    protected static string|null|\UnitEnum $navigationGroup = 'Ventas';
    protected static ?string $pluralLabel = 'Facturas CFDI';

    public static function form(Schema $schema): Schema
    {
        return FacturasCfdiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FacturasCfdiTable::configure($table);
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
            'index' => ListFacturasCfdi::route('/'),
            //'create' => CreateFacturasCfdi::route('/create'),
            //'edit' => EditFacturasCfdi::route('/{record}/edit'),
        ];
    }
}

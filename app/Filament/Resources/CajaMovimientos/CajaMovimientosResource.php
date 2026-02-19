<?php

namespace App\Filament\Resources\CajaMovimientos;

use App\Filament\Resources\CajaMovimientos\Pages\CreateCajaMovimientos;
use App\Filament\Resources\CajaMovimientos\Pages\EditCajaMovimientos;
use App\Filament\Resources\CajaMovimientos\Pages\ListCajaMovimientos;
use App\Filament\Resources\CajaMovimientos\Schemas\CajaMovimientosForm;
use App\Filament\Resources\CajaMovimientos\Tables\CajaMovimientosTable;
use App\Models\CajaMovimiento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CajaMovimientosResource extends Resource
{
    protected static ?string $model = CajaMovimiento::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-cash-register';

    protected static ?string $navigationLabel = 'Movimientos de Caja';

    protected static ?string $pluralLabel = 'Movimientos de Caja';
    protected static string|null|\UnitEnum $navigationGroup = 'Caja';

    public static function form(Schema $schema): Schema
    {
        return CajaMovimientosForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CajaMovimientosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCajaMovimientos::route('/'),
            'create' => CreateCajaMovimientos::route('/create'),
            'edit' => EditCajaMovimientos::route('/{record}/edit'),
        ];
    }
}

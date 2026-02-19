<?php

namespace App\Filament\Resources\Cajas;

use App\Filament\Resources\Cajas\Pages\CreateCajas;
use App\Filament\Resources\Cajas\Pages\EditCajas;
use App\Filament\Resources\Cajas\Pages\ListCajas;
use App\Filament\Resources\Cajas\Schemas\CajasForm;
use App\Filament\Resources\Cajas\Tables\CajasTable;
use App\Models\Caja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CajasResource extends Resource
{
    protected static ?string $model = Caja::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-cash-register';

    protected static string|null|\UnitEnum $navigationGroup = 'Caja';
    protected static ?string $navigationLabel = 'Cajas';
    protected static ?string $pluralLabel = 'Cajas';

    public static function form(Schema $schema): Schema
    {
        return CajasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CajasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCajas::route('/'),
            'create' => CreateCajas::route('/create'),
            'edit' => EditCajas::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Pagos;

use App\Filament\Resources\Pagos\Pages\CreatePagos;
use App\Filament\Resources\Pagos\Pages\EditPagos;
use App\Filament\Resources\Pagos\Pages\ListPagos;
use App\Filament\Resources\Pagos\Schemas\PagosForm;
use App\Filament\Resources\Pagos\Tables\PagosTable;
use App\Models\Pagos;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PagosResource extends Resource
{
    protected static ?string $model = Pagos::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-money-bill-wave';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $pluralLabel = 'Pagos';
    protected static string|null|\UnitEnum $navigationGroup = 'Pagos';

    public static function form(Schema $schema): Schema
    {
        return PagosForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagosTable::configure($table);
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
            'index' => ListPagos::route('/'),
            'create' => CreatePagos::route('/create'),
            'edit' => EditPagos::route('/{record}/edit'),
        ];
    }
}

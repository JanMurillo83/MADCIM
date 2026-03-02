<?php

namespace App\Filament\Resources\Cotizaciones;

use App\Filament\Resources\Cotizaciones\Pages\CreateCotizaciones;
use App\Filament\Resources\Cotizaciones\Pages\EditCotizaciones;
use App\Filament\Resources\Cotizaciones\Pages\ListCotizaciones;
use App\Filament\Resources\Cotizaciones\Schemas\CotizacionesForm;
use App\Filament\Resources\Cotizaciones\Tables\CotizacionesTable;
use App\Models\Cotizaciones;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;

class CotizacionesResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = Cotizaciones::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-invoice-dollar';

    protected static ?string $navigationLabel = 'Cotizaciones';

    protected static ?string $pluralLabel = 'Cotizaciones';

    protected static string|null|\UnitEnum $navigationGroup = 'Ventas';

    public static function form(Schema $schema): Schema
    {
        return CotizacionesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CotizacionesTable::configure($table);
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
            'index' => ListCotizaciones::route('/'),
            'create' => CreateCotizaciones::route('/create'),
            'edit' => EditCotizaciones::route('/{record}/edit'),
        ];
    }
}

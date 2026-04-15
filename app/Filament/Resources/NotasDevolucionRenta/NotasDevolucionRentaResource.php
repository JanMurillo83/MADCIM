<?php

namespace App\Filament\Resources\NotasDevolucionRenta;

use App\Filament\Concerns\HasRoleResourceAccess;
use App\Filament\Resources\NotasDevolucionRenta\Pages\CreateNotasDevolucionRenta;
use App\Filament\Resources\NotasDevolucionRenta\Pages\EditNotasDevolucionRenta;
use App\Filament\Resources\NotasDevolucionRenta\Pages\ListNotasDevolucionRenta;
use App\Filament\Resources\NotasDevolucionRenta\Schemas\NotasDevolucionRentaForm;
use App\Filament\Resources\NotasDevolucionRenta\Tables\NotasDevolucionRentaTable;
use App\Models\NotaDevolucionRenta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NotasDevolucionRentaResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = NotaDevolucionRenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Notas Devolucion Renta';

    protected static ?string $pluralLabel = 'Notas Devolucion Renta';

    protected static string|null|\UnitEnum $navigationGroup = 'Devoluciones';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return NotasDevolucionRentaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotasDevolucionRentaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotasDevolucionRenta::route('/'),
            'create' => CreateNotasDevolucionRenta::route('/create'),
            'edit' => EditNotasDevolucionRenta::route('/{record}/edit'),
        ];
    }
}

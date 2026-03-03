<?php

namespace App\Filament\Resources\NotasVentaRenta;

use App\Filament\Resources\NotasVentaRenta\Pages\CreateNotasVentaRenta;
use App\Filament\Resources\NotasVentaRenta\Pages\EditNotasVentaRenta;
use App\Filament\Resources\NotasVentaRenta\Pages\ListNotasVentaRenta;
use App\Filament\Resources\NotasVentaRenta\Schemas\NotasVentaRentaForm;
use App\Filament\Resources\NotasVentaRenta\Tables\NotasVentaRentaTable;
use App\Models\NotasVentaRenta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Concerns\HasRoleResourceAccess;
use Illuminate\Database\Eloquent\Model;

class NotasVentaRentaResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = NotasVentaRenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-file-invoice-dollar';

    protected static ?string $navigationLabel = 'Notas de Renta';

    protected static ?string $pluralLabel = 'Notas de Renta';
    protected static string|null|\UnitEnum $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 4;
    public static function form(Schema $schema): Schema
    {
        return NotasVentaRentaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotasVentaRentaTable::configure($table);
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
            'index' => ListNotasVentaRenta::route('/'),
            'create' => CreateNotasVentaRenta::route('/create'),
            'edit' => EditNotasVentaRenta::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && $record->estatus !== 'Cancelada';
    }
}

<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Pages\CreateHistorialSalarial;
use App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Pages\EditHistorialSalarial;
use App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Pages\ListHistorialSalarial;
use App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Schemas\HistorialSalarialForm;
use App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Tables\HistorialSalarialTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\HistorialSalarial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HistorialSalarialResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = HistorialSalarial::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Historial salarial';
    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';
    protected static ?string $slug = 'historial-salarial';
    protected static ?string $modelLabel = 'registro salarial';
    protected static ?string $pluralModelLabel = 'historial salarial';

    public static function form(Schema $schema): Schema { return HistorialSalarialForm::configure($schema); }
    public static function table(Table $table): Table { return HistorialSalarialTable::configure($table); }
    public static function getPages(): array
    {
        return [
            'index' => ListHistorialSalarial::route('/'),
            'create' => CreateHistorialSalarial::route('/create'),
            'edit' => EditHistorialSalarial::route('/{record}/edit'),
        ];
    }
}

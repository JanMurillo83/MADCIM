<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Incidencias;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Pages\CreateIncidencia;
use App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Pages\EditIncidencia;
use App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Pages\ListIncidencias;
use App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Schemas\IncidenciaForm;
use App\Filament\Clusters\RecursosHumanos\Resources\Incidencias\Tables\IncidenciasTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\Incidencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class IncidenciaResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = Incidencia::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static ?string $recordTitleAttribute = 'motivo';

    protected static ?string $navigationLabel = 'Incidencias';

    protected static string|null|\UnitEnum $navigationGroup = 'Incidencias';

    protected static ?string $modelLabel = 'incidencia';

    protected static ?string $pluralModelLabel = 'incidencias';

    public static function form(Schema $schema): Schema
    {
        return IncidenciaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncidenciasTable::configure($table);
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
            'index' => ListIncidencias::route('/'),
            'create' => CreateIncidencia::route('/create'),
            'edit' => EditIncidencia::route('/{record}/edit'),
        ];
    }
}

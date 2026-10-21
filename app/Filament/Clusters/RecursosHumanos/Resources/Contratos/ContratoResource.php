<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\Contratos;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Pages\CreateContrato;
use App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Pages\EditContrato;
use App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Pages\ListContratos;
use App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Schemas\ContratoForm;
use App\Filament\Clusters\RecursosHumanos\Resources\Contratos\Tables\ContratosTable;
use App\Models\Contrato;
use App\Filament\Concerns\HasRoleResourceAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContratoResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = Contrato::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static ?string $recordTitleAttribute = 'tipo';

    protected static ?string $navigationLabel = 'Contratos';

    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'contrato';

    protected static ?string $pluralModelLabel = 'contratos';

    public static function form(Schema $schema): Schema
    {
        return ContratoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContratosTable::configure($table);
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
            'index' => ListContratos::route('/'),
            'create' => CreateContrato::route('/create'),
            'edit' => EditContrato::route('/{record}/edit'),
        ];
    }
}

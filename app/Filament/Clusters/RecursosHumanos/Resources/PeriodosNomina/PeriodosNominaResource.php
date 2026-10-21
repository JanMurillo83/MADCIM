<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Pages\{CreatePeriodoNomina,EditPeriodoNomina,ListPeriodosNomina};
use App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Schemas\PeriodoNominaForm;
use App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Tables\PeriodosNominaTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\PeriodoNomina;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;

class PeriodosNominaResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = PeriodoNomina::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static ?string $navigationLabel = 'Nómina semanal';
    protected static string|null|\UnitEnum $navigationGroup = 'Incidencias';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $slug = 'nomina-semanal';
    protected static ?string $modelLabel = 'periodo de nómina';
    protected static ?string $pluralModelLabel = 'periodos de nómina';
    public static function form(Schema $schema): Schema { return PeriodoNominaForm::configure($schema); }
    public static function table(Table $table): Table { return PeriodosNominaTable::configure($table); }
    public static function getPages(): array { return ['index' => ListPeriodosNomina::route('/'), 'create' => CreatePeriodoNomina::route('/create'), 'edit' => EditPeriodoNomina::route('/{record}/edit')]; }
}

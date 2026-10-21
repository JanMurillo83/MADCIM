<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\ConceptosNomina;
use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\ConceptosNomina\Pages\{CreateConceptoNomina,EditConceptoNomina,ListConceptosNomina};
use App\Filament\Clusters\RecursosHumanos\Resources\ConceptosNomina\Schemas\ConceptoNominaForm;
use App\Filament\Clusters\RecursosHumanos\Resources\ConceptosNomina\Tables\ConceptosNominaTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\ConceptoNomina;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
class ConceptosNominaResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = ConceptoNomina::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static ?string $navigationLabel = 'Bonos y descuentos';
    protected static string|null|\UnitEnum $navigationGroup = 'Incidencias';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $slug = 'conceptos-nomina';
    protected static ?string $modelLabel = 'concepto de nómina';
    protected static ?string $pluralModelLabel = 'bonos y descuentos';
    public static function form(Schema $schema): Schema { return ConceptoNominaForm::configure($schema); }
    public static function table(Table $table): Table { return ConceptosNominaTable::configure($table); }
    public static function getPages(): array { return ['index' => ListConceptosNomina::route('/'), 'create' => CreateConceptoNomina::route('/create'), 'edit' => EditConceptoNomina::route('/{record}/edit')]; }
}

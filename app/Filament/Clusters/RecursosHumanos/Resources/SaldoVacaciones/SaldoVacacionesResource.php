<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones;
use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones\Pages\{CreateSaldoVacacion,EditSaldoVacacion,ListSaldoVacaciones};
use App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones\Schemas\SaldoVacacionForm;
use App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones\Tables\SaldoVacacionesTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\SaldoVacacion;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
class SaldoVacacionesResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = SaldoVacacion::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static ?string $navigationLabel = 'Saldos de vacaciones';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';
    protected static ?string $modelLabel = 'saldo de vacaciones';
    protected static ?string $pluralModelLabel = 'saldos de vacaciones';
    public static function form(Schema $schema): Schema { return SaldoVacacionForm::configure($schema); }
    public static function table(Table $table): Table { return SaldoVacacionesTable::configure($table); }
    public static function getPages(): array { return ['index' => ListSaldoVacaciones::route('/'), 'create' => CreateSaldoVacacion::route('/create'), 'edit' => EditSaldoVacacion::route('/{record}/edit')]; }
}

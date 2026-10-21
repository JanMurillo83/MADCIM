<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones;
use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\Pages\{CreateSolicitudVacacion,EditSolicitudVacacion,ListSolicitudesVacaciones};
use App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\Schemas\SolicitudVacacionForm;
use App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\Tables\SolicitudesVacacionesTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\SolicitudVacacion;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
class SolicitudesVacacionesResource extends Resource
{
    use HasRoleResourceAccess;
    protected static ?string $model = SolicitudVacacion::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static ?string $navigationLabel = 'Solicitudes de vacaciones';
    protected static ?string $modelLabel = 'solicitud de vacaciones';
    protected static ?string $pluralModelLabel = 'solicitudes de vacaciones';
    public static function form(Schema $schema): Schema { return SolicitudVacacionForm::configure($schema); }
    public static function table(Table $table): Table { return SolicitudesVacacionesTable::configure($table); }
    public static function getPages(): array { return ['index' => ListSolicitudesVacaciones::route('/'), 'create' => CreateSolicitudVacacion::route('/create'), 'edit' => EditSolicitudVacacion::route('/{record}/edit')]; }
}

<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Pages\CreateMovimientoLaboral;
use App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Pages\EditMovimientoLaboral;
use App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Pages\ListMovimientosLaborales;
use App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Schemas\MovimientoLaboralForm;
use App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Tables\MovimientosLaboralesTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\MovimientoLaboral;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MovimientosLaboralesResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = MovimientoLaboral::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Movimientos laborales';
    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';
    protected static ?string $modelLabel = 'movimiento laboral';
    protected static ?string $pluralModelLabel = 'movimientos laborales';

    public static function form(Schema $schema): Schema { return MovimientoLaboralForm::configure($schema); }
    public static function table(Table $table): Table { return MovimientosLaboralesTable::configure($table); }
    public static function getPages(): array
    {
        return [
            'index' => ListMovimientosLaborales::route('/'),
            'create' => CreateMovimientoLaboral::route('/create'),
            'edit' => EditMovimientoLaboral::route('/{record}/edit'),
        ];
    }
}

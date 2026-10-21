<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\Pages\CreateVinculacionBiometrica;
use App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\Pages\EditVinculacionBiometrica;
use App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\Pages\ListVinculacionesBiometricas;
use App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\Schemas\VinculacionBiometricaForm;
use App\Filament\Clusters\RecursosHumanos\Resources\VinculacionesBiometricas\Tables\VinculacionesBiometricasTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\EmpleadoDispositivoId;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VinculacionBiometricaResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = EmpleadoDispositivoId::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static ?string $navigationLabel = 'Vinculaciones biométricas';
    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';
    protected static ?string $slug = 'vinculaciones-biometricas';
    protected static ?string $modelLabel = 'vinculación biométrica';
    protected static ?string $pluralModelLabel = 'vinculaciones biométricas';

    public static function form(Schema $schema): Schema
    {
        return VinculacionBiometricaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VinculacionesBiometricasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVinculacionesBiometricas::route('/'),
            'create' => CreateVinculacionBiometrica::route('/create'),
            'edit' => EditVinculacionBiometrica::route('/{record}/edit'),
        ];
    }
}

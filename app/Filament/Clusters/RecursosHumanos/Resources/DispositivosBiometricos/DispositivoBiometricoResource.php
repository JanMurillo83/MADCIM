<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\Pages\CreateDispositivoBiometrico;
use App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\Pages\EditDispositivoBiometrico;
use App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\Pages\ListDispositivosBiometricos;
use App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\Schemas\DispositivoBiometricoForm;
use App\Filament\Clusters\RecursosHumanos\Resources\DispositivosBiometricos\Tables\DispositivosBiometricosTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\DispositivoBiometrico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DispositivoBiometricoResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = DispositivoBiometrico::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static ?string $navigationLabel = 'Dispositivos biométricos';
    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $slug = 'dispositivos-biometricos';
    protected static ?string $modelLabel = 'dispositivo biométrico';
    protected static ?string $pluralModelLabel = 'dispositivos biométricos';

    public static function form(Schema $schema): Schema
    {
        return DispositivoBiometricoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DispositivosBiometricosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDispositivosBiometricos::route('/'),
            'create' => CreateDispositivoBiometrico::route('/create'),
            'edit' => EditDispositivoBiometrico::route('/{record}/edit'),
        ];
    }
}

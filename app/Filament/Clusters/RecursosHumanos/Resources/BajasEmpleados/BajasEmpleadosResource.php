<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Pages\CreateBajaEmpleado;
use App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Pages\ListBajasEmpleados;
use App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Schemas\BajaEmpleadoForm;
use App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Tables\BajasEmpleadosTable;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Models\BajaEmpleado;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BajasEmpleadosResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = BajaEmpleado::class;
    protected static ?string $cluster = RecursosHumanosCluster::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-minus';
    protected static ?string $navigationLabel = 'Bajas y reingresos';
    protected static string|null|\UnitEnum $navigationGroup = 'Catálogos';
    protected static ?string $modelLabel = 'baja de empleado';
    protected static ?string $pluralModelLabel = 'bajas de empleados';

    public static function form(Schema $schema): Schema
    {
        return BajaEmpleadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BajasEmpleadosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBajasEmpleados::route('/'),
            'create' => CreateBajaEmpleado::route('/create'),
        ];
    }
}

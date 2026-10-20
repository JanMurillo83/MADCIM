<?php

namespace App\Filament\Resources\Empleados;

use App\Filament\Clusters\RecursosHumanos\RecursosHumanosCluster;
use App\Filament\Concerns\HasRoleResourceAccess;
use App\Filament\Resources\Empleados\Pages\CreateEmpleado;
use App\Filament\Resources\Empleados\Pages\EditEmpleado;
use App\Filament\Resources\Empleados\Pages\ListEmpleados;
use App\Filament\Resources\Empleados\Schemas\EmpleadoForm;
use App\Filament\Resources\Empleados\Tables\EmpleadosTable;
use App\Models\Empleado;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EmpleadoResource extends Resource
{
    use HasRoleResourceAccess;

    protected static ?string $model = Empleado::class;

    protected static ?string $cluster = RecursosHumanosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Empleados';

    protected static ?string $modelLabel = 'empleado';

    protected static ?string $pluralModelLabel = 'empleados';

    public static function form(Schema $schema): Schema
    {
        return EmpleadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmpleadosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmpleados::route('/'),
            'create' => CreateEmpleado::route('/create'),
            'edit' => EditEmpleado::route('/{record}/edit'),
        ];
    }
}

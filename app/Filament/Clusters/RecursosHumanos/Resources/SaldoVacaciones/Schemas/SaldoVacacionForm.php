<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones\Schemas;
use App\Models\Empleado;
use Filament\Forms\Components\{Select,TextInput,Textarea};
use Filament\Schemas\Schema;
class SaldoVacacionForm
{
    public static function configure(Schema $schema): Schema { return $schema->components([
        Select::make('empleado_id')->label('Empleado')->options(fn (): array => Empleado::where('estatus','activo')->orderBy('apellido_paterno')->get()->mapWithKeys(fn (Empleado $e): array => [$e->id => "{$e->numero_empleado} - {$e->nombre_completo}"])->all())->required()->searchable()->preload(),
        TextInput::make('anio')->label('Año')->numeric()->minValue(2020)->maxValue(2100)->required(),
        TextInput::make('dias_asignados')->label('Días asignados')->numeric()->minValue(0)->required(),
        TextInput::make('dias_usados')->label('Días usados')->numeric()->minValue(0)->default(0)->required(),
        Textarea::make('observaciones')->rows(3)->columnSpanFull(),
    ]); }
}

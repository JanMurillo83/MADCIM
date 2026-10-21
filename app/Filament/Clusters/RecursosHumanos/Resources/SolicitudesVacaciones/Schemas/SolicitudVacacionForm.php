<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\Schemas;
use App\Models\{Empleado,SaldoVacacion};
use Filament\Forms\Components\{DatePicker,Select,Textarea,TextInput};
use Filament\Schemas\Schema;
class SolicitudVacacionForm
{
    public static function configure(Schema $schema): Schema { return $schema->components([
        Select::make('empleado_id')->label('Empleado')->options(fn (): array => Empleado::where('estatus','activo')->orderBy('apellido_paterno')->get()->mapWithKeys(fn (Empleado $e): array => [$e->id => "{$e->numero_empleado} - {$e->nombre_completo}"])->all())->required()->searchable()->preload(),
        Select::make('saldo_vacaciones_id')->label('Saldo')->options(fn (): array => SaldoVacacion::query()->with('empleado')->where('anio', now()->year)->get()->mapWithKeys(fn (SaldoVacacion $s): array => [$s->id => "{$s->empleado?->nombre_completo} ({$s->dias_disponibles} días disponibles)"])->all())->required()->searchable()->preload(),
        DatePicker::make('fecha_inicio')->label('Inicio')->required(),
        DatePicker::make('fecha_fin')->label('Fin')->required(),
        TextInput::make('dias')->label('Días solicitados')->numeric()->minValue(0.5)->required(),
        Textarea::make('motivo')->rows(3)->columnSpanFull(),
    ]); }
}

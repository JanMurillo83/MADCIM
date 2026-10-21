<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\ConceptosNomina\Schemas;
use App\Models\{Empleado,PeriodoNomina};
use Filament\Forms\Components\{Select,TextInput,Textarea};
use Filament\Schemas\Schema;
class ConceptoNominaForm
{
    public static function configure(Schema $schema): Schema { return $schema->components([
        Select::make('periodo_nomina_id')->label('Periodo')->options(fn (): array => PeriodoNomina::where('estatus','abierto')->orderByDesc('fecha_inicio')->get()->mapWithKeys(fn (PeriodoNomina $p): array => [$p->id => "{$p->fecha_inicio->format('d/m/Y')} - {$p->fecha_fin->format('d/m/Y')}"])->all())->required()->searchable()->preload(),
        Select::make('empleado_id')->label('Empleado')->options(fn (): array => Empleado::where('estatus','activo')->orderBy('apellido_paterno')->get()->mapWithKeys(fn (Empleado $e): array => [$e->id => "{$e->numero_empleado} - {$e->nombre_completo}"])->all())->required()->searchable()->preload(),
        Select::make('tipo')->label('Tipo')->options([
            'bono' => 'Bono',
            'aguinaldo' => 'Aguinaldo operativo',
            'apoyo' => 'Pago de apoyo',
            'dia_festivo' => 'Día festivo laborado',
            'dia_descanso' => 'Día de descanso laborado',
            'descuento' => 'Descuento',
            'anticipo' => 'Anticipo',
            'prestamo' => 'Préstamo',
        ])->required(),
        TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.01)->default(1)->required(),
        TextInput::make('concepto')->label('Concepto')->required()->maxLength(120),
        TextInput::make('importe')->label('Importe')->numeric()->minValue(0.01)->required(),
        Textarea::make('observaciones')->rows(3)->columnSpanFull(),
    ]); }
}

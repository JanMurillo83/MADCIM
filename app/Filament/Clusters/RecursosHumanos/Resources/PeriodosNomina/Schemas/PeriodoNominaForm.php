<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Schemas;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
class PeriodoNominaForm
{
    public static function configure(Schema $schema): Schema { return $schema->components([
        DatePicker::make('fecha_inicio')->label('Inicio')->required(),
        DatePicker::make('fecha_fin')->label('Fin')->required()->afterOrEqual('fecha_inicio'),
    ]); }
}

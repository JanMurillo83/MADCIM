<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\MovimientosLaboralesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListMovimientosLaborales extends ListRecords
{
    protected static string $resource = MovimientosLaboralesResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}

<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\MovimientosLaborales\MovimientosLaboralesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditMovimientoLaboral extends EditRecord
{
    protected static string $resource = MovimientosLaboralesResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}

<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\HistorialSalarialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditHistorialSalarial extends EditRecord
{
    protected static string $resource = HistorialSalarialResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}

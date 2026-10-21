<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\HistorialSalarial\HistorialSalarialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListHistorialSalarial extends ListRecords
{
    protected static string $resource = HistorialSalarialResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}

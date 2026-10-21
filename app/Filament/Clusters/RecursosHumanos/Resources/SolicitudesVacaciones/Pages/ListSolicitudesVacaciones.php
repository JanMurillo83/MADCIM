<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\SolicitudesVacacionesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListSolicitudesVacaciones extends ListRecords { protected static string $resource = SolicitudesVacacionesResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }

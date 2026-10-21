<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\PeriodosNomina\PeriodosNominaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListPeriodosNomina extends ListRecords { protected static string $resource = PeriodosNominaResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }

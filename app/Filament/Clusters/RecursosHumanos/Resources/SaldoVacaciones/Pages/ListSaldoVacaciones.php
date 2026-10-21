<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\SaldoVacaciones\SaldoVacacionesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListSaldoVacaciones extends ListRecords { protected static string $resource = SaldoVacacionesResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }

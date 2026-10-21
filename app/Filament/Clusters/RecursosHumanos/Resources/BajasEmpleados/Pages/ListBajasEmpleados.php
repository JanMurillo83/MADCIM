<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\BajasEmpleadosResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBajasEmpleados extends ListRecords
{
    protected static string $resource = BajasEmpleadosResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

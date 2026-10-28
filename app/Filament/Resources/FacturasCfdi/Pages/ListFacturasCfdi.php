<?php

namespace App\Filament\Resources\FacturasCfdi\Pages;

use App\Filament\Concerns\NormalizesMountedActions;
use App\Filament\Resources\FacturasCfdi\FacturasCfdiResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListFacturasCfdi extends ListRecords
{
    use NormalizesMountedActions;

    protected static string $resource = FacturasCfdiResource::class;

    public function hydrate(): void
    {
        $this->normalizeMountedActions();
    }

    protected function afterActionCalled(Action $action): void
    {
        parent::afterActionCalled($action);
        $this->normalizeMountedActions();
    }
}

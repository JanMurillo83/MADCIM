<?php

namespace App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\Pages;

use App\Filament\Clusters\RecursosHumanos\Resources\BajasEmpleados\BajasEmpleadosResource;
use App\Services\GestionarBajaEmpleadoService;
use Filament\Resources\Pages\CreateRecord;

class CreateBajaEmpleado extends CreateRecord
{
    protected static string $resource = BajasEmpleadosResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(GestionarBajaEmpleadoService::class)->registrar(
            new \App\Models\BajaEmpleado($data),
        );
    }
}

<?php
namespace App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\Pages;
use App\Filament\Clusters\RecursosHumanos\Resources\SolicitudesVacaciones\SolicitudesVacacionesResource;
use Filament\Resources\Pages\CreateRecord;
class CreateSolicitudVacacion extends CreateRecord { protected static string $resource = SolicitudesVacacionesResource::class; protected function mutateFormDataBeforeCreate(array $data): array { $data['estatus'] = 'pendiente'; $data['solicitada_por_id'] = auth()->id(); return $data; } }

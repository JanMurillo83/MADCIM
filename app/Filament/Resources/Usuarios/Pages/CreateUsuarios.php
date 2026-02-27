<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuariosResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUsuarios extends CreateRecord
{
    protected static string $resource = UsuariosResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (($data['role'] ?? null) === 'Administrador') {
            $data['sucursal_id'] = null;
        }

        return $data;
    }
}

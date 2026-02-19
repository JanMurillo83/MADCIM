<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagosResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePagos extends CreateRecord
{
    protected static string $resource = PagosResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}

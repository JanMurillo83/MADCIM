<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPagos extends EditRecord
{
    protected static string $resource = PagosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

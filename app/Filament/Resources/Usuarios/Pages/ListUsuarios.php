<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuariosResource;
use Filament\Resources\Pages\ListRecords;

class ListUsuarios extends ListRecords
{
    protected static string $resource = UsuariosResource::class;
}

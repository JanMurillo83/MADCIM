<?php

namespace App\Filament\Pages\Reportes;

use App\Filament\Concerns\HasInventarioConsolidado;
use App\Filament\Concerns\HasRolePageAccess;
use BackedEnum;
use Filament\Pages\Page;

class ExistenciaPorSucursal extends Page
{
    use HasInventarioConsolidado;
    use HasRolePageAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?string $title = 'Inventario';
    protected static string|null|\UnitEnum $navigationGroup = 'Catalogos';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.reportes.inventario-consolidado';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return null;
    }
}

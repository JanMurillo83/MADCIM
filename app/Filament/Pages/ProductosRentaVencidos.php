<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Pages\Concerns\ConsultaProductosRenta;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Page;

class ProductosRentaVencidos extends Page
{
    use HasRolePageAccess;
    use ConsultaProductosRenta;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Productos vencidos';
    protected static ?string $title = 'Productos rentados vencidos';
    protected static string|null|\UnitEnum $navigationGroup = 'Consultas';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.productos-renta-vencimiento';

    protected function aplicarFiltroFecha(Builder $query): void
    {
        $query->whereDate('fecha_vencimiento', '<', now()->toDateString());
    }

    public function tituloConsulta(): string
    {
        return 'Productos rentados vencidos';
    }

    public function descripcionConsulta(): string
    {
        return 'Productos cuya fecha de vencimiento ya pasó y todavía tienen cantidades pendientes de devolución.';
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Pages\Concerns\ConsultaProductosRenta;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Page;

class ProductosRentaPorVencer extends Page
{
    use HasRolePageAccess;
    use ConsultaProductosRenta;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Productos por vencer (7 días)';
    protected static ?string $title = 'Productos por vencer en 7 días';
    protected static string|null|\UnitEnum $navigationGroup = 'Consultas';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.productos-renta-vencimiento';

    protected function aplicarFiltroFecha(Builder $query): void
    {
        $hoy = now()->toDateString();

        $query->whereBetween('fecha_vencimiento', [
            $hoy,
            now()->addDays(7)->toDateString(),
        ]);
    }

    public function tituloConsulta(): string
    {
        return 'Productos por vencer en los próximos 7 días';
    }

    public function descripcionConsulta(): string
    {
        return 'Productos rentados con fecha de vencimiento próxima y cantidades aún no devueltas.';
    }
}

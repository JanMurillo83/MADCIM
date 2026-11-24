<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Pages\Concerns\ConsultaProductosRenta;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

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

    #[Url]
    public ?string $linea = null;

    protected function aplicarFiltroFecha(Builder $query): void
    {
        $query->whereDate('fecha_vencimiento', '<', now()->toDateString());

        if (strtoupper(trim((string) $this->linea)) === 'MADERA') {
            $query->whereHas('producto', fn (Builder $productoQuery) => $productoQuery
                ->whereRaw('UPPER(TRIM(linea)) = ?', ['MADERA']));
        }
    }

    public function tituloConsulta(): string
    {
        return strtoupper(trim((string) $this->linea)) === 'MADERA'
            ? 'Madera vencida por cliente'
            : 'Productos rentados vencidos';
    }

    public function descripcionConsulta(): string
    {
        return strtoupper(trim((string) $this->linea)) === 'MADERA'
            ? 'Detalle de las partidas de madera vencidas que cada cliente todavía tiene pendientes por devolver.'
            : 'Productos cuya fecha de vencimiento ya pasó y todavía tienen cantidades pendientes de devolución.';
    }

    #[Computed]
    public function clientesConPendiente(): int
    {
        return $this->productos()
            ->pluck('cliente_id')
            ->filter()
            ->unique()
            ->count();
    }
}

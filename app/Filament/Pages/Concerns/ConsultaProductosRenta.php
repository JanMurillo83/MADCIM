<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Clientes;
use App\Models\RegistroRenta;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

trait ConsultaProductosRenta
{
    public ?int $cliente_id = null;
    public ?int $sucursal_id = null;

    abstract protected function aplicarFiltroFecha(Builder $query): void;

    abstract public function tituloConsulta(): string;

    abstract public function descripcionConsulta(): string;

    public function getClientesProperty(): Collection
    {
        return collect(Clientes::query()
            ->orderBy('nombre')
            ->pluck('nombre', 'id'));
    }

    public function getSucursalesProperty(): Collection
    {
        return collect(Sucursal::query()
            ->orderBy('nombre')
            ->pluck('nombre', 'id'));
    }

    #[Computed]
    public function productos(): Collection
    {
        $query = RegistroRenta::query()
            ->with(['cliente', 'producto', 'notaVentaRenta'])
            ->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')
            ->whereHas('notaVentaRenta', function (Builder $query): void {
                $query->whereIn('estatus', ['Activa', 'Pagada']);
            })
            ->when($this->cliente_id, fn (Builder $query) => $query->where('cliente_id', $this->cliente_id))
            ->when($this->sucursal_id, fn (Builder $query) => $query->whereHas(
                'notaVentaRenta',
                fn (Builder $notaQuery) => $notaQuery->where('sucursal_id', $this->sucursal_id),
            ));

        $this->aplicarFiltroFecha($query);

        return collect($query
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->get());
    }

    #[Computed]
    public function totalPendiente(): float
    {
        return (float) $this->productos()->sum(
            fn (RegistroRenta $registro): float => max(
                0,
                (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0),
            ),
        );
    }

    public function cantidadPendiente(RegistroRenta $registro): float
    {
        return max(0, (float) $registro->cantidad - (float) ($registro->cantidad_devuelta ?? 0));
    }
}

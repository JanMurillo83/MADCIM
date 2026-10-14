<?php

namespace App\Filament\Pages\Reportes;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\Productos;
use App\Models\Sucursal;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ExistenciaPorSucursal extends Page
{
    use HasRolePageAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Existencia por Sucursal';
    protected static ?string $title = 'Existencia por Sucursal';
    protected static string|null|\UnitEnum $navigationGroup = 'Catalogos';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.reportes.existencia-por-sucursal';

    public ?int $sucursal_id = null;
    public string $busqueda = '';

    public function mount(): void
    {
        $this->sucursal_id = auth()->user()?->isAdmin()
            ? Sucursal::query()->orderBy('nombre')->value('id')
            : auth()->user()?->sucursal_id;
    }

    public function getSucursalesProperty(): Collection
    {
        return Sucursal::query()
            ->when(! auth()->user()?->isAdmin(), fn ($query) => $query->whereKey(auth()->user()?->sucursal_id))
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }

    #[Computed]
    public function items(): Collection
    {
        return Productos::query()
            ->with(['existenciasSucursal' => fn ($query) => $query->where('sucursal_id', $this->sucursal_id ?: 0)])
            ->when(trim($this->busqueda) !== '', function ($query): void {
                $busqueda = trim($this->busqueda);
                $query->where(function ($query) use ($busqueda): void {
                    $query
                        ->where('clave', 'like', "%{$busqueda}%")
                        ->orWhere('descripcion', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('descripcion')
            ->get()
            ->map(fn (Productos $producto): array => [
                'clave' => $producto->clave,
                'descripcion' => $producto->descripcion,
                'linea' => $producto->linea,
                'grupo' => $producto->grupo,
                'existencia' => (float) ($producto->existenciasSucursal->first()?->existencia ?? 0),
                'costo' => (float) $producto->costo,
                'valor' => (float) ($producto->existenciasSucursal->first()?->existencia ?? 0) * (float) $producto->costo,
            ])
            ->values();
    }

    #[Computed]
    public function totalExistencia(): float
    {
        return (float) $this->items->sum('existencia');
    }

    #[Computed]
    public function totalValor(): float
    {
        return (float) $this->items->sum('valor');
    }
}

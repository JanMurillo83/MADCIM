<?php

namespace App\Filament\Pages\Reportes;

use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Concerns\RestringeSucursal;
use App\Models\Clientes;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class RentasPorLinea extends Page
{
    use HasRolePageAccess;
    use RestringeSucursal;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Rentas por Linea';
    protected static ?string $title = 'Rentas por Linea';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.reportes.rentas-por-linea';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $cliente_id = null;
    public ?int $producto_id = null;
    public ?string $linea = null;
    public ?string $grupo = null;
    public ?int $sucursal_id = null;
    public ?int $usuario_id = null;

    public function mount(): void
    {
        $this->inicializarSucursalUsuario();
        $this->fecha_inicio = now()->startOfMonth()->toDateString();
        $this->fecha_fin = now()->toDateString();
    }

    public function getClientesProperty(): Collection
    {
        return Clientes::orderBy('nombre')->get()->mapWithKeys(fn ($cliente) => [$cliente->id => $cliente->nombre]);
    }

    public function getProductosProperty(): Collection
    {
        return Productos::orderBy('descripcion')->get()->mapWithKeys(fn ($producto) => [
            $producto->id => $producto->clave . ' - ' . $producto->descripcion,
        ]);
    }

    public function getLineasProperty(): Collection
    {
        return Productos::query()->whereNotNull('linea')->distinct()->orderBy('linea')->pluck('linea', 'linea');
    }

    public function getGruposProperty(): Collection
    {
        return Productos::query()->whereNotNull('grupo')->distinct()->orderBy('grupo')->pluck('grupo', 'grupo');
    }

    public function getSucursalesProperty(): Collection
    {
        return $this->sucursalesDisponibles();
    }

    public function getUsuariosProperty(): Collection
    {
        return User::orderBy('name')->get()->mapWithKeys(fn ($user) => [$user->id => $user->name]);
    }

    #[Computed]
    public function rentas(): Collection
    {
        return RegistroRenta::query()
            ->with('producto')
            ->whereHas('notaVentaRenta', function ($query): void {
                $query->where('estatus', '!=', 'Cancelada')
                    ->when($this->sucursalEfectiva(), fn ($q) => $q->where('sucursal_id', $this->sucursalEfectiva()))
                    ->when($this->usuario_id, fn ($q) => $q->where('user_id', $this->usuario_id));
            })
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->producto_id, fn ($q) => $q->where('producto_id', $this->producto_id))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('fecha_renta', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('fecha_renta', '<=', $this->fecha_fin))
            ->get()
            ->groupBy('producto_id')
            ->map(function (Collection $items): array {
                $producto = $items->first()?->producto;

                return [
                    'linea' => $producto?->linea ?? 'Sin linea',
                    'grupo' => $producto?->grupo ?? 'Sin grupo',
                    'clave' => $producto?->clave ?? 'N/A',
                    'descripcion' => $producto?->descripcion ?? 'Producto eliminado',
                    'cantidad' => (float) $items->sum('cantidad'),
                    'total' => (float) $items->sum('importe_renta'),
                ];
            })
            ->filter(fn (array $row): bool => (! $this->linea || $row['linea'] === $this->linea)
                && (! $this->grupo || $row['grupo'] === $this->grupo))
            ->sortBy([
                ['linea', 'asc'],
                ['grupo', 'asc'],
                ['total', 'desc'],
            ])
            ->values();
    }

    #[Computed]
    public function totalCantidad(): float
    {
        return (float) $this->rentas->sum('cantidad');
    }

    #[Computed]
    public function totalTotal(): float
    {
        return (float) $this->rentas->sum('total');
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('exports.reportes.rentas-por-linea-pdf', [
            'rentas' => $this->rentas,
            'totals' => ['cantidad' => $this->totalCantidad, 'total' => $this->totalTotal],
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, 'rentas_por_linea_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [['Linea', 'Grupo', 'Producto', 'Cantidad', 'Importe renta']];
        foreach ($this->rentas as $row) {
            $rows[] = [
                $row['linea'],
                $row['grupo'],
                $row['clave'] . ' - ' . $row['descripcion'],
                number_format($row['cantidad'], 2, '.', ''),
                number_format($row['total'], 2, '.', ''),
            ];
        }
        $rows[] = [];
        $rows[] = ['TOTALES', '', '', number_format($this->totalCantidad, 2, '.', ''), number_format($this->totalTotal, 2, '.', '')];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'rentas_por_linea_' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

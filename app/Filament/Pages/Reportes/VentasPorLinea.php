<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Clientes;
use App\Models\Productos;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use App\Filament\Concerns\HasRolePageAccess;
use App\Filament\Concerns\RestringeSucursal;

class VentasPorLinea extends Page
{
    use HasRolePageAccess;
    use RestringeSucursal;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Ventas por Linea';
    protected static ?string $title = 'Ventas por Linea';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.reportes.ventas-por-linea';

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $cliente_id = null;
    public ?string $estatus = null;
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
        return Clientes::orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->nombre]);
    }

    public function getLineasProperty(): Collection
    {
        return Productos::select('linea')
            ->whereNotNull('linea')
            ->distinct()
            ->orderBy('linea')
            ->pluck('linea', 'linea');
    }

    public function getGruposProperty(): Collection
    {
        return Productos::select('grupo')
            ->whereNotNull('grupo')
            ->distinct()
            ->orderBy('grupo')
            ->pluck('grupo', 'grupo');
    }

    public function getEstatusesProperty(): array
    {
        return [
            'Activa' => 'Activa',
            'Pagada' => 'Pagada',
            'Cancelada' => 'Cancelada',
            'borrador' => 'Borrador',
        ];
    }

    public function getSucursalesProperty(): Collection
    {
        return $this->sucursalesDisponibles();
    }

    public function getUsuariosProperty(): Collection
    {
        return User::orderBy('name')
            ->get()
            ->mapWithKeys(fn ($u) => [$u->id => $u->name]);
    }

    #[Computed]
    public function ventas(): Collection
    {
        return $this->desglose()
            ->groupBy('linea')
            ->map(fn (Collection $rows, string $linea): array => [
                'linea' => $linea,
                'cantidad' => (float) $rows->sum('cantidad'),
                'subtotal' => (float) $rows->sum('subtotal'),
                'total' => (float) $rows->sum('total'),
            ])
            ->sortByDesc('total')
            ->values();
    }

    #[Computed]
    public function desglose(): Collection
    {
        $ventasNotas = DB::table('nota_venta_venta_partidas as p')
            ->join('notas_venta_venta as n', 'n.id', '=', 'p.nota_venta_venta_id')
            ->leftJoin('productos as prod', 'prod.id', '=', 'p.item')
            ->where('n.estatus', '!=', 'Cancelada')
            ->when($this->cliente_id, fn ($q) => $q->where('n.cliente_id', $this->cliente_id))
            ->when($this->sucursalEfectiva(), fn ($q) => $q->where('n.sucursal_id', $this->sucursalEfectiva()))
            ->when($this->usuario_id, fn ($q) => $q->where('n.user_id', $this->usuario_id))
            ->when($this->estatus, fn ($q) => $q->where('n.estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('n.fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('n.fecha_emision', '<=', $this->fecha_fin))
            ->groupBy('prod.linea', 'prod.grupo', 'prod.id', 'prod.clave', 'prod.descripcion')
            ->select('prod.linea as linea', 'prod.grupo as grupo', 'prod.id as producto_id', 'prod.clave as clave', 'prod.descripcion as descripcion',
                DB::raw('SUM(p.cantidad) as cantidad'),
                DB::raw('SUM(p.subtotal) as subtotal'),
                DB::raw('SUM(p.total) as total')
            )
            ->get();

        $ventasFacturas = DB::table('factura_cfdi_partidas as p')
            ->join('facturas_cfdi as f', 'f.id', '=', 'p.factura_cfdi_id')
            ->leftJoin('productos as prod', 'prod.id', '=', 'p.item')
            ->where('f.estatus', '!=', 'Cancelada')
            ->when($this->cliente_id, fn ($q) => $q->where('f.cliente_id', $this->cliente_id))
            ->when($this->sucursalEfectiva(), fn ($q) => $q->where('f.sucursal_id', $this->sucursalEfectiva()))
            ->when($this->usuario_id, fn ($q) => $q->where('f.user_id', $this->usuario_id))
            ->when($this->estatus, fn ($q) => $q->where('f.estatus', $this->estatus))
            ->when($this->fecha_inicio, fn ($q) => $q->whereDate('f.fecha_emision', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn ($q) => $q->whereDate('f.fecha_emision', '<=', $this->fecha_fin))
            ->groupBy('prod.linea', 'prod.grupo', 'prod.id', 'prod.clave', 'prod.descripcion')
            ->select('prod.linea as linea', 'prod.grupo as grupo', 'prod.id as producto_id', 'prod.clave as clave', 'prod.descripcion as descripcion',
                DB::raw('SUM(p.cantidad) as cantidad'),
                DB::raw('SUM(p.subtotal) as subtotal'),
                DB::raw('SUM(p.total) as total')
            )
            ->get();

        $map = [];

        foreach ($ventasNotas as $row) {
            $linea = $row->linea ?? 'Sin linea';
            $grupo = $row->grupo ?? 'Sin grupo';
            $clave = $row->clave ?? (string) ($row->producto_id ?? 'N/A');
            $key = $linea.'|'.$grupo.'|'.$clave;
            $map[$key] ??= ['linea' => $linea, 'grupo' => $grupo, 'clave' => $clave, 'descripcion' => $row->descripcion ?? 'Producto eliminado', 'cantidad' => 0, 'subtotal' => 0, 'total' => 0];
            $map[$key]['cantidad'] += (float) $row->cantidad;
            $map[$key]['subtotal'] += (float) $row->subtotal;
            $map[$key]['total'] += (float) $row->total;
        }

        foreach ($ventasFacturas as $row) {
            $linea = $row->linea ?? 'Sin linea';
            $grupo = $row->grupo ?? 'Sin grupo';
            $clave = $row->clave ?? (string) ($row->producto_id ?? 'N/A');
            $key = $linea.'|'.$grupo.'|'.$clave;
            $map[$key] ??= ['linea' => $linea, 'grupo' => $grupo, 'clave' => $clave, 'descripcion' => $row->descripcion ?? 'Producto eliminado', 'cantidad' => 0, 'subtotal' => 0, 'total' => 0];
            $map[$key]['cantidad'] += (float) $row->cantidad;
            $map[$key]['subtotal'] += (float) $row->subtotal;
            $map[$key]['total'] += (float) $row->total;
        }

        $collection = collect(array_values($map));

        if ($this->linea) {
            $collection = $collection->where('linea', $this->linea)->values();
        }
        if ($this->grupo) {
            $collection = $collection->where('grupo', $this->grupo)->values();
        }

        return $collection->sortBy([
            ['linea', 'asc'],
            ['grupo', 'asc'],
            ['total', 'desc'],
        ])->values();
    }

    #[Computed]
    public function totalCantidad(): float
    {
        return (float) $this->ventas->sum('cantidad');
    }

    #[Computed]
    public function totalSubtotal(): float
    {
        return (float) $this->ventas->sum('subtotal');
    }

    #[Computed]
    public function totalTotal(): float
    {
        return (float) $this->ventas->sum('total');
    }

    public function exportPdf()
    {
        $ventas = $this->desglose;
        $totals = [
            'cantidad' => $this->totalCantidad,
            'total' => $this->totalTotal,
        ];

        $pdf = Pdf::loadView('exports.reportes.ventas-por-linea-pdf', [
            'ventas' => $ventas,
            'totals' => $totals,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'ventas_por_linea_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Linea', 'Grupo', 'Producto', 'Cantidad', 'Total'];

        foreach ($this->desglose as $row) {
            $rows[] = [
                $row['linea'],
                $row['grupo'],
                $row['clave'].' - '.$row['descripcion'],
                number_format($row['cantidad'], 2, '.', ''),
                number_format($row['total'], 2, '.', ''),
            ];
        }

        $rows[] = [];
        $rows[] = ['TOTALES', '', '', number_format($this->totalCantidad, 2, '.', ''), number_format($this->totalTotal, 2, '.', '')];

        $filename = 'ventas_por_linea_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

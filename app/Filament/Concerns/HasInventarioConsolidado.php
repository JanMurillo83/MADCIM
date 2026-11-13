<?php

namespace App\Filament\Concerns;

use App\Models\ExistenciaSucursal;
use App\Models\MovimientoInventario;
use App\Models\Productos;
use App\Models\Sucursal;
use App\Services\InventarioMovimientoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

trait HasInventarioConsolidado
{
    public ?string $sucursal_id = null;
    public ?int $producto_id = null;
    public ?string $linea = null;
    public ?string $grupo = null;
    public string $existencia = 'todas';
    public bool $soloDiferencias = false;
    public int $pagina = 1;
    public int $porPagina = 5;
    public bool $transferenciaAbierta = false;
    public ?int $productoTransferenciaId = null;
    public ?int $sucursalOrigenId = null;
    public ?int $sucursalDestinoId = null;
    public ?int $cantidadTransferencia = null;
    public string $motivoTransferencia = '';
    public bool $kardexAbierto = false;
    public ?int $productoKardexId = null;

    public function mount(): void
    {
        $this->sucursal_id = auth()->user()?->isAdmin()
            ? null
            : (string) auth()->user()?->sucursal_id;
        $this->sucursalOrigenId = auth()->user()?->sucursal_id;
        $this->sucursalDestinoId = Sucursal::query()
            ->when($this->sucursalOrigenId, fn ($query) => $query->where('id', '!=', $this->sucursalOrigenId))
            ->orderBy('id')
            ->value('id');
    }

    public function getSucursalesProperty(): Collection
    {
        return Sucursal::query()
            ->when(! auth()->user()?->isAdmin(), fn ($query) => $query->whereKey(auth()->user()?->sucursal_id))
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->mapWithKeys(fn (Sucursal $sucursal): array => [(string) $sucursal->id => $sucursal->nombre]);
    }

    public function getProductosProperty(): Collection
    {
        return Productos::query()->orderBy('clave')->get(['id', 'clave', 'descripcion'])
            ->mapWithKeys(fn (Productos $producto): array => [$producto->id => $producto->clave . ' · ' . $producto->descripcion]);
    }

    public function getLineasProperty(): Collection
    {
        return Productos::query()->whereNotNull('linea')->distinct()->orderBy('linea')->pluck('linea', 'linea');
    }

    public function getGruposProperty(): Collection
    {
        return Productos::query()->whereNotNull('grupo')->distinct()->orderBy('grupo')->pluck('grupo', 'grupo');
    }

    #[Computed]
    public function sucursalesVisibles(): Collection
    {
        $query = Sucursal::query()->orderBy('nombre');
        if (! auth()->user()?->isAdmin()) {
            $query->whereKey(auth()->user()?->sucursal_id);
        } elseif ($this->sucursal_id !== null && $this->sucursal_id !== '') {
            $query->whereKey((int) $this->sucursal_id);
        }

        return $query->get(['id', 'nombre']);
    }

    #[Computed]
    public function filasBase(): Collection
    {
        $sucursales = Sucursal::query()->orderBy('nombre')->get(['id', 'nombre']);
        $idsVisibles = $this->sucursalesVisibles->modelKeys();
        $idsParaComparar = auth()->user()?->isAdmin() ? $sucursales->modelKeys() : $idsVisibles;
        $productos = Productos::query()
            ->with(['existenciasSucursal' => fn ($query) => $query->whereIn('sucursal_id', $idsParaComparar)])
            ->when($this->producto_id, fn ($query) => $query->whereKey($this->producto_id))
            ->when($this->linea, fn ($query) => $query->where('linea', $this->linea))
            ->when($this->grupo, fn ($query) => $query->where('grupo', $this->grupo))
            ->orderBy('clave')
            ->get();

        return $productos->map(function (Productos $producto) use ($sucursales, $idsVisibles): array {
            $stockPorSucursal = $producto->existenciasSucursal->keyBy('sucursal_id');
            $sucursalesComparacion = auth()->user()?->isAdmin() ? $sucursales : $this->sucursalesVisibles;
            $existenciasGlobales = $sucursalesComparacion->mapWithKeys(fn (Sucursal $sucursal): array => [
                $sucursal->id => (float) ($stockPorSucursal->get($sucursal->id)?->existencia ?? 0),
            ]);
            $existenciasVisibles = $existenciasGlobales->only($idsVisibles);
            $total = round((float) $existenciasVisibles->sum(), 2);
            $diferente = auth()->user()?->isAdmin() && $existenciasGlobales->count() > 1 && $existenciasGlobales->unique(fn ($stock) => round($stock, 2))->count() > 1;

            return [
                'id' => $producto->id,
                'clave' => $producto->clave,
                'descripcion' => $producto->descripcion,
                'linea' => $producto->linea,
                'grupo' => $producto->grupo,
                'existencias' => $existenciasVisibles,
                'existencias_globales' => $existenciasGlobales,
                'total' => $total,
                'apartado' => 0.0,
                'disponible' => $total,
                'costo' => (float) $producto->costo,
                'ultimo_costo' => (float) ($producto->ultimo_costo ?? 0),
                'valor' => round($total * (float) $producto->costo, 2),
                'diferente' => $diferente,
            ];
        })->values();
    }

    #[Computed]
    public function todasLasFilas(): Collection
    {
        return $this->filasBase
            ->when($this->existencia === 'positiva', fn (Collection $rows): Collection => $rows->where('total', '>', 0))
            ->when($this->existencia === 'cero', fn (Collection $rows): Collection => $rows->where('total', '<=', 0))
            ->when($this->soloDiferencias, fn (Collection $rows): Collection => $rows->where('diferente', true))
            ->values();
    }

    #[Computed]
    public function items(): Collection
    {
        return $this->todasLasFilas->slice(($this->pagina - 1) * $this->porPagina, $this->porPagina)->values();
    }

    #[Computed]
    public function totalProductos(): int
    {
        return $this->todasLasFilas->count();
    }

    #[Computed]
    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->totalProductos / max(1, $this->porPagina)));
    }

    #[Computed]
    public function existenciaPorSucursal(): Collection
    {
        $rows = $this->filasBase;
        return $this->sucursalesVisibles->map(fn (Sucursal $sucursal): array => [
            'nombre' => $sucursal->nombre,
            'existencia' => round((float) $rows->sum(fn (array $row): float => (float) ($row['existencias'][$sucursal->id] ?? 0)), 2),
            'apartado' => 0.0,
            'disponible' => round((float) $rows->sum(fn (array $row): float => (float) ($row['existencias'][$sucursal->id] ?? 0)), 2),
        ]);
    }

    #[Computed]
    public function productosSinExistencia(): int
    {
        return $this->filasBase->where('total', '<=', 0)->count();
    }

    #[Computed]
    public function movimientosRecientes(): Collection
    {
        return MovimientoInventario::query()->with(['producto', 'sucursal'])
            ->when(! auth()->user()?->isAdmin(), fn ($query) => $query->where('sucursal_id', auth()->user()?->sucursal_id))
            ->when(auth()->user()?->isAdmin() && $this->sucursal_id, fn ($query) => $query->where('sucursal_id', $this->sucursal_id))
            ->latest('fecha_movimiento')->limit(4)->get();
    }

    #[Computed]
    public function kardex(): Collection
    {
        $sucursalIds = $this->sucursalesVisibles->modelKeys();
        return MovimientoInventario::query()->with(['producto', 'sucursal', 'user'])
            ->when($this->productoKardexId, fn ($query) => $query->where('producto_id', $this->productoKardexId))
            ->whereIn('sucursal_id', $sucursalIds)
            ->latest('fecha_movimiento')->limit(30)->get();
    }

    public function updatedSucursal_id(): void { $this->reiniciarPagina(); }
    public function updatedProducto_id(): void { $this->reiniciarPagina(); }
    public function updatedLinea(): void { $this->reiniciarPagina(); }
    public function updatedGrupo(): void { $this->reiniciarPagina(); }
    public function updatedExistencia(): void { $this->reiniciarPagina(); }
    public function updatedSoloDiferencias(): void { $this->reiniciarPagina(); }
    public function updatedPorPagina(): void { $this->reiniciarPagina(); }

    public function irPagina(int $pagina): void
    {
        $this->pagina = min(max(1, $pagina), $this->totalPaginas);
    }

    public function abrirTransferencia(): void
    {
        $this->resetValidation();
        $this->transferenciaAbierta = true;
        $this->productoTransferenciaId ??= $this->producto_id;
        $this->sucursalOrigenId ??= auth()->user()?->sucursal_id;
    }

    public function transferirInventario(): void
    {
        $datos = $this->validate([
            'productoTransferenciaId' => ['required', 'integer', 'exists:productos,id'],
            'sucursalOrigenId' => ['required', 'integer', 'exists:sucursales,id'],
            'sucursalDestinoId' => ['required', 'integer', 'different:sucursalOrigenId', 'exists:sucursales,id'],
            'cantidadTransferencia' => ['required', 'integer', 'min:1'],
            'motivoTransferencia' => ['nullable', 'string', 'max:500'],
        ]);

        if (! auth()->user()?->isAdmin() && (int) $datos['sucursalOrigenId'] !== (int) auth()->user()?->sucursal_id) {
            throw ValidationException::withMessages(['sucursalOrigenId' => 'Solo puedes transferir productos desde tu sucursal.']);
        }

        $folio = 'TR-' . Str::upper(Str::random(8));
        $producto = Productos::findOrFail($datos['productoTransferenciaId']);
        $motivo = trim($datos['motivoTransferencia'] ?: 'Transferencia entre sucursales') . " ({$folio})";

        DB::transaction(function () use ($datos, $folio, $motivo): void {
            InventarioMovimientoService::salida(
                (int) $datos['productoTransferenciaId'],
                (float) $datos['cantidadTransferencia'],
                $motivo . ' · salida',
                $folio,
                (int) $datos['sucursalOrigenId'],
            );
            InventarioMovimientoService::entrada(
                (int) $datos['productoTransferenciaId'],
                (float) $datos['cantidadTransferencia'],
                $motivo . ' · entrada',
                $folio,
                (int) $datos['sucursalDestinoId'],
            );
        });

        $this->transferenciaAbierta = false;
        $this->reset(['productoTransferenciaId', 'cantidadTransferencia', 'motivoTransferencia']);
        unset($this->filasBase, $this->todasLasFilas, $this->items, $this->existenciaPorSucursal, $this->productosSinExistencia, $this->movimientosRecientes);
        Notification::make()->title('Transferencia registrada')->body("{$producto->clave} · Folio {$folio}")->success()->send();
    }

    public function abrirKardex(?int $productoId = null): void
    {
        $this->productoKardexId = $productoId ?? $this->producto_id;
        $this->kardexAbierto = true;
        unset($this->kardex);
    }

    public function cerrarKardex(): void
    {
        $this->kardexAbierto = false;
    }

    public function exportPdf()
    {
        $rows = $this->todasLasFilas;
        $pdf = Pdf::loadView('exports.reportes.inventario-pdf', [
            'items' => $rows,
            'totals' => [
                'productos' => $rows->count(),
                'valor' => (float) $rows->sum('valor'),
            ],
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(fn () => print $pdf->output(), 'inventario_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $sucursales = $this->sucursalesVisibles;
        $headers = ['Clave', 'Producto', 'Línea', 'Grupo'];
        foreach ($sucursales as $sucursal) {
            $headers[] = 'Existencia ' . $sucursal->nombre;
        }
        array_push($headers, 'Total', 'Disponible', 'Costo promedio', 'Último costo', 'Valor inventario');
        $rows = [$headers];
        foreach ($this->todasLasFilas as $item) {
            $row = [$item['clave'], $item['descripcion'], $item['linea'], $item['grupo']];
            foreach ($sucursales as $sucursal) {
                $row[] = number_format((float) ($item['existencias'][$sucursal->id] ?? 0), 0, '.', '');
            }
            array_push($row,
                number_format($item['total'], 0, '.', ''),
                number_format($item['disponible'], 0, '.', ''),
                number_format($item['costo'], 2, '.', ''),
                number_format($item['ultimo_costo'], 2, '.', ''),
                number_format($item['valor'], 2, '.', ''),
            );
            $rows[] = $row;
        }

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'inventario_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reiniciarPagina(): void
    {
        $this->pagina = 1;
        unset($this->filasBase, $this->todasLasFilas, $this->items, $this->totalProductos, $this->totalPaginas, $this->existenciaPorSucursal, $this->productosSinExistencia);
    }
}

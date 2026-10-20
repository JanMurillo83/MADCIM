<?php

namespace App\Filament\Pages\Reportes;

use App\Models\Productos;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use App\Filament\Concerns\HasRolePageAccess;

class Inventario extends Page
{
    use HasRolePageAccess;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?string $title = 'Inventario';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.reportes.inventario';

    public static function canAccess(): bool
    {
        return (auth()->user()?->isAdmin() ?? false)
            && (auth()->user()?->canAccessPage(static::class) ?? false);
    }

    public ?int $producto_id = null;
    public ?string $linea = null;
    public ?string $grupo = null;

    public function getProductosProperty(): Collection
    {
        return Productos::orderBy('descripcion')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->clave . ' - ' . $p->descripcion]);
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

    #[Computed]
    public function items(): Collection
    {
        return Productos::query()
            ->when($this->producto_id, fn ($q) => $q->where('id', $this->producto_id))
            ->when($this->linea, fn ($q) => $q->where('linea', $this->linea))
            ->when($this->grupo, fn ($q) => $q->where('grupo', $this->grupo))
            ->orderBy('descripcion')
            ->get()
            ->map(function ($row) {
                return [
                    'clave' => $row->clave,
                    'descripcion' => $row->descripcion,
                    'linea' => $row->linea,
                    'grupo' => $row->grupo,
                    'costo' => (float) $row->costo,
                    'ultimo_costo' => (float) ($row->ultimo_costo ?? 0),
                    'valor' => (float) $row->existencia * (float) $row->costo,
                ];
            });
    }

    public function exportPdf()
    {
        $items = $this->items;
        $pdf = Pdf::loadView('exports.reportes.inventario-pdf', [
            'items' => $items,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'inventario_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $rows = [];
        $rows[] = ['Clave', 'Producto', 'Linea', 'Grupo', 'Costo promedio', 'Ultimo costo'];

        foreach ($this->items as $row) {
            $rows[] = [
                $row['clave'],
                $row['descripcion'],
                $row['linea'],
                $row['grupo'],
                number_format($row['costo'], 2, '.', ''),
                number_format($row['ultimo_costo'], 2, '.', ''),
            ];
        }

        $rows[] = [];

        $filename = 'inventario_' . now()->format('Ymd_His') . '.csv';

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

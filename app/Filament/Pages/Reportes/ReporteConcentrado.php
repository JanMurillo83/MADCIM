<?php

namespace App\Filament\Pages\Reportes;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\ClienteDireccionEntrega;
use App\Models\Clientes;
use App\Models\NotaEnvio;
use App\Models\NotasVentaRenta;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;

class ReporteConcentrado extends Page
{
    use HasRolePageAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'Reporte Concentrado';
    protected static ?string $title = 'Reporte Concentrado';
    protected static string|null|\UnitEnum $navigationGroup = 'Concentrados';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reportes.reporte-concentrado';

    public ?int $cliente_id = null;
    public ?int $nota_origen_id = null;
    public ?int $direccion_entrega_id = null;

    public function getClientesProperty(): Collection
    {
        return Clientes::query()
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->nombre]);
    }

    public function getNotasOrigenProperty(): Collection
    {
        return NotasVentaRenta::query()
            ->with('cliente')
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->whereHas('notasEnvio', function ($q) {
                $q->where(function ($query) {
                    $query->where('estado_renta', '!=', 'Devuelta')
                        ->orWhereNull('estado_renta');
                });
            })
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function ($nota) {
                $cliente = $nota->cliente?->nombre ?? 'Sin cliente';
                return [$nota->id => trim(($nota->serie ?? '') . ($nota->folio ?? '')) . ' - ' . $cliente];
            });
    }

    public function getDireccionesProperty(): Collection
    {
        return ClienteDireccionEntrega::query()
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->nota_origen_id, function ($q) {
                $q->whereIn('id', NotaEnvio::query()
                    ->where('nota_venta_renta_id', $this->nota_origen_id)
                    ->whereNotNull('direccion_entrega_id')
                    ->distinct()
                    ->pluck('direccion_entrega_id'));
            })
            ->orderBy('nombre_direccion')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->id => ($d->nombre_direccion ? ($d->nombre_direccion . ' - ') : '') . $d->direccion_completa]);
    }

    #[Computed]
    public function notas()
    {
        $notas = NotaEnvio::query()
            ->with(['cliente', 'notaVentaRenta', 'direccionEntrega', 'partidas.producto'])
            ->whereNotNull('nota_venta_renta_id')
            ->where(function ($query) {
                $query->where('estado_renta', '!=', 'Devuelta')
                    ->orWhereNull('estado_renta');
            })
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->when($this->nota_origen_id, fn ($q) => $q->where('nota_venta_renta_id', $this->nota_origen_id))
            ->when($this->direccion_entrega_id, fn ($q) => $q->where('direccion_entrega_id', $this->direccion_entrega_id))
            ->orderBy('id')
            ->get();

        return $notas->toBase();
    }

    #[Computed]
    public function grupos(): Collection
    {
        return $this->notas
            ->groupBy(fn ($nota) => implode('|', [
                (string) ($nota->cliente_id ?? 0),
                (string) ($nota->nota_venta_renta_id ?? 0),
                (string) ($nota->direccion_entrega_id ?? 0),
            ]))
            ->map(function (Collection $notasGrupo) {
                $nota = $notasGrupo->first();
                $rows = collect();

                foreach ($notasGrupo as $notaGrupo) {
                    foreach ($notaGrupo->partidas as $partida) {
                        if (($partida->producto?->clave ?? '') === 'SRENTA-M2') {
                            continue;
                        }

                        $clave = trim((string) ($partida->producto?->clave ?? $partida->producto_id ?? 'N/A'));
                        $producto = trim((string) ($partida->producto?->descripcion ?? $partida->descripcion ?? 'N/A'));
                        $precioVenta = (float) ($partida->producto?->precio_venta ?? 0);
                        $cantidadEnviada = (float) $partida->cantidad;
                        $cantidadDevuelta = (float) $partida->cantidad_devuelta;
                        $cantidadPendiente = max(0, $cantidadEnviada - $cantidadDevuelta);

                        $rows->push([
                            'clave' => $clave,
                            'producto' => $producto,
                            'cantidad_enviada' => $cantidadEnviada,
                            'cantidad_devuelta' => $cantidadDevuelta,
                            'cantidad_pendiente' => $cantidadPendiente,
                            'importe_enviado' => $cantidadEnviada * $precioVenta,
                            'importe_devuelto' => $cantidadDevuelta * $precioVenta,
                            'importe_cobrar' => $cantidadPendiente * $precioVenta,
                        ]);
                    }
                }

                return [
                    'resumen' => [
                        'cliente' => $nota?->cliente?->nombre ?? 'N/A',
                        'nota_origen' => $nota && $nota->notaVentaRenta ? trim(($nota->notaVentaRenta->serie ?? '') . ($nota->notaVentaRenta->folio ?? '')) : 'N/A',
                        'telefono' => $nota?->cliente?->telefono ?? 'N/A',
                        'direccion_obra' => $nota?->direccionEntrega?->direccion_completa ?? 'N/A',
                    ],
                    'filas' => $this->agruparFilas($rows),
                    'totales' => [
                        'cantidad_enviada' => (float) $rows->sum('cantidad_enviada'),
                        'cantidad_devuelta' => (float) $rows->sum('cantidad_devuelta'),
                        'cantidad_pendiente' => (float) $rows->sum('cantidad_pendiente'),
                        'importe_enviado' => (float) $rows->sum('importe_enviado'),
                        'importe_devuelto' => (float) $rows->sum('importe_devuelto'),
                        'importe_cobrar' => (float) $rows->sum('importe_cobrar'),
                    ],
                ];
            })
            ->values();
    }

    #[Computed]
    public function filas(): Collection
    {
        $rows = collect();

        foreach ($this->notas as $nota) {
            foreach ($nota->partidas as $partida) {
                if (($partida->producto?->clave ?? '') === 'SRENTA-M2') {
                    continue;
                }

                $clave = trim((string) ($partida->producto?->clave ?? $partida->producto_id ?? 'N/A'));
                $producto = trim((string) ($partida->producto?->descripcion ?? $partida->descripcion ?? 'N/A'));
                $precioVenta = (float) ($partida->producto?->precio_venta ?? 0);
                $cantidadEnviada = (float) $partida->cantidad;
                $cantidadDevuelta = (float) $partida->cantidad_devuelta;
                $cantidadPendiente = max(0, $cantidadEnviada - $cantidadDevuelta);

                $rows->push([
                    'clave' => $clave,
                    'producto' => $producto,
                    'cantidad_enviada' => $cantidadEnviada,
                    'cantidad_devuelta' => $cantidadDevuelta,
                    'cantidad_pendiente' => $cantidadPendiente,
                    'importe_enviado' => $cantidadEnviada * $precioVenta,
                    'importe_devuelto' => $cantidadDevuelta * $precioVenta,
                    'importe_cobrar' => $cantidadPendiente * $precioVenta,
                ]);
            }
        }

        return $this->agruparFilas($rows);
    }

    #[Computed]
    public function totalGeneral(): array
    {
        return [
            'cantidad_enviada' => (float) $this->grupos->sum(fn ($g) => (float) ($g['totales']['cantidad_enviada'] ?? 0)),
            'cantidad_devuelta' => (float) $this->grupos->sum(fn ($g) => (float) ($g['totales']['cantidad_devuelta'] ?? 0)),
            'cantidad_pendiente' => (float) $this->grupos->sum(fn ($g) => (float) ($g['totales']['cantidad_pendiente'] ?? 0)),
            'importe_enviado' => (float) $this->grupos->sum(fn ($g) => (float) ($g['totales']['importe_enviado'] ?? 0)),
            'importe_devuelto' => (float) $this->grupos->sum(fn ($g) => (float) ($g['totales']['importe_devuelto'] ?? 0)),
            'importe_cobrar' => (float) $this->grupos->sum(fn ($g) => (float) ($g['totales']['importe_cobrar'] ?? 0)),
        ];
    }

    private function agruparFilas(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn ($r) => mb_strtolower($r['clave'] . '|' . $r['producto']))
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'clave' => $first['clave'],
                    'producto' => $first['producto'],
                    'cantidad_enviada' => (float) $group->sum('cantidad_enviada'),
                    'cantidad_devuelta' => (float) $group->sum('cantidad_devuelta'),
                    'cantidad_pendiente' => (float) $group->sum('cantidad_pendiente'),
                    'importe_enviado' => (float) $group->sum('importe_enviado'),
                    'importe_devuelto' => (float) $group->sum('importe_devuelto'),
                    'importe_cobrar' => (float) $group->sum('importe_cobrar'),
                ];
            })
            ->values();
    }

    public function exportPdf()
    {
        $pdf = Pdf::loadView('exports.reportes.concentrado-pdf', [
            'titulo' => 'Concentrado de madera enviada al cliente x obra',
            'grupos' => $this->grupos,
            'totalGeneral' => $this->totalGeneral,
        ])->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'reporte_concentrado_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel()
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'concentrado_xlsx_');
        $filename = 'reporte_concentrado_' . now()->format('Ymd_His') . '.xlsx';

        $writer = new XLSXWriter();
        $writer->openToFile($tmpPath);
        $writer->addRow(Row::fromValues(['Concentrado de madera enviada al cliente x obra']));

        foreach ($this->grupos as $index => $grupo) {
            $resumen = $grupo['resumen'];

            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Grupo', (int) $index + 1]));
            $writer->addRow(Row::fromValues(['Cliente', $resumen['cliente']]));
            $writer->addRow(Row::fromValues(['Nota Origen', $resumen['nota_origen']]));
            $writer->addRow(Row::fromValues(['Tel', $resumen['telefono']]));
            $writer->addRow(Row::fromValues(['Direccion de Obra', $resumen['direccion_obra']]));
            $writer->addRow(Row::fromValues(['clave', 'producto', 'Cantidad Enviada', 'Canti. Devuelta', 'Cant. Pend. X devolver', 'Importe Enviado', 'Importe Devuelto', 'Importe x Cobrar']));

            foreach ($grupo['filas'] as $row) {
                $writer->addRow(Row::fromValues([
                    $row['clave'],
                    $row['producto'],
                    (float) $row['cantidad_enviada'],
                    (float) $row['cantidad_devuelta'],
                    (float) $row['cantidad_pendiente'],
                    (float) $row['importe_enviado'],
                    (float) $row['importe_devuelto'],
                    (float) $row['importe_cobrar'],
                ]));
            }

            $totales = $grupo['totales'];
            $writer->addRow(Row::fromValues([
                'TOTAL',
                '',
                (float) $totales['cantidad_enviada'],
                (float) $totales['cantidad_devuelta'],
                (float) $totales['cantidad_pendiente'],
                (float) $totales['importe_enviado'],
                (float) $totales['importe_devuelto'],
                (float) $totales['importe_cobrar'],
            ]));
        }

        $totalGeneral = $this->totalGeneral;
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues([
            'TOTAL GENERAL',
            '',
            (float) $totalGeneral['cantidad_enviada'],
            (float) $totalGeneral['cantidad_devuelta'],
            (float) $totalGeneral['cantidad_pendiente'],
            (float) $totalGeneral['importe_enviado'],
            (float) $totalGeneral['importe_devuelto'],
            (float) $totalGeneral['importe_cobrar'],
        ]));

        $writer->close();

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}

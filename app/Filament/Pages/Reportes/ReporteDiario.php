<?php

namespace App\Filament\Pages\Reportes;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\CajaMovimiento;
use App\Models\NotaDevolucionRenta;
use App\Models\NotaEnvio;
use App\Models\NotaVentaVentaPartidas;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Models\RegistroRenta;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;

class ReporteDiario extends Page
{
    use HasRolePageAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Reporte Diario';
    protected static ?string $title = 'Reporte Diario';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reportes.reporte-diario';

    public ?string $fecha = null;

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
    }

    #[Computed]
    public function reporte(): array
    {
        $rentas = $this->notasRenta();
        $ventas = $this->notasVenta();
        $envios = $this->notasEnvio();
        $devoluciones = $this->notasDevolucion();
        $productosVendidos = $this->productosVendidos();
        $productosRentados = $this->productosRentados();
        $pagos = $this->pagos();
        $egresos = $this->egresos();
        $notasCredito = $this->notasCredito();

        return [
            'fecha' => $this->fecha,
            'resumen' => [
                'notas_renta' => [
                    'cantidad' => $rentas->count(),
                    'total' => (float) $rentas->sum('total'),
                ],
                'notas_venta' => [
                    'cantidad' => $ventas->count(),
                    'total' => (float) $ventas->sum('total'),
                ],
                'notas_envio' => [
                    'cantidad' => $envios->count(),
                ],
                'notas_devolucion' => [
                    'cantidad' => $devoluciones->count(),
                    'unidades' => (float) $devoluciones->sum('unidades'),
                ],
                'productos_vendidos' => [
                    'lineas' => $productosVendidos->count(),
                    'cantidad' => (float) $productosVendidos->sum('cantidad'),
                    'total' => (float) $productosVendidos->sum('total'),
                ],
                'productos_rentados' => [
                    'lineas' => $productosRentados->count(),
                    'cantidad' => (float) $productosRentados->sum('cantidad'),
                    'total' => (float) $productosRentados->sum('importe_renta'),
                ],
                'pagos' => [
                    'cantidad' => $pagos->sum('cantidad'),
                    'total' => (float) $pagos->sum('total'),
                ],
                'egresos' => [
                    'cantidad' => $egresos->count(),
                    'total' => (float) $egresos->sum('importe'),
                ],
                'notas_credito' => [
                    'cantidad' => $notasCredito->count(),
                    'total' => (float) $notasCredito->sum('total'),
                ],
            ],
            'rentas' => $rentas,
            'ventas' => $ventas,
            'envios' => $envios,
            'devoluciones' => $devoluciones,
            'productos_vendidos' => $productosVendidos,
            'productos_rentados' => $productosRentados,
            'pagos' => $pagos->values(),
            'egresos' => $egresos,
            'notas_credito' => $notasCredito,
        ];
    }

    private function notasRenta(): Collection
    {
        return NotasVentaRenta::query()
            ->with('cliente')
            ->whereDate('fecha_emision', $this->fecha)
            ->where('estatus', '!=', 'Cancelada')
            ->orderBy('id')
            ->get()
            ->map(fn (NotasVentaRenta $nota): array => $this->documentoRow($nota, 'Renta'));
    }

    private function notasVenta(): Collection
    {
        return NotasVentaVenta::query()
            ->with('cliente')
            ->whereDate('fecha_emision', $this->fecha)
            ->where('estatus', '!=', 'Cancelada')
            ->orderBy('id')
            ->get()
            ->map(fn (NotasVentaVenta $nota): array => $this->documentoRow($nota, 'Venta'));
    }

    private function notasEnvio(): Collection
    {
        return NotaEnvio::query()
            ->with('cliente')
            ->whereDate('fecha_emision', $this->fecha)
            ->where('estatus', '!=', 'Cancelada')
            ->orderBy('id')
            ->get()
            ->map(fn (NotaEnvio $nota): array => [
                'tipo' => $nota->nota_venta_venta_id ? 'Venta' : 'Renta',
                'folio' => $this->folio($nota),
                'cliente' => $nota->cliente?->nombre ?? 'Sin cliente',
                'estatus' => (string) ($nota->estatus ?? 'Sin estatus'),
            ]);
    }

    private function notasDevolucion(): Collection
    {
        return NotaDevolucionRenta::query()
            ->with(['cliente', 'partidas'])
            ->whereDate('fecha_emision', $this->fecha)
            ->orderBy('id')
            ->get()
            ->map(fn (NotaDevolucionRenta $nota): array => [
                'folio' => $this->folio($nota),
                'cliente' => $nota->cliente?->nombre ?? 'Sin cliente',
                'estatus' => (string) ($nota->estatus ?? 'Sin estatus'),
                'unidades' => (float) $nota->partidas->sum(fn ($partida) => (float) ($partida->cantidad_aplicada ?: $partida->cantidad_recogida)),
            ]);
    }

    private function productosVendidos(): Collection
    {
        return NotaVentaVentaPartidas::query()
            ->with('producto')
            ->whereHas('documento', fn ($query) => $query
                ->whereDate('fecha_emision', $this->fecha)
                ->where('estatus', '!=', 'Cancelada'))
            ->get()
            ->groupBy('item')
            ->map(function (Collection $partidas): array {
                $primera = $partidas->first();
                $cantidad = (float) $partidas->sum('cantidad');
                $total = (float) $partidas->sum(fn ($partida) => (float) $partida->total ?: ((float) $partida->cantidad * (float) $partida->valor_unitario));

                return [
                    'clave' => (string) ($primera?->producto?->clave ?? $primera?->item ?? 'N/A'),
                    'descripcion' => (string) ($primera?->producto?->descripcion ?? $primera?->descripcion ?? 'Producto eliminado'),
                    'cantidad' => $cantidad,
                    'total' => $total,
                ];
            })
            ->sortByDesc('cantidad')
            ->values();
    }

    private function productosRentados(): Collection
    {
        return RegistroRenta::query()
            ->with('producto')
            ->whereDate('fecha_renta', $this->fecha)
            ->get()
            ->groupBy('producto_id')
            ->map(function (Collection $registros): array {
                $primero = $registros->first();

                return [
                    'clave' => (string) ($primero?->producto?->clave ?? $primero?->producto_id ?? 'N/A'),
                    'descripcion' => (string) ($primero?->producto?->descripcion ?? 'Producto eliminado'),
                    'cantidad' => (float) $registros->sum('cantidad'),
                    'importe_renta' => (float) $registros->sum('importe_renta'),
                    'importe_deposito' => (float) $registros->sum('importe_deposito'),
                ];
            })
            ->sortByDesc('cantidad')
            ->values();
    }

    private function pagos(): Collection
    {
        return Pagos::query()
            ->whereDate('fecha_pago', $this->fecha)
            ->whereNull('cfdi_fecha_cancelacion')
            ->get()
            ->groupBy(fn (Pagos $pago): string => $this->formaPago($pago->forma_pago))
            ->map(fn (Collection $pagos, string $forma): array => [
                'forma_pago' => $forma,
                'cantidad' => $pagos->count(),
                'total' => (float) $pagos->sum('importe'),
            ])
            ->sortBy('forma_pago')
            ->values();
    }

    private function egresos(): Collection
    {
        return CajaMovimiento::query()
            ->with(['caja', 'user'])
            ->where('tipo', 'Egreso')
            ->whereDate('fecha', $this->fecha)
            ->orderBy('id')
            ->get()
            ->map(fn (CajaMovimiento $movimiento): array => [
                'concepto' => (string) ($movimiento->fuente ?: $movimiento->observaciones ?: 'Egreso'),
                'referencia' => (string) ($movimiento->referencia ?? ''),
                'metodo_pago' => (string) ($movimiento->metodo_pago ?: 'Sin especificar'),
                'importe' => (float) $movimiento->importe,
                'caja' => $movimiento->caja?->nombre ?? ('Caja ' . ($movimiento->caja_id ?? '')),
                'usuario' => $movimiento->user?->name ?? 'N/A',
            ]);
    }

    private function notasCredito(): Collection
    {
        $rentas = NotasVentaRenta::query()
            ->with('cliente')
            ->whereDate('fecha_emision', $this->fecha)
            ->where('condicion_pago', 'credito')
            ->where('estatus', '!=', 'Cancelada')
            ->get()
            ->map(fn (NotasVentaRenta $nota): array => $this->creditoRow($nota, 'Renta'));

        $ventas = NotasVentaVenta::query()
            ->with('cliente')
            ->whereDate('fecha_emision', $this->fecha)
            ->where('condicion_pago', 'credito')
            ->where('estatus', '!=', 'Cancelada')
            ->get()
            ->map(fn (NotasVentaVenta $nota): array => $this->creditoRow($nota, 'Venta'));

        return $rentas->concat($ventas)->sortBy('folio')->values();
    }

    private function documentoRow(object $nota, string $tipo): array
    {
        return [
            'tipo' => $tipo,
            'folio' => $this->folio($nota),
            'cliente' => $nota->cliente?->nombre ?? 'Sin cliente',
            'total' => (float) ($nota->total ?? 0),
            'estatus' => (string) ($nota->estatus ?? 'Sin estatus'),
        ];
    }

    private function creditoRow(object $nota, string $tipo): array
    {
        return [
            'tipo' => $tipo,
            'folio' => $this->folio($nota),
            'cliente' => $nota->cliente?->nombre ?? 'Sin cliente',
            'total' => (float) ($nota->total ?? 0),
            'vencimiento' => $nota->fecha_vencimiento_pago?->format('Y-m-d') ?? 'N/A',
            'estatus' => (string) ($nota->estatus ?? 'Sin estatus'),
        ];
    }

    private function folio(object $documento): string
    {
        return trim((string) ($documento->serie ?? '') . (string) ($documento->folio ?? '')) ?: '#' . $documento->id;
    }

    private function formaPago(?string $forma): string
    {
        return match (mb_strtolower(trim((string) $forma))) {
            '01', 'efectivo' => 'Efectivo',
            '02', 'cheque' => 'Cheque',
            '03', 'transferencia' => 'Transferencia',
            '04', 'tarjeta crédito', 'tarjeta credito' => 'Tarjeta crédito',
            '28', 'tarjeta débito', 'tarjeta debito' => 'Tarjeta débito',
            '99', 'crédito', 'credito' => 'Crédito',
            default => $forma ?: 'Sin especificar',
        };
    }

    public function exportPdf()
    {
        $reporte = $this->reporte();
        $pdf = Pdf::loadView('exports.reportes.reporte-diario-pdf', compact('reporte'))
            ->setPaper('letter', 'landscape');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, 'reporte_diario_' . str_replace('-', '', (string) $this->fecha) . '.pdf');
    }

    public function exportExcel()
    {
        $reporte = $this->reporte();
        $tmpPath = tempnam(sys_get_temp_dir(), 'reporte_diario_');
        $filename = 'reporte_diario_' . str_replace('-', '', (string) $this->fecha) . '.xlsx';
        $writer = new XLSXWriter();
        $writer->openToFile($tmpPath);

        $writer->addRow(Row::fromValues(['REPORTE DIARIO', $reporte['fecha']]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['RESUMEN EJECUTIVO']));
        foreach ($this->filasResumen($reporte['resumen']) as $fila) {
            $writer->addRow(Row::fromValues($fila));
        }

        $this->agregarSeccionExcel($writer, 'NOTAS DE RENTA', ['Tipo', 'Folio', 'Cliente', 'Total', 'Estatus'], $reporte['rentas'], ['tipo', 'folio', 'cliente', 'total', 'estatus']);
        $this->agregarSeccionExcel($writer, 'NOTAS DE VENTA', ['Tipo', 'Folio', 'Cliente', 'Total', 'Estatus'], $reporte['ventas'], ['tipo', 'folio', 'cliente', 'total', 'estatus']);
        $this->agregarSeccionExcel($writer, 'NOTAS DE ENVIO', ['Tipo', 'Folio', 'Cliente', 'Estatus'], $reporte['envios'], ['tipo', 'folio', 'cliente', 'estatus']);
        $this->agregarSeccionExcel($writer, 'NOTAS DE DEVOLUCION', ['Folio', 'Cliente', 'Estatus', 'Unidades'], $reporte['devoluciones'], ['folio', 'cliente', 'estatus', 'unidades']);
        $this->agregarSeccionExcel($writer, 'PRODUCTOS VENDIDOS', ['Clave', 'Producto', 'Cantidad', 'Total'], $reporte['productos_vendidos'], ['clave', 'descripcion', 'cantidad', 'total']);
        $this->agregarSeccionExcel($writer, 'PRODUCTOS RENTADOS', ['Clave', 'Producto', 'Cantidad', 'Importe renta', 'Deposito'], $reporte['productos_rentados'], ['clave', 'descripcion', 'cantidad', 'importe_renta', 'importe_deposito']);
        $this->agregarSeccionExcel($writer, 'PAGOS POR FORMA DE PAGO', ['Forma de pago', 'Cantidad', 'Total'], $reporte['pagos'], ['forma_pago', 'cantidad', 'total']);
        $this->agregarSeccionExcel($writer, 'EGRESOS', ['Concepto', 'Referencia', 'Forma de pago', 'Importe', 'Caja', 'Usuario'], $reporte['egresos'], ['concepto', 'referencia', 'metodo_pago', 'importe', 'caja', 'usuario']);
        $this->agregarSeccionExcel($writer, 'NOTAS A CREDITO', ['Tipo', 'Folio', 'Cliente', 'Total', 'Vencimiento', 'Estatus'], $reporte['notas_credito'], ['tipo', 'folio', 'cliente', 'total', 'vencimiento', 'estatus']);

        $writer->close();

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function filasResumen(array $resumen): array
    {
        return [
            ['Notas de renta', $resumen['notas_renta']['cantidad'], $resumen['notas_renta']['total']],
            ['Notas de venta', $resumen['notas_venta']['cantidad'], $resumen['notas_venta']['total']],
            ['Notas de envio', $resumen['notas_envio']['cantidad'], ''],
            ['Notas de devolucion', $resumen['notas_devolucion']['cantidad'], $resumen['notas_devolucion']['unidades'] . ' unidades'],
            ['Productos vendidos', $resumen['productos_vendidos']['cantidad'], $resumen['productos_vendidos']['total']],
            ['Productos rentados', $resumen['productos_rentados']['cantidad'], $resumen['productos_rentados']['total']],
            ['Pagos recibidos', $resumen['pagos']['cantidad'], $resumen['pagos']['total']],
            ['Egresos', $resumen['egresos']['cantidad'], $resumen['egresos']['total']],
            ['Notas a credito', $resumen['notas_credito']['cantidad'], $resumen['notas_credito']['total']],
        ];
    }

    private function agregarSeccionExcel(XLSXWriter $writer, string $titulo, array $encabezados, Collection $filas, array $campos): void
    {
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues([$titulo]));
        $writer->addRow(Row::fromValues($encabezados));

        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues(array_map(fn (string $campo) => $fila[$campo] ?? '', $campos)));
        }
    }
}

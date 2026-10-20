<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CobrosPendientes;
use App\Filament\Pages\ProductosRentaVencidos;
use App\Filament\Pages\Reportes\ControlDepositos;
use App\Filament\Resources\CajaMovimientos\CajaMovimientosResource;
use App\Models\CajaMovimiento;
use App\Models\CierreDevolucionRenta;
use App\Models\DevolucionesRenta;
use App\Models\FacturaCfdiPartidas;
use App\Models\FacturasCfdi;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\NotaVentaRentaPartidas;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Models\Sucursal;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class IndicadoresDashboard extends Widget
{
    protected string $view = 'filament.widgets.indicadores-dashboard';

    protected int|string|array $columnSpan = 'full';

    public ?int $sucursalId = null;

    public string $periodo = 'mes';

    public string $mes = '';

    public function mount(): void
    {
        $this->mes = now()->format('Y-m');
        if (($user = auth()->user()) && ! $user->isAdmin()) {
            $this->sucursalId = $user->sucursal_id ? (int) $user->sucursal_id : null;
        }
    }

    public function updatedPeriodo(): void
    {
        $this->dispatch('dashboard-updated');
    }

    public function updatedSucursalId(): void
    {
        $this->dispatch('dashboard-updated');
    }

    public function updatedMes(): void
    {
        $this->periodo = 'mes';
        $this->dispatch('dashboard-updated');
    }

    /** Preserves the prior stats contract used by existing tests/integrations. */
    protected function getStats(): array
    {
        $data = $this->getViewData();
        $stats = [];
        foreach ($data['metrics'] as $metric) {
            $stats[] = Stat::make($metric['label'], self::formatCurrency($metric['value']));
        }
        foreach ($data['metrics'] as $metric) {
            $stats[] = Stat::make($metric['label'], self::formatCurrency($metric['value']));
        }
        $stats[] = Stat::make('Rentas vencidas', (string) $data['alerts']['vencidas']['value']);
        $stats[] = Stat::make('Rentas por vencer', (string) $data['porVencer']);

        return $stats;
    }

    protected function getViewData(): array
    {
        [$inicio, $fin] = $this->rangoPeriodo(now());
        $sucursales = Sucursal::query()->when(! auth()->user()?->isAdmin(), fn (Builder $q) => $q->whereKey(auth()->user()?->sucursal_id))->orderBy('nombre')->get(['id', 'nombre']);
        $definitions = [
            'ventas' => ['Ventas', 'green', 'heroicon-o-chart-bar-square'], 'madera' => ['Renta de madera', 'brown', 'heroicon-o-rectangle-group'],
            'equipo' => ['Renta de equipo', 'orange', 'heroicon-o-wrench-screwdriver'], 'depositos' => ['Depósitos cobrados', 'blue', 'heroicon-o-banknotes'],
            'pendientes' => ['Depósitos pendientes', 'red', 'heroicon-o-document-currency-dollar'],
        ];
        $metrics = [];
        foreach ($definitions as $key => [$label, $color, $icon]) {
            $value = $this->metric($key, $inicio, $fin);
            [$previousStart, $previousEnd] = $this->periodoAnterior($inicio, $fin);
            $previous = $this->metric($key, $previousStart, $previousEnd);
            $metrics[$key] = compact('label', 'color', 'icon', 'value') + ['branches' => $this->branchMetric($key, $inicio, $fin, $sucursales), 'change' => $previous > 0 ? round((($value - $previous) / $previous) * 100, 1) : null];
        }

        return [
            'sucursales' => $sucursales, 'inicio' => $inicio, 'fin' => $fin, 'periodoLabel' => $this->periodoLabel($inicio), 'metrics' => $metrics,
            'chart' => $this->chartData($inicio, $fin, $sucursales),
            'comparativo' => collect($metrics)->map(fn ($m) => ['label' => $m['label'], 'branches' => $m['branches'], 'value' => $m['value']])->values(),
            'alerts' => $this->alerts($inicio, $fin), 'porVencer' => $this->rentasActivas()->whereBetween('fecha_vencimiento', [now()->toDateString(), now()->copy()->addDays(7)->toDateString()])->count(),
            'cash' => $this->cashSummary(), 'annualMetrics' => $this->annualMetrics($sucursales), 'movimientos' => $this->movimientosRecientes(),
            'urls' => ['rentas' => ProductosRentaVencidos::getUrl(), 'depositos' => ControlDepositos::getUrl(), 'cobros' => CobrosPendientes::getUrl(), 'devoluciones' => url('/consulta-devoluciones'), 'movimientos' => CajaMovimientosResource::getUrl('index')],
        ];
    }

    private function rangoPeriodo(Carbon $now): array
    {
        if ($this->periodo === 'hoy') {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }
        if ($this->periodo === 'anio') {
            return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
        }
        $date = preg_match('/^\\d{4}-\\d{2}$/', $this->mes) ? Carbon::createFromFormat('Y-m', $this->mes) : $now;

        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    private function periodoAnterior(Carbon $inicio, Carbon $fin): array
    {
        $days = $inicio->diffInDays($fin) + 1;

        return [$inicio->copy()->subDays($days), $inicio->copy()->subSecond()];
    }

    private function metric(string $key, Carbon $inicio, Carbon $fin): float
    {
        return match ($key) {
            'ventas' => $this->ventas($inicio, $fin), 'madera' => $this->rentaPorLinea($inicio, $fin, 'MADERA'), 'equipo' => $this->rentaPorLinea($inicio, $fin, 'EQUIPO'),
            'depositos' => $this->depositosNetos($inicio, $fin),
            'pendientes' => $this->depositosPendientes($inicio, $fin),
        };
    }

    private function branchMetric(string $key, Carbon $inicio, Carbon $fin, Collection $sucursales): array
    {
        $selected = $this->sucursalId;
        $values = $sucursales->mapWithKeys(function (Sucursal $branch) use ($key, $inicio, $fin) {
            $this->sucursalId = $branch->id;

            return [$branch->id => ['name' => $branch->nombre, 'value' => $this->metric($key, $inicio, $fin)]];
        })->all();
        $this->sucursalId = $selected;

        return $values;
    }

    private function ventas(Carbon $inicio, Carbon $fin): float
    {
        $notas = (float) $this->scope(NotasVentaVenta::query())->whereBetween('fecha_emision', [$inicio, $fin])->where('estatus', '!=', 'Cancelada')->sum('total');
        $facturas = (float) $this->scope(FacturasCfdi::query())->whereBetween('fecha_emision', [$inicio, $fin])->where('estatus', '!=', 'Cancelada')->sum('total');

        // A CFDI can be generated from a note; subtract only the identified note
        // lines included in those CFDI to avoid presenting the same sale twice.
        return $notas + $facturas - $this->importeNotasIncluidasEnFacturas($inicio, $fin);
    }

    private function importeNotasIncluidasEnFacturas(Carbon $inicio, Carbon $fin): float
    {
        $partidas = FacturaCfdiPartidas::query()
            ->where('no_identificacion', 'like', 'NVV:%')
            ->whereHas('documento', function (Builder $query) use ($inicio, $fin): void {
                $query->whereBetween('fecha_emision', [$inicio, $fin])->where('estatus', '!=', 'Cancelada');
                $this->scope($query);
            })
            ->get(['no_identificacion', 'total']);

        $notaIds = $partidas->map(function (FacturaCfdiPartidas $partida): ?int {
            return preg_match('/^NVV:(\\d+):\\d+$/', (string) $partida->no_identificacion, $matches) === 1 ? (int) $matches[1] : null;
        })->filter()->unique();
        $notasActivas = $this->scope(NotasVentaVenta::query())->whereIn('id', $notaIds)->where('estatus', '!=', 'Cancelada')->pluck('id')->flip();

        return (float) $partidas->filter(function (FacturaCfdiPartidas $partida) use ($notasActivas): bool {
            return preg_match('/^NVV:(\\d+):\\d+$/', (string) $partida->no_identificacion, $matches) === 1 && $notasActivas->has((int) $matches[1]);
        })->sum('total');
    }

    private function rentaPorLinea(Carbon $inicio, Carbon $fin, string $linea): float
    {
        return (float) $this->scope(NotaVentaRentaPartidas::query())->whereHas('documento', function (Builder $q) use ($inicio, $fin) {
            $q->whereBetween('fecha_emision', [$inicio, $fin])->where('estatus', '!=', 'Cancelada');
            $this->scope($q);
        })->whereHas('producto', fn (Builder $q) => $q->whereRaw('UPPER(TRIM(linea)) = ?', [$linea]))->sum('total');
    }

    private function depositosPendientes(Carbon $inicio, Carbon $fin): float
    {
        $cobrados = $this->metric('depositos', $inicio, $fin);
        $aplicados = (float) $this->scope(CierreDevolucionRenta::query())->whereBetween('cerrada_en', [$inicio, $fin])->where('estatus', '!=', 'Cancelado')->sum('deposito_aplicado');

        return max(0, $cobrados - $aplicados);
    }

    private function depositosNetos(Carbon $inicio, Carbon $fin): float
    {
        $cobrados = (float) $this->scope(NotasVentaRenta::query())
            ->whereBetween('fecha_emision', [$inicio, $fin])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('deposito');
        $devueltos = (float) $this->scope(CajaMovimiento::query())
            ->whereBetween('fecha', [$inicio, $fin])
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->sum('importe');

        return max(0, $cobrados - $devueltos);
    }

    private function chartData(Carbon $inicio, Carbon $fin, Collection $sucursales): array
    {
        $days = min($inicio->diffInDays($fin) + 1, 31);
        $labels = [];
        $series = ['global' => ['label' => 'Global', 'color' => 'green', 'values' => []]];
        foreach ($sucursales as $branch) {
            $series['branch-'.$branch->id] = ['label' => $branch->nombre, 'color' => count($series) === 1 ? 'brown' : 'orange', 'values' => []];
        }
        $selected = $this->sucursalId;
        for ($i = 0; $i < $days; $i++) {
            $day = $inicio->copy()->addDays($i);
            $labels[] = $day->format('d');
            $this->sucursalId = null;
            $series['global']['values'][] = $this->ventas($day->copy()->startOfDay(), $day->copy()->endOfDay());
            foreach ($sucursales as $branch) {
                $this->sucursalId = $branch->id;
                $series['branch-'.$branch->id]['values'][] = $this->ventas($day->copy()->startOfDay(), $day->copy()->endOfDay());
            }
        }
        $this->sucursalId = $selected;

        return compact('labels', 'series') + ['max' => max(1, ...collect($series)->pluck('values')->flatten()->all())];
    }

    private function rentasActivas(): Builder
    {
        return $this->scope(RegistroRenta::query())->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')->whereHas('notaVentaRenta', fn (Builder $q) => $q->whereIn('estatus', ['Activa', 'Pagada']));
    }

    private function alerts(Carbon $inicio, Carbon $fin): array
    {
        $vencidas = $this->rentasActivas()->whereDate('fecha_vencimiento', '<', now()->toDateString())->count();
        $cobros = $this->scope(NotasVentaVenta::query())->where('estatus', '!=', 'Cancelada')->where('saldo_pendiente', '>', 0)->whereDate('fecha_vencimiento_pago', '<', now()->toDateString())->count() + $this->scope(NotasVentaRenta::query())->where('estatus', '!=', 'Cancelada')->where('saldo_pendiente', '>', 0)->whereDate('fecha_vencimiento_pago', '<', now()->toDateString())->count();

        return ['vencidas' => ['label' => 'Rentas vencidas', 'value' => $vencidas, 'detail' => 'Rentas activas fuera de fecha', 'color' => 'red'], 'depositos' => ['label' => 'Depósitos pendientes', 'value' => $this->depositosPendientes($inicio, $fin), 'detail' => 'Del periodo seleccionado', 'color' => 'orange'], 'stock' => ['label' => 'Productos sin existencia', 'value' => Productos::query()->where('existencia', '<=', 0)->count(), 'detail' => 'No hay umbral de stock mínimo configurado', 'color' => 'yellow'], 'cobros' => ['label' => 'Cuentas por cobrar vencidas', 'value' => $cobros, 'detail' => 'Con saldo pendiente y fecha vencida', 'color' => 'red'], 'devoluciones' => ['label' => 'Devoluciones pendientes', 'value' => $this->obrasListasParaCerrar(), 'detail' => 'Obras sin pendientes por cerrar', 'color' => 'purple']];
    }

    private function obrasListasParaCerrar(): int
    {
        $items = $this->scope(RegistroRenta::query())
            ->with('notaVentaRenta:id,direccion_entrega_id')
            ->whereHas('notaVentaRenta', fn (Builder $query) => $query
                ->whereIn('estatus', ['Activa', 'Pagada'])
                ->whereHas('notasEnvio', fn (Builder $envio) => $envio->whereIn('estatus', ['Enviada', 'Entregada'])))
            ->get(['cliente_id', 'nota_venta_renta_id', 'cantidad', 'cantidad_devuelta']);

        $listas = $items
            ->groupBy(fn (RegistroRenta $item): string => $item->cliente_id . '-' . ($item->notaVentaRenta?->direccion_entrega_id ?? 0))
            ->filter(fn (Collection $grupo): bool => $grupo->every(
                fn (RegistroRenta $item): bool => (float) ($item->cantidad_devuelta ?? 0) >= (float) $item->cantidad
            ));

        if ($listas->isEmpty()) {
            return 0;
        }

        $cierres = $this->scope(CierreDevolucionRenta::query())
            ->whereNotIn('estatus', ['Cancelado'])
            ->get(['cliente_id', 'direccion_entrega_id'])
            ->mapWithKeys(fn (CierreDevolucionRenta $cierre): array => [
                $cierre->cliente_id . '-' . $cierre->direccion_entrega_id => true,
            ]);

        return $listas->keys()->reject(fn (string $clave): bool => $cierres->has($clave))->count();
    }

    private function cashSummary(): array
    {
        $today = [now()->copy()->startOfDay(), now()->copy()->endOfDay()];
        $rows = [];
        foreach (Sucursal::query()->when(! auth()->user()?->isAdmin(), fn (Builder $q) => $q->whereKey(auth()->user()?->sucursal_id))->orderBy('nombre')->get() as $branch) {
            $query = CajaMovimiento::query()->whereHas('caja', fn (Builder $q) => $q->where('sucursal_id', $branch->id))->whereBetween('fecha', $today);
            $amount = fn (string $type, ?string $method = null) => (float) (clone $query)->where('tipo', $type)->when($method, fn (Builder $q) => $q->where('metodo_pago', $method))->sum('importe');
            $retiros = (float) (clone $query)->where('tipo', 'Egreso')->where('fuente', 'like', '%Retiro%')->sum('importe');
            $gastos = (float) (clone $query)->where('tipo', 'Egreso')->where('fuente', 'not like', '%Retiro%')->sum('importe');
            $rows[$branch->id] = ['name' => $branch->nombre, 'efectivo' => $amount('Ingreso', 'Efectivo'), 'tarjeta' => $amount('Ingreso', 'Tarjeta'), 'transferencias' => $amount('Ingreso', 'Transferencia'), 'gastos' => $gastos, 'retiros' => $retiros];
            $rows[$branch->id]['saldo'] = $rows[$branch->id]['efectivo'] + $rows[$branch->id]['tarjeta'] + $rows[$branch->id]['transferencias'] - $rows[$branch->id]['gastos'] - $rows[$branch->id]['retiros'];
        }

        return $rows;
    }

    private function annualMetrics(Collection $sucursales): array
    {
        $start = now()->copy()->startOfYear();
        $end = now()->copy()->endOfYear();
        $items = [];
        foreach (['ventas', 'madera', 'equipo', 'depositos', 'pendientes'] as $key) {
            $items[$key] = ['value' => $this->metric($key, $start, $end), 'branches' => $this->branchMetric($key, $start, $end, $sucursales)];
        }

        return $items;
    }

    private function movimientosRecientes(): Collection
    {
        return $this->scope(CajaMovimiento::query())->with('caja.sucursal')->latest('fecha')->limit(6)->get();
    }

    private function scope(Builder $query): Builder
    {
        if (! $this->sucursalId) {
            return $query;
        } $model = $query->getModel();
        if (in_array('sucursal_id', $model->getFillable(), true)) {
            return $query->where($model->getTable().'.sucursal_id', $this->sucursalId);
        } foreach (['caja', 'notaVentaRenta', 'notaOrigen', 'documentoOrigen'] as $relation) {
            if (method_exists($model, $relation)) {
                return $query->whereHas($relation, fn (Builder $q) => $q->where('sucursal_id', $this->sucursalId));
            }
        }

        return $query;
    }

    private function periodoLabel(Carbon $inicio): string
    {
        return $this->periodo === 'hoy' ? $inicio->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y') : ($this->periodo === 'anio' ? $inicio->format('Y') : ucfirst($inicio->locale('es')->translatedFormat('F Y')));
    }

    public static function formatCurrency(float|int|null $value): string
    {
        return '$'.number_format((float) $value, 2, '.', ',');
    }
}

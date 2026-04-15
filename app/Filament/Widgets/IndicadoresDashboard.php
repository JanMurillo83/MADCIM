<?php

namespace App\Filament\Widgets;

use App\Models\CajaMovimiento;
use App\Models\FacturasCfdi;
use App\Models\NotaVentaRentaPartidas;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Productos;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class IndicadoresDashboard extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = now();
        $inicioMes = $now->copy()->startOfMonth();
        $finMes = $now->copy()->endOfMonth();
        $inicioAnio = $now->copy()->startOfYear();
        $finAnio = $now->copy()->endOfYear();

        $ventasNotas = NotasVentaVenta::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasFacturas = FacturasCfdi::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasDelMes = $ventasNotas + $ventasFacturas;

        $ventasNotasAnio = NotasVentaVenta::query()
            ->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasFacturasAnio = FacturasCfdi::query()
            ->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasDelAnio = $ventasNotasAnio + $ventasFacturasAnio;

        $depositosCobradosDelMes = NotasVentaRenta::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('deposito');

        $depositosDevueltosDelMes = CajaMovimiento::query()
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->sum('importe');

        $depositosNetosDelMes = $depositosCobradosDelMes - $depositosDevueltosDelMes;

        $depositosCobradosDelAnio = NotasVentaRenta::query()
            ->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('deposito');

        $depositosDevueltosDelAnio = CajaMovimiento::query()
            ->whereBetween('fecha', [$inicioAnio, $finAnio])
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->sum('importe');

        $depositosNetosDelAnio = $depositosCobradosDelAnio - $depositosDevueltosDelAnio;

        $rentasMaderaDelMes = NotaVentaRentaPartidas::query()
            ->whereHas('documento', function ($query) use ($inicioMes, $finMes) {
                $query->whereBetween('fecha_emision', [$inicioMes, $finMes])
                    ->where('estatus', '!=', 'Cancelada');
            })
            ->whereHas('producto', function ($query) {
                $query->whereRaw("UPPER(TRIM(linea)) = 'MADERA'");
            })
            ->sum('total');

        $rentasEquipoDelMes = NotaVentaRentaPartidas::query()
            ->whereHas('documento', function ($query) use ($inicioMes, $finMes) {
                $query->whereBetween('fecha_emision', [$inicioMes, $finMes])
                    ->where('estatus', '!=', 'Cancelada');
            })
            ->whereHas('producto', function ($query) {
                $query->whereRaw("UPPER(TRIM(linea)) = 'EQUIPO'");
            })
            ->sum('total');

        $rentasMaderaDelAnio = NotaVentaRentaPartidas::query()
            ->whereHas('documento', function ($query) use ($inicioAnio, $finAnio) {
                $query->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
                    ->where('estatus', '!=', 'Cancelada');
            })
            ->whereHas('producto', function ($query) {
                $query->whereRaw("UPPER(TRIM(linea)) = 'MADERA'");
            })
            ->sum('total');

        $rentasEquipoDelAnio = NotaVentaRentaPartidas::query()
            ->whereHas('documento', function ($query) use ($inicioAnio, $finAnio) {
                $query->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
                    ->where('estatus', '!=', 'Cancelada');
            })
            ->whereHas('producto', function ($query) {
                $query->whereRaw("UPPER(TRIM(linea)) = 'EQUIPO'");
            })
            ->sum('total');

        $diasPorVencer = 7;

        // Rentas activas (no devueltas ni canceladas)
        $rentasBase = NotasVentaRenta::query()
            ->whereNotNull('fecha_vencimiento')
            ->whereIn('estatus', ['Activa', 'Pagada']);

        // Rentas vencidas: fecha_vencimiento ya pasó
        $rentasVencidas = (clone $rentasBase)
            ->where('fecha_vencimiento', '<', $now->toDateString())
            ->count();

        // Rentas por vencer: fecha_vencimiento en los próximos 7 días
        $fechaFinPorVencer = $now->copy()->addDays($diasPorVencer);
        $rentasPorVencer = (clone $rentasBase)
            ->whereBetween('fecha_vencimiento', [$now->toDateString(), $fechaFinPorVencer->toDateString()])
            ->count();

        $valorInventario = Productos::query()
            ->selectRaw('SUM(existencia * precio_venta) as total')
            ->value('total') ?? 0;

        return [
            Stat::make('Mensual | Ventas', $this->formatCurrency($ventasDelMes))
                ->description($this->descriptionWithLink('Notas de venta y facturas del mes', '/notas-venta-venta/notas-venta-ventas'))
                ->icon('heroicon-o-banknotes')
                ->color('info'),
            Stat::make('Mensual | Renta Madera', $this->formatCurrency((float) $rentasMaderaDelMes))
                ->description($this->descriptionWithLink('Notas de renta del mes (línea MADERA)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-receipt-refund')
                ->color('info'),
            Stat::make('Mensual | Renta Equipo', $this->formatCurrency((float) $rentasEquipoDelMes))
                ->description($this->descriptionWithLink('Notas de renta del mes (línea EQUIPO)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-receipt-refund')
                ->color('info'),
            Stat::make('Mensual | Depósitos Netos', $this->formatCurrency((float) $depositosNetosDelMes))
                ->description($this->descriptionWithLink('Depósitos cobrados menos devoluciones del mes', '/control-depositos'))
                ->icon('heroicon-o-shield-check')
                ->color('info'),
            Stat::make('Anual | Ventas Acumuladas', $this->formatCurrency((float) $ventasDelAnio))
                ->description($this->descriptionWithLink('Notas de venta y facturas del año', '/notas-venta-venta/notas-venta-ventas'))
                ->icon('heroicon-o-chart-bar-square')
                ->color('success'),
            Stat::make('Anual | Renta Madera', $this->formatCurrency((float) $rentasMaderaDelAnio))
                ->description($this->descriptionWithLink('Notas de renta del año (línea MADERA)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-rectangle-group')
                ->color('success'),
            Stat::make('Anual | Renta Equipo', $this->formatCurrency((float) $rentasEquipoDelAnio))
                ->description($this->descriptionWithLink('Notas de renta del año (línea EQUIPO)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success'),
            Stat::make('Anual | Depósitos Netos', $this->formatCurrency((float) $depositosNetosDelAnio))
                ->description($this->descriptionWithLink('Depósitos cobrados menos devoluciones del año', '/control-depositos'))
                ->icon('heroicon-o-calendar-days')
                ->color('success'),
            Stat::make('Rentas vencidas', (string) $rentasVencidas)
                ->description($this->descriptionWithLink('Rentas sin devolucion', '/notas-rentadas'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Rentas por vencer', (string) $rentasPorVencer)
                ->description($this->descriptionWithLink('Proximas ' . $diasPorVencer . ' dias', '/notas-rentadas'))
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Valor inventario actual', $this->formatCurrency((float) $valorInventario))
                ->description($this->descriptionWithLink('Existencia x precio de venta', '/productos'))
                ->icon('heroicon-o-archive-box'),
        ];
    }

    private function descriptionWithLink(string $text, string $url): HtmlString
    {
        $safeText = e($text);
        $safeUrl = e($url);

        return new HtmlString($safeText . ' <a class="fi-btn fi-size-xs fi-outlined" href="' . $safeUrl . '" wire:navigate>Ver</a>');
    }

    private function formatCurrency(float $value): string
    {
        return '$' . number_format($value, 2, '.', ',');
    }
}

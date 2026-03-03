<?php

namespace App\Filament\Widgets;

use App\Models\CajaMovimiento;
use App\Models\FacturasCfdi;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Productos;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IndicadoresDashboard extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = now();
        $inicioMes = $now->copy()->startOfMonth();
        $finMes = $now->copy()->endOfMonth();

        $ventasNotas = NotasVentaVenta::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasFacturas = FacturasCfdi::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasDelMes = $ventasNotas + $ventasFacturas;

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

        $rentasSinDepositoDelMes = NotasVentaRenta::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelada')
            ->selectRaw('SUM(total - deposito) as total')
            ->value('total') ?? 0;

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
            Stat::make('Ventas del mes', $this->formatCurrency($ventasDelMes))
                ->description('Notas de venta y facturas del mes')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Rentas del mes', $this->formatCurrency((float) $rentasSinDepositoDelMes))
                ->description('Notas de renta del mes (sin depósito)')
                ->icon('heroicon-o-receipt-refund'),
            Stat::make('Depósitos del mes', $this->formatCurrency((float) $depositosNetosDelMes))
                ->description('Depósitos cobrados menos devoluciones del mes')
                ->icon('heroicon-o-shield-check'),
            Stat::make('Rentas vencidas', (string) $rentasVencidas)
                ->description('Rentas sin devolucion')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Rentas por vencer', (string) $rentasPorVencer)
                ->description('Proximas ' . $diasPorVencer . ' dias')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Valor inventario actual', $this->formatCurrency((float) $valorInventario))
                ->description('Existencia x precio de venta')
                ->icon('heroicon-o-archive-box'),
        ];
    }

    private function formatCurrency(float $value): string
    {
        return '$' . number_format($value, 2, '.', ',');
    }
}

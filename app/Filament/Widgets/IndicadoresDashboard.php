<?php

namespace App\Filament\Widgets;

use App\Enums\TipoDocumento;
use App\Models\Documentos;
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

        $ventasDelMes = Documentos::query()
            ->whereIn('tipo', [
                TipoDocumento::NotaVentaVenta->value,
                TipoDocumento::FacturaCfdi->value,
            ])
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->sum('total');

        $rentasDelMes = Documentos::query()
            ->where('tipo', TipoDocumento::NotaVentaRenta->value)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->sum('total');

        $diasVencimientoRenta = 30;
        $diasPorVencer = 7;

        $rentasBase = Documentos::query()
            ->where('tipo', TipoDocumento::NotaVentaRenta->value)
            ->whereNotNull('fecha_emision')
            ->whereDoesntHave('documentosRelacionados', function ($query) {
                $query->where('tipo', TipoDocumento::DevolucionRenta->value);
            });

        $fechaLimiteVencidas = $now->copy()->subDays($diasVencimientoRenta);
        $rentasVencidas = (clone $rentasBase)
            ->where('fecha_emision', '<=', $fechaLimiteVencidas)
            ->count();

        $fechaInicioPorVencer = $now->copy()->subDays($diasVencimientoRenta);
        $fechaFinPorVencer = $now->copy()->addDays($diasPorVencer)->subDays($diasVencimientoRenta);
        $rentasPorVencer = (clone $rentasBase)
            ->whereBetween('fecha_emision', [$fechaInicioPorVencer, $fechaFinPorVencer])
            ->count();

        $valorInventario = Productos::query()
            ->selectRaw('SUM(existencia * precio_venta) as total')
            ->value('total') ?? 0;

        return [
            Stat::make('Ventas del mes', $this->formatCurrency($ventasDelMes))
                ->description('Notas de venta y facturas del mes')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Rentas del mes', $this->formatCurrency($rentasDelMes))
                ->description('Notas de renta del mes')
                ->icon('heroicon-o-receipt-refund'),
            Stat::make('Rentas vencidas', (string) $rentasVencidas)
                ->description('Rentas sin devolucion')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Rentas por vencer', (string) $rentasPorVencer)
                ->description('Proximas ' . $diasPorVencer . ' dias')
                ->icon('heroicon-o-clock'),
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

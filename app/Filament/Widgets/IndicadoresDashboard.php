<?php

namespace App\Filament\Widgets;

use App\Models\CajaMovimiento;
use App\Models\CierreDevolucionRenta;
use App\Models\FacturaCfdiPartidas;
use App\Models\FacturasCfdi;
use App\Models\NotaVentaRentaPartidas;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Productos;
use App\Models\RegistroRenta;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Filament\Pages\ProductosRentaPorVencer;
use App\Filament\Pages\ProductosRentaVencidos;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class IndicadoresDashboard extends StatsOverviewWidget
{
    public function content(Schema $schema): Schema
    {
        $stats = $this->getStats();

        return $schema
            ->columns(['@xl' => 2, '!@lg' => 1])
            ->components([
                Section::make('Este mes')
                    ->description('Resultados de ' . now()->locale('es')->translatedFormat('F Y'))
                    ->schema(array_slice($stats, 0, 5))
                    ->columns(['@xl' => 2, '!@lg' => 1])
                    ->contained()
                    ->gridContainer(),
                Section::make('Acumulado del año')
                    ->description('Resultados acumulados de ' . now()->year)
                    ->schema(array_slice($stats, 5, 5))
                    ->columns(['@xl' => 2, '!@lg' => 1])
                    ->contained()
                    ->gridContainer(),
                Section::make('Operación actual')
                    ->description('Seguimiento de rentas e inventario')
                    ->schema(array_slice($stats, 10))
                    ->columns(['@xl' => 3, '!@lg' => 1])
                    ->contained()
                    ->gridContainer()
                    ->columnSpanFull(),
            ]);
    }

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

        $ventasNotasYaFacturadasDelMes = $this->importeNotasIncluidasEnFacturas($inicioMes, $finMes);
        $ventasDelMes = $ventasNotas + $ventasFacturas - $ventasNotasYaFacturadasDelMes;

        $ventasNotasAnio = NotasVentaVenta::query()
            ->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasFacturasAnio = FacturasCfdi::query()
            ->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('total');

        $ventasNotasYaFacturadasDelAnio = $this->importeNotasIncluidasEnFacturas($inicioAnio, $finAnio);
        $ventasDelAnio = $ventasNotasAnio + $ventasFacturasAnio - $ventasNotasYaFacturadasDelAnio;

        $depositosCobradosDelMes = NotasVentaRenta::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('deposito');

        $depositosDevueltosDelMes = CajaMovimiento::query()
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->sum('importe');

        $depositosAplicadosDelMes = CierreDevolucionRenta::query()
            ->whereBetween('cerrada_en', [$inicioMes, $finMes])
            ->where('estatus', '!=', 'Cancelado')
            ->sum('deposito_aplicado');

        $depositosPendientesDelMes = max(0, $depositosCobradosDelMes - $depositosAplicadosDelMes - $depositosDevueltosDelMes);

        $depositosCobradosDelAnio = NotasVentaRenta::query()
            ->whereBetween('fecha_emision', [$inicioAnio, $finAnio])
            ->where('estatus', '!=', 'Cancelada')
            ->sum('deposito');

        $depositosDevueltosDelAnio = CajaMovimiento::query()
            ->whereBetween('fecha', [$inicioAnio, $finAnio])
            ->where('tipo', 'Egreso')
            ->where('fuente', 'Devolución depósito renta')
            ->sum('importe');

        $depositosAplicadosDelAnio = CierreDevolucionRenta::query()
            ->whereBetween('cerrada_en', [$inicioAnio, $finAnio])
            ->where('estatus', '!=', 'Cancelado')
            ->sum('deposito_aplicado');

        $depositosPendientesDelAnio = max(0, $depositosCobradosDelAnio - $depositosAplicadosDelAnio - $depositosDevueltosDelAnio);

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

        // Productos rentados con cantidad pendiente de devolución.
        $rentasBase = RegistroRenta::query()
            ->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')
            ->whereHas('notaVentaRenta', fn ($query) => $query->whereIn('estatus', ['Activa', 'Pagada']));

        $aplicarFiltroVencimiento = function ($query, string $inicio, string $fin): void {
            $query->whereBetween('fecha_vencimiento', [$inicio, $fin]);
        };

        // Rentas vencidas: fecha_vencimiento ya pasó
        $rentasVencidasQuery = clone $rentasBase;
        $aplicarFiltroVencimiento($rentasVencidasQuery, '1900-01-01', $now->copy()->subDay()->toDateString());
        $rentasVencidas = $rentasVencidasQuery->count();

        // Rentas por vencer: fecha_vencimiento en los próximos 7 días
        $fechaFinPorVencer = $now->copy()->addDays($diasPorVencer);
        $rentasPorVencerQuery = clone $rentasBase;
        $aplicarFiltroVencimiento($rentasPorVencerQuery, $now->toDateString(), $fechaFinPorVencer->toDateString());
        $rentasPorVencer = $rentasPorVencerQuery->count();

        $usuario = auth()->user();
        $valorInventario = $usuario?->isAdmin()
            ? (Productos::query()->selectRaw('SUM(existencia * precio_venta) as total')->value('total') ?? 0)
            : null;

        return [
            Stat::make('Ventas', $this->formatCurrency($ventasDelMes))
                ->description($this->descriptionWithLink('Notas de venta y facturas del mes', '/notas-venta-venta/notas-venta-ventas'))
                ->icon('heroicon-o-banknotes')
                ->color('info'),
            Stat::make('Renta de madera', $this->formatCurrency((float) $rentasMaderaDelMes))
                ->description($this->descriptionWithLink('Notas de renta del mes (línea MADERA)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-receipt-refund')
                ->color('info'),
            Stat::make('Renta de equipo', $this->formatCurrency((float) $rentasEquipoDelMes))
                ->description($this->descriptionWithLink('Notas de renta del mes (línea EQUIPO)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-receipt-refund')
                ->color('info'),
            Stat::make('Depósitos cobrados', $this->formatCurrency((float) $depositosCobradosDelMes))
                ->description($this->descriptionWithLink('Monto total de depósitos cobrados en el mes', '/control-depositos'))
                ->icon('heroicon-o-shield-check')
                ->color('info'),
            Stat::make('Depósitos pendientes', $this->formatCurrency((float) $depositosPendientesDelMes))
                ->description($this->descriptionWithLink('Pendiente de devolución de los depósitos cobrados este mes', '/control-depositos'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning'),
            Stat::make('Ventas', $this->formatCurrency((float) $ventasDelAnio))
                ->description($this->descriptionWithLink('Notas de venta y facturas del año', '/notas-venta-venta/notas-venta-ventas'))
                ->icon('heroicon-o-chart-bar-square')
                ->color('success'),
            Stat::make('Renta de madera', $this->formatCurrency((float) $rentasMaderaDelAnio))
                ->description($this->descriptionWithLink('Notas de renta del año (línea MADERA)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-rectangle-group')
                ->color('success'),
            Stat::make('Renta de equipo', $this->formatCurrency((float) $rentasEquipoDelAnio))
                ->description($this->descriptionWithLink('Notas de renta del año (línea EQUIPO)', '/notas-venta-renta/notas-venta-rentas'))
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success'),
            Stat::make('Depósitos cobrados', $this->formatCurrency((float) $depositosCobradosDelAnio))
                ->description($this->descriptionWithLink('Monto total de depósitos cobrados en el año', '/control-depositos'))
                ->icon('heroicon-o-calendar-days')
                ->color('success'),
            Stat::make('Depósitos pendientes', $this->formatCurrency((float) $depositosPendientesDelAnio))
                ->description($this->descriptionWithLink('Pendiente de devolución de los depósitos cobrados este año', '/control-depositos'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning'),
            Stat::make('Rentas vencidas', (string) $rentasVencidas)
                ->description($this->descriptionWithLink('Productos rentados sin devolucion', ProductosRentaVencidos::getUrl()))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Rentas por vencer', (string) $rentasPorVencer)
                ->description($this->descriptionWithLink('Productos proximos a vencer', ProductosRentaPorVencer::getUrl()))
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Valor inventario actual', $valorInventario === null ? 'N/D' : $this->formatCurrency((float) $valorInventario))
                ->description($valorInventario === null
                    ? 'El inventario aún no está segmentado por sucursal'
                    : $this->descriptionWithLink('Existencia x precio de venta', '/productos'))
                ->icon('heroicon-o-archive-box'),
        ];
    }

    private function descriptionWithLink(string $text, string $url): HtmlString
    {
        $safeText = e($text);
        $safeUrl = e($url);

        return new HtmlString($safeText . ' <a class="fi-btn fi-size-xs fi-outlined" href="' . $safeUrl . '" wire:navigate>Ver</a>');
    }

    private function importeNotasIncluidasEnFacturas($inicio, $fin): float
    {
        $partidasFacturadas = FacturaCfdiPartidas::query()
            ->where('no_identificacion', 'like', 'NVV:%')
            ->whereHas('documento', function ($query) use ($inicio, $fin): void {
                $query->whereBetween('fecha_emision', [$inicio, $fin])
                    ->where('estatus', '!=', 'Cancelada');
            })
            ->get(['no_identificacion', 'total']);

        if ($partidasFacturadas->isEmpty()) {
            return 0;
        }

        $notaIds = $partidasFacturadas
            ->map(fn (FacturaCfdiPartidas $partida): ?int => $this->notaIdDesdeIdentificador($partida->no_identificacion))
            ->filter()
            ->unique()
            ->values();

        $notasActivas = NotasVentaVenta::query()
            ->whereIn('id', $notaIds)
            ->where('estatus', '!=', 'Cancelada')
            ->pluck('id')
            ->flip();

        return (float) $partidasFacturadas
            ->filter(function (FacturaCfdiPartidas $partida) use ($notasActivas): bool {
                $notaId = $this->notaIdDesdeIdentificador($partida->no_identificacion);

                return $notaId !== null && $notasActivas->has($notaId);
            })
            ->sum('total');
    }

    private function notaIdDesdeIdentificador(?string $identificador): ?int
    {
        if (!preg_match('/^NVV:(\d+):\d+$/', (string) $identificador, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function formatCurrency(float $value): string
    {
        return '$' . number_format($value, 2, '.', ',');
    }
}

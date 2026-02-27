<?php

namespace App\Filament\Pages\Reportes;

use BackedEnum;
use Filament\Pages\Page;
use App\Filament\Pages\Reportes\CajaDiaria;
use App\Filament\Pages\Reportes\ComprasPorPeriodo;
use App\Filament\Pages\Reportes\ControlDepositos;
use App\Filament\Pages\Reportes\CuentasPorCobrar;
use App\Filament\Pages\Reportes\DetallePorCliente;
use App\Filament\Pages\Reportes\Inventario;
use App\Filament\Pages\Reportes\OrdenesCompraPorEstatus;
use App\Filament\Pages\Reportes\ProductosMasRentados;
use App\Filament\Pages\Reportes\RecepcionesPorProveedor;
use App\Filament\Pages\Reportes\RentasActivasVencidas;
use App\Filament\Pages\Reportes\VentasPorLinea;
use App\Filament\Pages\Reportes\VentasPorPeriodo;
use App\Filament\Concerns\HasRolePageAccess;

class CentroReportes extends Page
{
    use HasRolePageAccess;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Centro de Reportes';
    protected static ?string $title = 'Centro de Reportes';
    protected static string|null|\UnitEnum $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.reportes.centro-reportes';

    public array $reportes = [];

    public function mount(): void
    {
        $this->reportes = [
            [
                'titulo' => 'Ventas y Rentas por Periodo',
                'descripcion' => 'Ventas, rentas y CFDI por rango de fechas con filtros por cliente, sucursal y usuario.',
                'url' => VentasPorPeriodo::getUrl(),
            ],
            [
                'titulo' => 'Compras por Periodo',
                'descripcion' => 'Requisiciones, ordenes y recepciones por rango de fechas con filtros por proveedor.',
                'url' => ComprasPorPeriodo::getUrl(),
            ],
            [
                'titulo' => 'Ordenes de Compra por Estatus',
                'descripcion' => 'Seguimiento de ordenes por estatus y proveedor.',
                'url' => OrdenesCompraPorEstatus::getUrl(),
            ],
            [
                'titulo' => 'Recepciones por Proveedor',
                'descripcion' => 'Recepciones por proveedor y rango de fechas.',
                'url' => RecepcionesPorProveedor::getUrl(),
            ],
            [
                'titulo' => 'Rentas Activas y Vencidas',
                'descripcion' => 'Rentas por estatus y estado de vencimiento.',
                'url' => RentasActivasVencidas::getUrl(),
            ],
            [
                'titulo' => 'Ventas por Linea',
                'descripcion' => 'Consolidado de ventas por linea de producto.',
                'url' => VentasPorLinea::getUrl(),
            ],
            [
                'titulo' => 'Caja Diaria',
                'descripcion' => 'Entradas y salidas de caja por dia.',
                'url' => CajaDiaria::getUrl(),
            ],
            [
                'titulo' => 'Control de Depositos',
                'descripcion' => 'Seguimiento de depositos por rentas.',
                'url' => ControlDepositos::getUrl(),
            ],
            [
                'titulo' => 'Cuentas por Cobrar',
                'descripcion' => 'Saldos pendientes por cliente.',
                'url' => CuentasPorCobrar::getUrl(),
            ],
            [
                'titulo' => 'Detalle por Cliente',
                'descripcion' => 'Detalle de documentos por cliente.',
                'url' => DetallePorCliente::getUrl(),
            ],
            [
                'titulo' => 'Inventario',
                'descripcion' => 'Reporte de existencias y costos.',
                'url' => Inventario::getUrl(),
            ],
            [
                'titulo' => 'Productos Mas Rentados',
                'descripcion' => 'Ranking de productos con mas rentas.',
                'url' => ProductosMasRentados::getUrl(),
            ],
        ];
    }
}

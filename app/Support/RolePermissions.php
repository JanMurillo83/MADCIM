<?php

namespace App\Support;

class RolePermissions
{
    public const ROLE_ADMIN = 'Administrador';
    public const ROLE_SUPERVISOR = 'Supervisor';
    public const ROLE_CAJERO = 'Cajero';
    public const ROLE_VENDEDOR = 'Vendedor';
    public const ROLE_ALMACEN = 'Almacen';
    public const ROLE_ENTREGAS = 'Entregas';

    public const RESOURCES = [
        'App\\Filament\\Resources\\Usuarios\\UsuariosResource' => [self::ROLE_ADMIN],
        'App\\Filament\\Resources\\Sucursales\\SucursalesResource' => [self::ROLE_ADMIN],
        'App\\Filament\\Resources\\Configuracion\\ConfiguracionResource' => [self::ROLE_ADMIN],

        'App\\Filament\\Resources\\Clientes\\ClientesResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],
        'App\\Filament\\Resources\\ClienteDireccionEntregas\\ClienteDireccionEntregaResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],
        'App\\Filament\\Resources\\Cotizaciones\\CotizacionesResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],

        'App\\Filament\\Resources\\NotasVentaRenta\\NotasVentaRentaResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],
        'App\\Filament\\Resources\\NotasVentaVenta\\NotasVentaVentaResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],
        'App\\Filament\\Resources\\FacturasCfdi\\FacturasCfdiResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR, self::ROLE_CAJERO],
        'App\\Filament\\Resources\\Pagos\\PagosResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_CAJERO],
        'App\\Filament\\Resources\\Cajas\\CajasResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_CAJERO],
        'App\\Filament\\Resources\\CajaMovimientos\\CajaMovimientosResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_CAJERO],

        'App\\Filament\\Resources\\Productos\\ProductosResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN],
        'App\\Filament\\Resources\\Embarques\\EmbarquesResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN, self::ROLE_ENTREGAS],
        'App\\Filament\\Resources\\NotasEnvio\\NotasEnvioResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN, self::ROLE_ENTREGAS],
        'App\\Filament\\Resources\\ItemsEnRenta\\ItemsEnRentaResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN, self::ROLE_ENTREGAS],
        'App\\Filament\\Resources\\NotasRentadas\\NotasRentadasResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN, self::ROLE_ENTREGAS],

        'App\\Filament\\Resources\\DevolucionesRenta\\DevolucionesRentaResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],
        'App\\Filament\\Resources\\DevolucionesVenta\\DevolucionesVentaResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],

        'App\\Filament\\Resources\\Proveedores\\ProveedoresResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN],
        'App\\Filament\\Resources\\RequisicionesCompra\\RequisicionesCompraResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN],
        'App\\Filament\\Resources\\OrdenesCompra\\OrdenesCompraResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN],
        'App\\Filament\\Resources\\RecepcionesCompra\\RecepcionesCompraResource' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN],
    ];

    public const PAGES = [
        'App\\Filament\\Pages\\AyudaPage' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_VENDEDOR],
        'App\\Filament\\Pages\\ConsultaItemsRentadosPorDireccion' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_ALMACEN, self::ROLE_ENTREGAS],

        'App\\Filament\\Pages\\Reportes\\CentroReportes' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\CajaDiaria' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\ControlDepositos' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\CuentasPorCobrar' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\DetallePorCliente' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\Inventario' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\ProductosMasRentados' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\RentasActivasVencidas' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\VentasPorLinea' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\VentasPorPeriodo' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\ComprasPorPeriodo' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\OrdenesCompraPorEstatus' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
        'App\\Filament\\Pages\\Reportes\\RecepcionesPorProveedor' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
    ];

    public static function canAccessResource(string $class, ?string $role): bool
    {
        if ($role === self::ROLE_ADMIN) {
            return true;
        }

        $allowed = self::RESOURCES[$class] ?? null;
        if ($allowed === null) {
            return false;
        }

        return in_array($role, $allowed, true);
    }

    public static function canAccessPage(string $class, ?string $role): bool
    {
        if ($role === self::ROLE_ADMIN) {
            return true;
        }

        $allowed = self::PAGES[$class] ?? null;
        if ($allowed === null) {
            return false;
        }

        return in_array($role, $allowed, true);
    }
}

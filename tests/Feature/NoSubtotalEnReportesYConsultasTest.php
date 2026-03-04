<?php

namespace Tests\Feature;

use Tests\TestCase;

class NoSubtotalEnReportesYConsultasTest extends TestCase
{
    public function test_reportes_y_exportaciones_no_muestran_subtotal(): void
    {
        $paths = [
            'resources/views/filament/pages/reportes/compras-por-periodo.blade.php',
            'resources/views/filament/pages/reportes/ventas-por-periodo.blade.php',
            'resources/views/filament/pages/reportes/ventas-por-linea.blade.php',
            'resources/views/filament/pages/reportes/ordenes-por-estatus.blade.php',
            'resources/views/filament/pages/reportes/recepciones-por-proveedor.blade.php',

            'resources/views/exports/reportes/compras-por-periodo-pdf.blade.php',
            'resources/views/exports/reportes/ventas-por-periodo-pdf.blade.php',
            'resources/views/exports/reportes/ventas-por-linea-pdf.blade.php',
            'resources/views/exports/reportes/ordenes-por-estatus-pdf.blade.php',
            'resources/views/exports/reportes/recepciones-por-proveedor-pdf.blade.php',
        ];

        foreach ($paths as $path) {
            $content = file_get_contents(base_path($path));

            $this->assertNotFalse($content, "No se pudo leer el archivo: {$path}");
            $this->assertStringNotContainsString('Subtotal', $content, "El archivo aún muestra 'Subtotal': {$path}");
        }
    }

    public function test_consultas_no_muestran_columna_subtotal_en_modulos_no_facturas(): void
    {
        $paths = [
            'app/Filament/Resources/Cotizaciones/Tables/CotizacionesTable.php',
            'app/Filament/Resources/NotasVentaVenta/Tables/NotasVentaVentaTable.php',
            'app/Filament/Resources/NotasVentaRenta/Tables/NotasVentaRentaTable.php',
            'app/Filament/Resources/DevolucionesVenta/Tables/DevolucionesVentaTable.php',
            'app/Filament/Resources/DevolucionesRenta/Tables/DevolucionesRentaTable.php',
            'app/Filament/Resources/OrdenesCompra/Tables/OrdenesCompraTable.php',
            'app/Filament/Resources/RecepcionesCompra/Tables/RecepcionesCompraTable.php',
            'app/Filament/Resources/RequisicionesCompra/Tables/RequisicionesCompraTable.php',
        ];

        foreach ($paths as $path) {
            $content = file_get_contents(base_path($path));

            $this->assertNotFalse($content, "No se pudo leer el archivo: {$path}");
            $this->assertStringNotContainsString("TextColumn::make('subtotal')", $content, "El archivo aún define columna subtotal: {$path}");
        }
    }

    public function test_facturas_si_puede_mostrar_subtotal(): void
    {
        $content = file_get_contents(base_path('app/Filament/Resources/FacturasCfdi/Tables/FacturasCfdiTable.php'));

        $this->assertNotFalse($content);
        $this->assertStringContainsString("TextColumn::make('subtotal')", $content);
    }
}

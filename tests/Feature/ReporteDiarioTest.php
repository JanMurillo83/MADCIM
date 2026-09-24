<?php

namespace Tests\Feature;

use App\Filament\Pages\Reportes\ReporteDiario;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ReporteDiarioTest extends TestCase
{
    public function test_reporte_diario_expone_todas_las_secciones_y_exportaciones(): void
    {
        $page = app(ReporteDiario::class);
        $page->fecha = '2026-09-23';

        $reporte = $page->reporte();

        $this->assertSame('2026-09-23', $reporte['fecha']);
        $this->assertSame([
            'notas_renta',
            'notas_venta',
            'notas_envio',
            'notas_devolucion',
            'productos_vendidos',
            'productos_rentados',
            'pagos',
            'egresos',
            'notas_credito',
        ], array_keys($reporte['resumen']));
        $this->assertArrayHasKey('rentas', $reporte);
        $this->assertArrayHasKey('ventas', $reporte);
        $this->assertArrayHasKey('productos_vendidos', $reporte);
        $this->assertArrayHasKey('productos_rentados', $reporte);

        $this->assertInstanceOf(StreamedResponse::class, $page->exportPdf());

        $excel = $page->exportExcel();
        $this->assertInstanceOf(BinaryFileResponse::class, $excel);
        $this->assertFileExists($excel->getFile()->getPathname());
        @unlink($excel->getFile()->getPathname());
    }
}

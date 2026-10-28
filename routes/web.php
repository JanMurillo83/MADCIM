<?php

use App\Http\Controllers\AdmsController;
use App\Http\Controllers\CajaCierreTicketController;
use App\Http\Controllers\CajaMovimientoTicketController;
use App\Http\Controllers\CotizacionPdfController;
use App\Http\Controllers\DevolucionRentaController;
use App\Http\Controllers\NominaPdfController;
use App\Http\Controllers\NotaDevolucionRentaPdfController;
use App\Http\Controllers\NotaEnvioPdfController;
use App\Http\Controllers\NotaVentaRentaPdfController;
use App\Http\Controllers\NotaVentaVentaPdfController;
use App\Http\Controllers\OrdenCompraPdfController;
use App\Http\Controllers\PagoTicketController;
use App\Http\Controllers\RecepcionCompraPdfController;
use App\Http\Controllers\RequisicionCompraPdfController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::match(['get', 'post'], '/iclock/cdata', [AdmsController::class, 'cdata'])
    ->middleware('throttle:adms')
    ->name('adms.cdata');
Route::get('/iclock/getrequest', [AdmsController::class, 'getRequest'])
    ->middleware('throttle:adms')
    ->name('adms.getrequest');
Route::post('/iclock/devicecmd', [AdmsController::class, 'deviceCmd'])
    ->middleware('throttle:adms')
    ->name('adms.devicecmd');

// Rutas para impresión de cotizaciones
Route::middleware(['auth'])->group(function () {
    Route::get('/nomina-semanal/{periodo}/pdf', [NominaPdfController::class, 'nomina'])
        ->name('nomina-semanal.pdf');
    Route::get('/nomina-semanal/{periodo}/pdf/preview', [NominaPdfController::class, 'previewNomina'])
        ->name('nomina-semanal.pdf.preview');
    Route::get('/nomina-semanal/{nomina}/recibo', [NominaPdfController::class, 'recibo'])
        ->name('nomina-semanal.recibo');
    Route::get('/nomina-semanal/{nomina}/recibo/preview', [NominaPdfController::class, 'previewRecibo'])
        ->name('nomina-semanal.recibo.preview');

    Route::get('/pagos/{id}/ticket', PagoTicketController::class)
        ->name('pagos.ticket');
    Route::get('/caja-movimientos/{id}/devolucion-deposito/ticket', [CajaMovimientoTicketController::class, 'devolucionDeposito'])
        ->name('caja-movimientos.devolucion-deposito.ticket');
    Route::get('/cajas/{id}/cierre/ticket', CajaCierreTicketController::class)
        ->name('cajas.cierre.ticket');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/cotizaciones/{id}/pdf/ticket', [CotizacionPdfController::class, 'ticket'])
        ->name('cotizaciones.pdf.ticket');
    Route::get('/cotizaciones/{id}/pdf/carta', [CotizacionPdfController::class, 'carta'])
        ->name('cotizaciones.pdf.carta');
    Route::get('/cotizaciones/{id}/descargar/ticket', [CotizacionPdfController::class, 'descargarTicket'])
        ->name('cotizaciones.descargar.ticket');
    Route::get('/cotizaciones/{id}/descargar/carta', [CotizacionPdfController::class, 'descargarCarta'])
        ->name('cotizaciones.descargar.carta');
});

// Rutas para impresión de notas de venta renta
Route::middleware(['auth'])->group(function () {
    Route::get('/notas-venta-renta/{id}/preview', [NotaVentaRentaPdfController::class, 'preview'])
        ->name('notas-venta-renta.preview');
    Route::get('/notas-venta-renta/{id}/pdf/ticket', [NotaVentaRentaPdfController::class, 'ticket'])
        ->name('notas-venta-renta.pdf.ticket');
    Route::get('/notas-venta-renta/{id}/pdf/carta', [NotaVentaRentaPdfController::class, 'carta'])
        ->name('notas-venta-renta.pdf.carta');
    Route::get('/notas-venta-renta/{id}/cierre-devolucion-ticket', [NotaVentaRentaPdfController::class, 'cierreDevolucionTicket'])
        ->name('notas-venta-renta.cierre-devolucion-ticket');
    Route::get('/notas-venta-renta/{id}/descargar/ticket', [NotaVentaRentaPdfController::class, 'descargarTicket'])
        ->name('notas-venta-renta.descargar.ticket');
    Route::get('/notas-venta-renta/{id}/descargar/carta', [NotaVentaRentaPdfController::class, 'descargarCarta'])
        ->name('notas-venta-renta.descargar.carta');
    Route::get('/notas-venta-renta/{id}/hoja-embarque', [NotaVentaRentaPdfController::class, 'hojaEmbarque'])
        ->name('notas-venta-renta.hoja-embarque');
    Route::post('/notas-venta-renta/{id}/registrar-pago', [NotaVentaRentaPdfController::class, 'registrarPago'])
        ->name('notas-venta-renta.registrar-pago');

    // Rutas para devolución de renta
    Route::get('/notas-venta-renta/{id}/devolucion', [DevolucionRentaController::class, 'mostrarFormulario'])
        ->name('notas-venta-renta.devolucion');
    Route::post('/notas-venta-renta/{id}/devolucion', [DevolucionRentaController::class, 'procesarDevolucion'])
        ->name('notas-venta-renta.devolucion.procesar');
    Route::get('/notas-venta-renta/{id}/devolucion/pdf', [DevolucionRentaController::class, 'generarPDF'])
        ->name('notas-venta-renta.devolucion.pdf');
});

// Rutas para impresión de notas de envío
Route::middleware(['auth'])->group(function () {
    Route::get('/notas-envio/{id}/pdf/ticket', [NotaEnvioPdfController::class, 'ticket'])
        ->name('notas-envio.pdf.ticket');
});

// Rutas para impresión de notas de devolución renta
Route::middleware(['auth'])->group(function () {
    Route::get('/notas-devolucion-renta/{id}/pdf/ticket', [NotaDevolucionRentaPdfController::class, 'ticket'])
        ->name('notas-devolucion-renta.pdf.ticket');
});

// Rutas para impresión de notas de venta venta
Route::middleware(['auth'])->group(function () {
    Route::get('/notas-venta-venta/{id}/pdf/ticket', [NotaVentaVentaPdfController::class, 'ticket'])
        ->name('notas-venta-venta.pdf.ticket');
    Route::get('/notas-venta-venta/{id}/pdf/carta', [NotaVentaVentaPdfController::class, 'carta'])
        ->name('notas-venta-venta.pdf.carta');
    Route::get('/notas-venta-venta/{id}/descargar/ticket', [NotaVentaVentaPdfController::class, 'descargarTicket'])
        ->name('notas-venta-venta.descargar.ticket');
    Route::get('/notas-venta-venta/{id}/descargar/carta', [NotaVentaVentaPdfController::class, 'descargarCarta'])
        ->name('notas-venta-venta.descargar.carta');
});

// Rutas para impresión de requisiciones de compra
Route::middleware(['auth'])->group(function () {
    Route::get('/requisiciones-compra/{id}/pdf/ticket', [RequisicionCompraPdfController::class, 'ticket'])
        ->name('requisiciones-compra.pdf.ticket');
    Route::get('/requisiciones-compra/{id}/pdf/carta', [RequisicionCompraPdfController::class, 'carta'])
        ->name('requisiciones-compra.pdf.carta');
    Route::get('/requisiciones-compra/{id}/descargar/ticket', [RequisicionCompraPdfController::class, 'descargarTicket'])
        ->name('requisiciones-compra.descargar.ticket');
    Route::get('/requisiciones-compra/{id}/descargar/carta', [RequisicionCompraPdfController::class, 'descargarCarta'])
        ->name('requisiciones-compra.descargar.carta');
});

// Rutas para impresión de ordenes de compra
Route::middleware(['auth'])->group(function () {
    Route::get('/ordenes-compra/{id}/pdf/ticket', [OrdenCompraPdfController::class, 'ticket'])
        ->name('ordenes-compra.pdf.ticket');
    Route::get('/ordenes-compra/{id}/pdf/carta', [OrdenCompraPdfController::class, 'carta'])
        ->name('ordenes-compra.pdf.carta');
    Route::get('/ordenes-compra/{id}/descargar/ticket', [OrdenCompraPdfController::class, 'descargarTicket'])
        ->name('ordenes-compra.descargar.ticket');
    Route::get('/ordenes-compra/{id}/descargar/carta', [OrdenCompraPdfController::class, 'descargarCarta'])
        ->name('ordenes-compra.descargar.carta');
});

// Rutas para impresión de recepciones de compra
Route::middleware(['auth'])->group(function () {
    Route::get('/recepciones-compra/{id}/pdf/ticket', [RecepcionCompraPdfController::class, 'ticket'])
        ->name('recepciones-compra.pdf.ticket');
    Route::get('/recepciones-compra/{id}/pdf/carta', [RecepcionCompraPdfController::class, 'carta'])
        ->name('recepciones-compra.pdf.carta');
    Route::get('/recepciones-compra/{id}/descargar/ticket', [RecepcionCompraPdfController::class, 'descargarTicket'])
        ->name('recepciones-compra.descargar.ticket');
    Route::get('/recepciones-compra/{id}/descargar/carta', [RecepcionCompraPdfController::class, 'descargarCarta'])
        ->name('recepciones-compra.descargar.carta');
});
Route::get('/report-previews/{token}', function (string $token) {
    abort_unless(preg_match('/^[0-9a-f-]{36}$/', $token) === 1, 404);

    $path = "report-previews/{$token}.pdf";
    abort_unless(Storage::disk('local')->exists($path), 404);

    return response()->file(Storage::disk('local')->path($path), [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="reporte.pdf"',
    ]);
})->middleware('auth')->name('report-preview.file');

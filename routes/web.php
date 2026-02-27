<?php

use App\Http\Controllers\CotizacionPdfController;
use App\Http\Controllers\DevolucionRentaController;
use App\Http\Controllers\NotaVentaRentaPdfController;
use App\Http\Controllers\NotaEnvioPdfController;
use App\Http\Controllers\NotaVentaVentaPdfController;
use Illuminate\Support\Facades\Route;

// Rutas para impresión de cotizaciones
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
    Route::get('/notas-envio/{id}/cierre-devolucion-ticket', [NotaEnvioPdfController::class, 'cierreDevolucionTicket'])
        ->name('notas-envio.cierre-devolucion-ticket');
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

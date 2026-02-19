<?php

namespace App\Http\Controllers;

use App\Models\NotasVentaRenta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class NotaVentaRentaPdfController extends Controller
{
    /**
     * Muestra vista previa HTML con html2media para imprimir/descargar
     */
    public function preview($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

        return view('notas-venta-renta.preview', [
            'notaVenta' => $notaVenta
        ]);
    }

    /**
     * Genera PDF de nota de venta renta en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-renta.ticket', [
            'notaVenta' => $notaVenta
        ]);

        // Configurar tamaño de ticket (80mm x auto)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width

        return $pdf->stream("nota-venta-renta-{$notaVenta->serie}-{$notaVenta->folio}-ticket.pdf");
    }

    /**
     * Genera PDF de nota de venta renta en formato carta
     */
    public function carta($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-renta.carta', [
            'notaVenta' => $notaVenta
        ]);

        // Configurar tamaño carta
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("nota-venta-renta-{$notaVenta->serie}-{$notaVenta->folio}-carta.pdf");
    }

    /**
     * Descarga PDF de nota de venta renta en formato ticket
     */
    public function descargarTicket($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-renta.ticket', [
            'notaVenta' => $notaVenta
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->download("nota-venta-renta-{$notaVenta->serie}-{$notaVenta->folio}-ticket.pdf");
    }

    /**
     * Descarga PDF de nota de venta renta en formato carta
     */
    public function descargarCarta($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-renta.carta', [
            'notaVenta' => $notaVenta
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("nota-venta-renta-{$notaVenta->serie}-{$notaVenta->folio}-carta.pdf");
    }

    /**
     * Genera PDF de hoja de embarque con los registros de renta
     */
    public function hojaEmbarque($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'registrosRenta.producto'])->findOrFail($id);
        $registros = $notaVenta->registrosRenta;

        $pdf = Pdf::loadView('pdf.notas-venta-renta.hoja-embarque', [
            'notaVenta' => $notaVenta,
            'registros' => $registros,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("hoja-embarque-{$notaVenta->serie}-{$notaVenta->folio}.pdf");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NotasVentaVenta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class NotaVentaVentaPdfController extends Controller
{
    /**
     * Genera PDF de nota de venta venta en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $notaVenta = NotasVentaVenta::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-venta.ticket', [
            'notaVenta' => $notaVenta
        ]);

        // Configurar tamaño de ticket (80mm x auto)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width

        return $pdf->stream("nota-venta-venta-{$notaVenta->serie}-{$notaVenta->folio}-ticket.pdf");
    }

    /**
     * Genera PDF de nota de venta venta en formato carta
     */
    public function carta($id)
    {
        $notaVenta = NotasVentaVenta::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-venta.carta', [
            'notaVenta' => $notaVenta
        ]);

        // Configurar tamaño carta
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("nota-venta-venta-{$notaVenta->serie}-{$notaVenta->folio}-carta.pdf");
    }

    /**
     * Descarga PDF de nota de venta venta en formato ticket
     */
    public function descargarTicket($id)
    {
        $notaVenta = NotasVentaVenta::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-venta.ticket', [
            'notaVenta' => $notaVenta
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->download("nota-venta-venta-{$notaVenta->serie}-{$notaVenta->folio}-ticket.pdf");
    }

    /**
     * Descarga PDF de nota de venta venta en formato carta
     */
    public function descargarCarta($id)
    {
        $notaVenta = NotasVentaVenta::with(['cliente', 'partidas'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-venta.carta', [
            'notaVenta' => $notaVenta
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("nota-venta-venta-{$notaVenta->serie}-{$notaVenta->folio}-carta.pdf");
    }
}

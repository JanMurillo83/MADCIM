<?php

namespace App\Http\Controllers;

use App\Models\NotasVentaVenta;
use App\Support\TicketPaperSize;
use Barryvdh\DomPDF\Facade\Pdf;

class NotaVentaVentaPdfController extends Controller
{
    /**
     * Genera PDF de nota de venta venta en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $notaVenta = NotasVentaVenta::with(['cliente', 'partidas', 'pagos'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-venta.ticket', [
            'notaVenta' => $notaVenta
        ]);

        $pdf->setPaper([0, 0, 226.77, $this->altoTicket($notaVenta)], 'portrait');

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
        $notaVenta = NotasVentaVenta::with(['cliente', 'partidas', 'pagos'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-venta-venta.ticket', [
            'notaVenta' => $notaVenta
        ]);

        $pdf->setPaper([0, 0, 226.77, $this->altoTicket($notaVenta)], 'portrait');

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

    private function altoTicket(NotasVentaVenta $notaVenta): float
    {
        $altoFijo = 280;
        if ($notaVenta->condicion_pago === 'credito') {
            $altoFijo += 40;
        } else {
            $altoFijo += max(0, $notaVenta->pagos->count() - 1) * 22;
        }

        if ($notaVenta->observaciones) {
            $altoFijo += max(1, (int) ceil(mb_strlen($notaVenta->observaciones) / 38)) * 11;
        }

        return TicketPaperSize::heightForItems(
            $notaVenta->partidas,
            $altoFijo,
            charactersPerLine: 38,
            lineHeight: 11,
            rowSpacing: 18,
        );
    }
}

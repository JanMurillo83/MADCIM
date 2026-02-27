<?php

namespace App\Http\Controllers;

use App\Models\NotaEnvio;
use App\Models\NotasVentaVenta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class NotaEnvioPdfController extends Controller
{
    /**
     * Genera PDF de nota de envío en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $notaEnvio = NotaEnvio::with(['cliente', 'direccionEntrega', 'notaVentaRenta', 'partidas.producto'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.notas-envio.ticket', [
            'notaEnvio' => $notaEnvio,
        ]);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->stream("nota-envio-{$notaEnvio->serie}-{$notaEnvio->folio}-ticket.pdf");
    }

    /**
     * Muestra ticket de resumen de cierre de devolución
     */
    public function cierreDevolucionTicket($id)
    {
        $notaEnvio = NotaEnvio::with(['cliente', 'notaVentaRenta', 'partidas.producto'])->findOrFail($id);
        $notaRenta = $notaEnvio->notaVentaRenta;

        $items = $notaEnvio->partidas()->with('producto')->get();

        $deposito = (float)($notaRenta->deposito ?? 0);
        $totalDescuento = 0;

        foreach ($items as $item) {
            $faltante = (float)$item->cantidad - (float)$item->cantidad_devuelta;
            if ($faltante > 0) {
                $precioVenta = $item->producto ? (float)$item->producto->precio_venta : 0;
                $totalDescuento += $faltante * $precioVenta;
            }
        }

        $impuestosFaltantes = $totalDescuento > 0 ? round($totalDescuento * 0.16, 2) : 0;
        $importeDevolver = max(0, $deposito - $totalDescuento);

        // Buscar nota de venta-venta generada por faltantes (la más reciente del cliente)
        $notaVentaVenta = null;
        if ($totalDescuento > 0) {
            $notaVentaVenta = NotasVentaVenta::where('cliente_id', $notaRenta->cliente_id)
                ->where('serie', 'M')
                ->latest()
                ->first();
        }

        $observaciones = session('cierre_devolucion_observaciones_' . $id, null);

        return view('notas-envio.cierre-devolucion-ticket', [
            'notaEnvio' => $notaEnvio,
            'notaRenta' => $notaRenta,
            'items' => $items,
            'deposito' => $deposito,
            'totalDescuento' => $totalDescuento,
            'impuestosFaltantes' => $impuestosFaltantes,
            'importeDevolver' => $importeDevolver,
            'notaVentaVenta' => $notaVentaVenta,
            'observaciones' => $observaciones,
        ]);
    }
}

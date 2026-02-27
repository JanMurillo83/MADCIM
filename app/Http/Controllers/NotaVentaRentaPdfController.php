<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\NotasVentaRenta;
use App\Models\Pagos;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotaVentaRentaPdfController extends Controller
{
    /**
     * Muestra vista previa HTML con html2media para imprimir/descargar
     */
    public function preview($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

        return view('notas-venta-renta.preview', [
            'notaVenta' => $notaVenta
        ]);
    }

    /**
     * Genera PDF de nota de venta renta en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

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
        $notaVenta = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

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
        $notaVenta = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

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
        $notaVenta = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'partidas', 'registrosRenta.producto'])->findOrFail($id);

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

    /**
     * Registra pago de contado desde la vista preview
     */
    public function registrarPago(Request $request, $id)
    {
        $request->validate([
            'importe' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string',
        ]);

        $notaVenta = NotasVentaRenta::findOrFail($id);
        $userId = Auth::id();
        $metodoPago = $request->input('metodo_pago', 'Efectivo');
        $importe = (float) $request->input('importe');

        $cajaId = null;
        if ($metodoPago === 'Efectivo') {
            $caja = Caja::where('estatus', 'Abierta')
                ->where('usuario_apertura_id', $userId)
                ->first();
            $cajaId = $caja?->id;
        }

        Pagos::create([
            'documento_tipo' => 'notas_venta_renta',
            'documento_id' => $notaVenta->id,
            'cliente_id' => $notaVenta->cliente_id,
            'fecha_pago' => now(),
            'forma_pago' => $metodoPago,
            'metodo_pago' => $metodoPago,
            'importe' => $importe,
            'referencia' => 'Pago de contado al crear nota',
            'user_id' => $userId,
            'caja_id' => $cajaId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado correctamente.',
            'saldo_pendiente' => $notaVenta->fresh()->saldo_pendiente,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Configuracion;
use App\Models\DevolucionesRenta;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Services\CierreDevolucionRentaService;
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
        return view('notas-venta-renta.preview', $this->datosImpresion($id));
    }

    /**
     * Genera PDF de nota de venta renta en formato ticket (80mm)
     */
    public function ticket($id)
    {
        $datos = $this->datosImpresion($id);
        $notaVenta = $datos['notaVenta'];

        $pdf = Pdf::loadView('pdf.notas-venta-renta.ticket', $datos);

        // Configurar tamaño de ticket (80mm x auto)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width

        return $pdf->stream("nota-venta-renta-{$notaVenta->serie}-{$notaVenta->folio}-ticket.pdf");
    }

    /**
     * Genera PDF de nota de venta renta en formato carta
     */
    public function carta($id)
    {
        $datos = $this->datosImpresion($id);
        $notaVenta = $datos['notaVenta'];

        $pdf = Pdf::loadView('pdf.notas-venta-renta.carta', $datos);

        // Configurar tamaño carta
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("nota-venta-renta-{$notaVenta->serie}-{$notaVenta->folio}-carta.pdf");
    }

    public function cierreDevolucionTicket($id)
    {
        $notaVenta = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'notasEnvio'])->findOrFail($id);
        $resumen = app(CierreDevolucionRentaService::class)->obtenerResumen($notaVenta);

        $notaVentaVenta = NotasVentaVenta::query()
            ->where('documento_origen_id', $notaVenta->id)
            ->latest()
            ->first();

        $devolucion = DevolucionesRenta::query()
            ->where('documento_origen_id', $notaVenta->id)
            ->latest()
            ->first();

        $observaciones = session('cierre_devolucion_observaciones_nvr_' . $notaVenta->id, null);

        return view('notas-venta-renta.cierre-devolucion-ticket', [
            'notaVenta' => $notaVenta,
            'resumen' => $resumen,
            'notaVentaVenta' => $notaVentaVenta,
            'devolucion' => $devolucion,
            'observaciones' => $observaciones,
        ]);
    }

    /**
     * Descarga PDF de nota de venta renta en formato ticket
     */
    public function descargarTicket($id)
    {
        $datos = $this->datosImpresion($id);
        $notaVenta = $datos['notaVenta'];

        $pdf = Pdf::loadView('pdf.notas-venta-renta.ticket', $datos);

        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        return $pdf->download("nota-venta-renta-{$notaVenta->serie}-{$notaVenta->folio}-ticket.pdf");
    }

    /**
     * Descarga PDF de nota de venta renta en formato carta
     */
    public function descargarCarta($id)
    {
        $datos = $this->datosImpresion($id);
        $notaVenta = $datos['notaVenta'];

        $pdf = Pdf::loadView('pdf.notas-venta-renta.carta', $datos);

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
            'importe_recibido' => 'nullable|numeric|min:0',
        ]);

        $notaVenta = NotasVentaRenta::findOrFail($id);
        $userId = Auth::id();
        $metodoPago = $request->input('metodo_pago', 'Efectivo');
        $formaPago = match ($metodoPago) {
            'Efectivo' => '01',
            'Cheque' => '02',
            'Transferencia' => '03',
            'Tarjeta',
            'Tarjeta Crédito' => '04',
            'Tarjeta Debito',
            'Tarjeta Débito' => '28',
            default => $metodoPago,
        };
        $importe = (float) $request->input('importe');
        $importeRecibido = $formaPago === '01'
            ? (float) $request->input('importe_recibido', $importe)
            : $importe;

        if ($formaPago === '01' && $importeRecibido < $importe) {
            return response()->json([
                'success' => false,
                'message' => 'El pago recibido no puede ser menor al total de la nota.',
            ], 422);
        }

        $cajaId = null;
        if ($formaPago === '01') {
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
            'forma_pago' => $formaPago,
            'importe' => $importe,
            'importe_recibido' => $importeRecibido,
            'cambio' => $formaPago === '01' ? round($importeRecibido - $importe, 2) : 0,
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

    private function datosImpresion($id): array
    {
        $notaVenta = NotasVentaRenta::with([
            'cliente',
            'direccionEntrega',
            'partidas',
            'registrosRenta.producto',
            'pagos',
        ])->findOrFail($id);

        return [
            'notaVenta' => $notaVenta,
            'configuracion' => Configuracion::first(),
        ];
    }
}

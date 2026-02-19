<?php

namespace App\Http\Controllers;

use App\Models\NotasVentaRenta;
use App\Models\Productos;
use App\Models\RegistroRenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevolucionRentaController extends Controller
{
    public function mostrarFormulario($id)
    {
        $nota = NotasVentaRenta::with(['cliente', 'registrosRenta.producto'])->findOrFail($id);

        // Obtener items activos de la renta
        $itemsRentados = $nota->registrosRenta()->where('estado', 'Activo')->get();

        if ($itemsRentados->isEmpty()) {
            return redirect()->back()->with('error', 'No hay items activos para devolver en esta nota.');
        }

        return view('devoluciones.renta.formulario', compact('nota', 'itemsRentados'));
    }

    public function procesarDevolucion(Request $request, $id)
    {
        $nota = NotasVentaRenta::with(['cliente', 'registrosRenta.producto'])->findOrFail($id);

        $request->validate([
            'items' => 'required|array',
            'items.*.cantidad_devuelta' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalDepositoInicial = 0;
            $totalDescuento = 0;
            $itemsDevolucion = [];

            foreach ($request->items as $registroId => $item) {
                $registro = RegistroRenta::with('producto')->findOrFail($registroId);
                $cantidadDevuelta = (int) $item['cantidad_devuelta'];
                $cantidadFaltante = $registro->cantidad - $cantidadDevuelta;

                // Calcular descuento por faltantes
                $descuento = 0;
                if ($cantidadFaltante > 0) {
                    $producto = Productos::find($registro->producto_id);
                    $descuento = $cantidadFaltante * ($producto->precio_venta ?? 0);
                    $totalDescuento += $descuento;
                }

                $itemsDevolucion[] = [
                    'producto' => $registro->producto->descripcion,
                    'cantidad_rentada' => $registro->cantidad,
                    'cantidad_devuelta' => $cantidadDevuelta,
                    'cantidad_faltante' => $cantidadFaltante,
                    'descuento' => $descuento,
                ];

                // Actualizar estado del registro
                $registro->update(['estado' => 'Devuelto']);

                $totalDepositoInicial += $registro->importe_deposito;
            }

            // Calcular depósito a devolver
            $depositoADevolver = $totalDepositoInicial - $totalDescuento;

            // Actualizar estatus de la nota
            $nota->update(['estatus' => 'Devuelta']);

            DB::commit();

            // Guardar datos en sesión para el PDF
            session([
                'devolucion_data' => [
                    'nota' => $nota,
                    'items' => $itemsDevolucion,
                    'deposito_inicial' => $totalDepositoInicial,
                    'total_descuento' => $totalDescuento,
                    'deposito_devolver' => $depositoADevolver,
                ]
            ]);

            return redirect()->route('notas-venta-renta.devolucion.pdf', $id);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al procesar la devolución: ' . $e->getMessage());
        }
    }

    public function generarPDF($id)
    {
        $data = session('devolucion_data');

        if (!$data) {
            return redirect()->back()->with('error', 'No hay datos de devolución disponibles.');
        }

        return view('pdf.devolucion-renta', $data);
    }
}

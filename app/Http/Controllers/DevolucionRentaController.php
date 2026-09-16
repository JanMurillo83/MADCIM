<?php

namespace App\Http\Controllers;

use App\Models\NotasVentaRenta;
use App\Models\NotaDevolucionRenta;
use App\Models\NotaDevolucionRentaPartida;
use App\Models\RegistroRenta;
use App\Services\InventarioMovimientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevolucionRentaController extends Controller
{
    public function mostrarFormulario($id)
    {
        $nota = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'registrosRenta.producto'])->findOrFail($id);

        $itemsRentados = $this->obtenerItemsPendientes($nota);

        if ($itemsRentados->isEmpty()) {
            return redirect()->back()->with('error', 'No hay productos activos para devolver en esta nota.');
        }

        return view('devoluciones.renta.formulario', compact('nota', 'itemsRentados'));
    }

    public function procesarDevolucion(Request $request, $id)
    {
        $nota = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'registrosRenta.producto'])->findOrFail($id);

        $request->validate([
            'items' => 'required|array',
            'items.*.cantidad_devuelta' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalDevueltoAhora = 0;
            $itemsDevolucion = [];

            foreach ($request->items as $registroId => $item) {
                $registro = RegistroRenta::with('producto')
                    ->whereKey($registroId)
                    ->when(
                        $nota->direccion_entrega_id,
                        fn ($query) => $query
                            ->where('cliente_id', $nota->cliente_id)
                            ->whereHas('notaVentaRenta', fn ($notaQuery) => $notaQuery->where('direccion_entrega_id', $nota->direccion_entrega_id)),
                        fn ($query) => $query->where('nota_venta_renta_id', $nota->id),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();
                $cantidadActual = (float) ($registro->cantidad_devuelta ?? 0);
                $pendiente = max(0, (float) $registro->cantidad - $cantidadActual);
                $cantidadAhora = (float) $item['cantidad_devuelta'];

                if ($cantidadAhora < 0 || $cantidadAhora > $pendiente) {
                    throw new \RuntimeException('La cantidad devuelta supera el pendiente del producto ' . ($registro->producto?->descripcion ?? 'seleccionado') . '.');
                }

                if ($cantidadAhora <= 0) {
                    continue;
                }

                $nuevaCantidadDevuelta = $cantidadActual + $cantidadAhora;
                $registro->update([
                    'cantidad_devuelta' => $nuevaCantidadDevuelta,
                    'estado' => $nuevaCantidadDevuelta >= (float) $registro->cantidad ? 'Devuelto' : 'Activo',
                ]);

                InventarioMovimientoService::entrada(
                    productoId: $registro->producto_id,
                    cantidad: $cantidadAhora,
                    motivo: 'Devolución de renta ' . ($nota->serie ?? '') . ($nota->folio ?? $nota->id),
                    documentoReferencia: ($nota->serie ?? '') . ($nota->folio ?? $nota->id),
                );

                $itemsDevolucion[] = [
                    'producto_id' => $registro->producto_id,
                    'producto' => $registro->producto?->descripcion ?? 'Producto',
                    'cantidad_rentada' => $registro->cantidad,
                    'cantidad_devuelta' => $nuevaCantidadDevuelta,
                    'cantidad_devuelta_ahora' => $cantidadAhora,
                    'cantidad_faltante' => max(0, (float) $registro->cantidad - $nuevaCantidadDevuelta),
                ];

                $totalDevueltoAhora += $cantidadAhora;
            }

            if ($totalDevueltoAhora <= 0) {
                throw new \RuntimeException('Capture al menos una cantidad devuelta mayor que cero.');
            }

            $notaDevolucion = NotaDevolucionRenta::create([
                'serie' => 'NDR',
                'folio_interno' => 'DEV-' . now()->format('YmdHis'),
                'nota_venta_renta_id' => $nota->id,
                'cliente_id' => $nota->cliente_id,
                'direccion_entrega_id' => $nota->direccion_entrega_id,
                'fecha_emision' => now()->toDateString(),
                'estatus' => 'Aplicada',
                'observaciones' => 'Devolución registrada desde la consulta de devoluciones.',
                'aplicada_en' => now(),
                'user_id' => Auth::id(),
            ]);

            foreach ($itemsDevolucion as $item) {
                $registro = $this->buscarRegistroPorProducto(
                    $nota,
                    (int) $item['producto_id'],
                );

                NotaDevolucionRentaPartida::create([
                    'nota_devolucion_renta_id' => $notaDevolucion->id,
                    'producto_id' => $item['producto_id'],
                    'descripcion' => $item['producto'],
                    'cantidad_enviada' => $item['cantidad_rentada'],
                    'cantidad_devuelta' => $item['cantidad_devuelta'] - $item['cantidad_devuelta_ahora'],
                    'cantidad_a_devolver' => $item['cantidad_devuelta_ahora'],
                    'cantidad_aplicada' => $item['cantidad_devuelta_ahora'],
                    'observaciones' => $registro?->observaciones,
                ]);
            }

            DB::commit();

            // Guardar datos en sesión para el PDF
            session([
                'devolucion_data' => [
                    'nota' => $nota,
                    'items' => $itemsDevolucion,
                    'deposito_inicial' => (float) ($nota->deposito ?? 0),
                    'total_descuento' => 0,
                    'deposito_devolver' => 0,
                    'total_devuelto_ahora' => $totalDevueltoAhora,
                    'nota_devolucion_id' => $notaDevolucion->id,
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

    private function obtenerItemsPendientes(NotasVentaRenta $nota)
    {
        return RegistroRenta::query()
            ->with('producto')
            ->when(
                $nota->direccion_entrega_id,
                fn ($query) => $query
                    ->where('cliente_id', $nota->cliente_id)
                    ->whereHas('notaVentaRenta', fn ($notaQuery) => $notaQuery->where('direccion_entrega_id', $nota->direccion_entrega_id)),
                fn ($query) => $query->where('nota_venta_renta_id', $nota->id),
            )
            ->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')
            ->orderBy('id')
            ->get();
    }

    private function buscarRegistroPorProducto(NotasVentaRenta $nota, int $productoId): ?RegistroRenta
    {
        return RegistroRenta::query()
            ->where('cliente_id', $nota->cliente_id)
            ->where('producto_id', $productoId)
            ->when(
                $nota->direccion_entrega_id,
                fn ($query) => $query->whereHas('notaVentaRenta', fn ($notaQuery) => $notaQuery->where('direccion_entrega_id', $nota->direccion_entrega_id)),
                fn ($query) => $query->where('nota_venta_renta_id', $nota->id),
            )
            ->latest('id')
            ->first();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NotasVentaRenta;
use App\Models\NotaDevolucionRenta;
use App\Models\NotaDevolucionRentaPartida;
use App\Models\RegistroRenta;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DevolucionRentaController extends Controller
{
    public function mostrarFormulario($id)
    {
        $nota = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'registrosRenta.producto'])->findOrFail($id);

        $itemsRentados = $this->obtenerItemsPendientes($nota);

        if ($itemsRentados->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('No hay productos pendientes de devolución')
                ->body('Actualice la consulta y revise la sucursal, la obra y las devoluciones registradas.')
                ->send();

            return redirect()->back()->with('error', 'No hay productos activos para devolver en esta nota.');
        }

        return view('devoluciones.renta.formulario', compact('nota', 'itemsRentados'));
    }

    public function procesarDevolucion(Request $request, $id)
    {
        $nota = NotasVentaRenta::with(['cliente', 'direccionEntrega', 'registrosRenta.producto'])->findOrFail($id);

        $request->validate([
            'folio_interno' => 'required|string|max:100|unique:notas_devolucion_renta,folio_interno',
            'items' => 'required|array',
            'items.*.cantidad_devuelta' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalDevueltoAhora = 0;
            $itemsDevolucion = [];
            $notaEnvioOrigenIds = [];
            $notaIdsObra = $this->notasDeObra($nota)->pluck('id');

            foreach ($request->items as $registroId => $item) {
                $registro = RegistroRenta::with('producto')
                    ->whereKey($registroId)
                    ->whereIn('nota_venta_renta_id', $notaIdsObra)
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

                $notaEnvioId = $registro->nota_envio_id
                    ?? $registro->notaEnvioPartida?->notaEnvio?->id
                    ?? null;

                if ($notaEnvioId) {
                    $notaEnvioOrigenIds[] = (int) $notaEnvioId;
                }

                $nuevaCantidadDevuelta = $cantidadActual + $cantidadAhora;
                $itemsDevolucion[] = [
                    'producto_id' => $registro->producto_id,
                    'producto' => $registro->producto?->descripcion ?? 'Producto',
                    'cantidad_rentada' => $registro->cantidad,
                    'cantidad_devuelta' => $nuevaCantidadDevuelta,
                    'cantidad_devuelta_ahora' => $cantidadAhora,
                    'cantidad_faltante' => max(0, (float) $registro->cantidad - $nuevaCantidadDevuelta),
                    'registro_renta_id' => $registro->id,
                    'nota_envio_id' => $notaEnvioId,
                    'nota_envio_partida_id' => $registro->nota_envio_partida_id,
                    'observaciones' => $registro->observaciones,
                ];

                $totalDevueltoAhora += $cantidadAhora;
            }

            if ($totalDevueltoAhora <= 0) {
                throw new \RuntimeException('Capture al menos una cantidad devuelta mayor que cero.');
            }

            $notaDevolucion = NotaDevolucionRenta::create([
                'serie' => 'NDR',
                'folio_interno' => $request->string('folio_interno')->trim()->toString(),
                'nota_envio_id' => count(array_unique($notaEnvioOrigenIds)) === 1 ? $notaEnvioOrigenIds[0] : null,
                'nota_venta_renta_id' => $nota->id,
                'cliente_id' => $nota->cliente_id,
                'direccion_entrega_id' => $nota->direccion_entrega_id,
                'fecha_emision' => now()->toDateString(),
                    'estatus' => 'Borrador',
                'observaciones' => 'Devolución registrada desde la consulta de devoluciones.',
                'aplicada_en' => now(),
                'user_id' => Auth::id(),
            ]);

            foreach ($itemsDevolucion as $item) {
                NotaDevolucionRentaPartida::create([
                    'nota_devolucion_renta_id' => $notaDevolucion->id,
                    'registro_renta_id' => $item['registro_renta_id'],
                    'nota_envio_partida_id' => $item['nota_envio_partida_id'],
                    'producto_id' => $item['producto_id'],
                    'descripcion' => $item['producto'],
                    'cantidad_enviada' => $item['cantidad_rentada'],
                    'cantidad_devuelta' => $item['cantidad_devuelta'] - $item['cantidad_devuelta_ahora'],
                    'cantidad_a_devolver' => $item['cantidad_devuelta_ahora'],
                    'cantidad_aplicada' => 0,
                    'observaciones' => $item['observaciones'],
                ]);
            }

            $notaDevolucion->aplicarCantidadesRecogidas();

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

        } catch (\Throwable $e) {
            DB::rollBack();
            $referenciaError = (string) Str::uuid();
            Log::error('Falló el procesamiento de una devolución de renta.', [
                'referencia_error' => $referenciaError,
                'nota_venta_renta_id' => $nota->id,
                'cliente_id' => $nota->cliente_id,
                'direccion_entrega_id' => $nota->direccion_entrega_id,
                'sucursal_id' => $nota->sucursal_id,
                'user_id' => Auth::id(),
                'folio_interno' => $request->input('folio_interno'),
                'items' => $request->input('items'),
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
            ]);

            $mensaje = $e instanceof \DomainException
                ? $e->getMessage()
                : "No se pudo aplicar la devolución. Referencia de error: {$referenciaError}.";

            return redirect()->back()->with('error', $mensaje);
        }
    }

    public function generarPDF($id)
    {
        $data = session('devolucion_data');

        if (!$data) {
            return redirect()->back()->with('error', 'No hay datos de devolución disponibles.');
        }

        $data['notaDevolucion'] = NotaDevolucionRenta::find($data['nota_devolucion_id'] ?? null);

        return view('pdf.devolucion-renta', $data);
    }

    private function obtenerItemsPendientes(NotasVentaRenta $nota)
    {
        return RegistroRenta::query()
            ->with('producto')
            ->whereIn('nota_venta_renta_id', $this->notasDeObra($nota)->select('id'))
            ->whereRaw('COALESCE(cantidad_devuelta, 0) < cantidad')
            ->orderBy('id')
            ->get();
    }

    private function notasDeObra(NotasVentaRenta $nota): Builder
    {
        return NotasVentaRenta::query()
            ->where('cliente_id', $nota->cliente_id)
            ->where('sucursal_id', $nota->sucursal_id)
            ->when(
                $nota->direccion_entrega_id,
                fn (Builder $query) => $query->where('direccion_entrega_id', $nota->direccion_entrega_id),
                fn (Builder $query) => $query->whereNull('direccion_entrega_id'),
            )
            ->where('estatus', '!=', 'Cancelada')
            ->whereHas('notasEnvio', fn (Builder $query) => $query->whereIn('estatus', ['Enviada', 'Entregada']));
    }

}

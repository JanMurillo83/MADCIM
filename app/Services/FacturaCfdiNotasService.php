<?php

namespace App\Services;

use App\Enums\TipoNotaRenta;
use App\Models\FacturaCfdiPartidas;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\NotaVentaRentaPartidas;
use App\Models\NotaVentaVentaPartidas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class FacturaCfdiNotasService
{
    /**
     * @return array<int, string>
     */
    public function opcionesNotasVenta(?int $clienteId, ?int $sucursalId): array
    {
        if (! $clienteId || ! $sucursalId) {
            return [];
        }

        return $this->queryNotasVenta($clienteId, $sucursalId)
            ->orderByDesc('fecha_emision')
            ->get()
            ->mapWithKeys(static function (NotasVentaVenta $nota): array {
                $label = trim(($nota->serie ?? '').($nota->folio ?? ''));
                $label .= ' | '.optional($nota->fecha_emision)->format('Y-m-d');
                $label .= ' | $'.number_format((float) $nota->total, 2);

                return [$nota->id => $label];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function opcionesNotasRenta(?int $clienteId, ?int $sucursalId): array
    {
        if (! $clienteId || ! $sucursalId) {
            return [];
        }

        return $this->queryNotasRenta($clienteId, $sucursalId)
            ->orderByDesc('fecha_emision')
            ->get()
            ->mapWithKeys(static function (NotasVentaRenta $nota): array {
                $label = trim(($nota->serie ?? '').($nota->folio ?? ''));
                $label .= ' | '.optional($nota->fecha_emision)->format('Y-m-d');
                $label .= ' | $'.number_format((float) $nota->total, 2);

                return [$nota->id => $label];
            })
            ->all();
    }

    /**
     * @param  array<int, mixed>  $notasVentaIds
     * @param  array<int, mixed>  $notasRentaIds
     * @return array<int, array<string, mixed>>
     */
    public function construirPartidas(
        array $notasVentaIds,
        array $notasRentaIds,
        ?int $clienteId,
        ?int $sucursalId,
    ): array {
        $notasVentaIds = $this->normalizarIds($notasVentaIds);
        $notasRentaIds = $this->normalizarIds($notasRentaIds);

        if ($notasVentaIds === [] && $notasRentaIds === []) {
            return [];
        }

        if (! $clienteId || ! $sucursalId) {
            throw ValidationException::withMessages([
                'data.notas_venta_ids' => 'Selecciona el cliente y la sucursal antes de elegir notas.',
            ]);
        }

        $notasVenta = $notasVentaIds === []
            ? collect()
            : $this->queryNotasVenta($clienteId, $sucursalId)
                ->with('partidas.producto')
                ->whereIn('id', $notasVentaIds)
                ->get();

        if ($notasVenta->count() !== count($notasVentaIds)) {
            throw ValidationException::withMessages([
                'data.notas_venta_ids' => 'Una o más notas de venta ya no están disponibles para facturar.',
            ]);
        }

        $notasRenta = $notasRentaIds === []
            ? collect()
            : $this->queryNotasRenta($clienteId, $sucursalId)
                ->with('partidas.producto')
                ->whereIn('id', $notasRentaIds)
                ->get();

        if ($notasRenta->count() !== count($notasRentaIds)) {
            throw ValidationException::withMessages([
                'data.notas_renta_ids' => 'Una o más notas de renta ya no están disponibles para facturar.',
            ]);
        }

        $partidas = [];

        foreach ($notasVenta as $nota) {
            foreach ($nota->partidas as $partida) {
                $partidas[] = $this->mapearPartidaVenta($nota, $partida);
            }
        }

        foreach ($notasRenta as $nota) {
            foreach ($nota->partidas as $partida) {
                $partidas[] = $this->mapearPartidaRenta($nota, $partida);
            }
        }

        return $partidas;
    }

    private function queryNotasVenta(int $clienteId, int $sucursalId): Builder
    {
        $query = NotasVentaVenta::query()
            ->where('cliente_id', $clienteId)
            ->where('sucursal_id', $sucursalId)
            ->where(function ($query): void {
                $query->whereNull('estatus')
                    ->orWhereRaw('LOWER(estatus) <> ?', ['cancelada']);
            });

        $notasFacturadas = $this->idsNotasFacturadas('NVV');
        if ($notasFacturadas !== []) {
            $query->whereNotIn('id', $notasFacturadas);
        }

        return $query;
    }

    private function queryNotasRenta(int $clienteId, int $sucursalId): Builder
    {
        $query = NotasVentaRenta::query()
            ->where('cliente_id', $clienteId)
            ->where('sucursal_id', $sucursalId)
            ->where(function ($query): void {
                $query->whereNull('estatus')
                    ->orWhereRaw('LOWER(estatus) <> ?', ['cancelada']);
            });

        $notasFacturadas = $this->idsNotasFacturadas('NVR');
        if ($notasFacturadas !== []) {
            $query->whereNotIn('id', $notasFacturadas);
        }

        return $query;
    }

    /**
     * @return array<int, int>
     */
    private function idsNotasFacturadas(string $prefijo): array
    {
        return FacturaCfdiPartidas::query()
            ->whereHas('documento', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereNull('estatus')
                        ->orWhereRaw('LOWER(estatus) <> ?', ['cancelada']);
                });
            })
            ->where('no_identificacion', 'like', $prefijo.':%')
            ->pluck('no_identificacion')
            ->map(static function (?string $identificador): ?int {
                $segmentos = explode(':', (string) $identificador);
                $id = (int) ($segmentos[1] ?? 0);

                return $id > 0 ? $id : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function normalizarIds(array $ids): array
    {
        return collect($ids)
            ->map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearPartidaVenta(NotasVentaVenta $nota, NotaVentaVentaPartidas $partida): array
    {
        $subtotal = (float) $partida->subtotal;
        $impuestos = (float) $partida->impuestos;
        $cantidad = (float) $partida->cantidad;

        return [
            'cantidad' => $cantidad,
            'item' => (string) $partida->item,
            'clave_prod_serv' => $partida->producto?->clave_prod_serv,
            'no_identificacion' => "NVV:{$nota->id}:{$partida->id}",
            'clave_unidad' => $partida->producto?->clave_unidad,
            'unidad' => $partida->producto?->unidad_sat,
            'descripcion' => $partida->descripcion,
            'objeto_imp' => $impuestos > 0 ? '02' : ($partida->producto?->objeto_imp ?? '01'),
            'valor_unitario' => $cantidad > 0 ? $subtotal / $cantidad : (float) $partida->valor_unitario,
            'subtotal' => $subtotal,
            'descuento' => 0,
            'impuestos' => $impuestos,
            'total' => $subtotal + $impuestos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearPartidaRenta(NotasVentaRenta $nota, NotaVentaRentaPartidas $partida): array
    {
        $subtotal = (float) $partida->subtotal;
        $impuestos = (float) $partida->impuestos;
        $tipoRenta = TipoNotaRenta::tryFrom((string) ($partida->tipo_nota_renta ?? $nota->tipo_nota_renta));
        $cantidad = $tipoRenta?->esMaderaM2()
            ? (float) ($partida->metros_m2 ?? $nota->metros_m2)
            : (float) $partida->cantidad;
        $cantidad = $cantidad > 0 ? $cantidad : 1;

        return [
            'cantidad' => $cantidad,
            'item' => (string) $partida->item,
            'clave_prod_serv' => $partida->producto?->clave_prod_serv,
            'no_identificacion' => "NVR:{$nota->id}:{$partida->id}",
            'clave_unidad' => $partida->producto?->clave_unidad,
            'unidad' => $partida->producto?->unidad_sat,
            'descripcion' => $partida->descripcion,
            'objeto_imp' => $impuestos > 0 ? '02' : ($partida->producto?->objeto_imp ?? '01'),
            'valor_unitario' => $subtotal / $cantidad,
            'subtotal' => $subtotal,
            'descuento' => 0,
            'impuestos' => $impuestos,
            'total' => $subtotal + $impuestos,
        ];
    }
}

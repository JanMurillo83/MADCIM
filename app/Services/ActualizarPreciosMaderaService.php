<?php

namespace App\Services;

use App\Models\HistorialPreciosMadera;
use App\Models\Productos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ActualizarPreciosMaderaService
{
    public const LARGO_ESTANDAR_CM = 250.0;

    /** @var array<string, string> */
    public const BASES = [
        'POLINENTERO' => 'Polin entero',
        'BARROTE-ENTERO' => 'Barrote entero',
        'TABLA30-ENTERA' => 'Tabla de 30 entera',
        'TABLA25-ENTERA' => 'Tabla de 25 entera',
        'TABLA20-ENTERA' => 'Tabla de 20 entera',
        'TABLA15-ENTERA' => 'Tabla de 15 entera',
        'DUELA10-ENTERA' => 'Duela entera',
    ];

    /** @return array<string, array<string, mixed>> */
    public function obtenerPreciosBase(): array
    {
        $productos = Productos::query()
            ->whereIn('clave', array_keys(self::BASES))
            ->get()
            ->keyBy('clave');

        return collect(self::BASES)->mapWithKeys(function (string $label, string $clave) use ($productos): array {
            $producto = $productos->get($clave);

            return [$clave => [
                'clave' => $clave,
                'label' => $label,
                'producto_id' => $producto?->id,
                'renta' => (float) ($producto?->precio_renta_dia ?? 0),
                'venta' => (float) ($producto?->precio_venta ?? 0),
                'disponible' => (bool) $producto,
            ]];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function previsualizar(array $preciosBase): array
    {
        $bases = $this->normalizarPreciosBase($preciosBase);
        $productos = Productos::query()
            ->with('productoBase')
            ->where('linea', 'MADERA')
            ->orderBy('grupo')
            ->orderBy('clave')
            ->get();

        $filas = [];
        foreach ($productos as $producto) {
            if ($this->esExcluido($producto)) {
                continue;
            }

            $baseClave = $this->resolverClaveBase($producto);
            $baseProducto = $baseClave ? $this->productoBase($baseClave) : null;
            $base = $baseClave ? ($bases[$baseClave] ?? null) : null;
            $largo = $this->resolverLargoCm($producto);

            $fila = [
                'producto_id' => $producto->id,
                'clave' => $producto->clave,
                'producto' => $producto->descripcion,
                'grupo' => $producto->grupo,
                'base_clave' => $baseClave,
                'base_producto' => $baseProducto?->descripcion,
                'largo_cm' => $largo,
                'renta_actual' => (float) $producto->precio_renta_dia,
                'venta_actual' => (float) $producto->precio_venta,
                'renta_nueva' => null,
                'venta_nueva' => null,
                'estado' => 'Revisar familia o longitud',
                'listo' => false,
            ];

            if ($base && $baseProducto && $largo > 0) {
                $fila['renta_nueva'] = $this->calcularPrecio($base['renta'], $largo);
                $fila['venta_nueva'] = $this->calcularPrecio($base['venta'], $largo);
                $fila['estado'] = 'Listo para actualizar';
                $fila['listo'] = true;
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    /** @return array{lote_id: string, actualizados: int, bases: int, omitidos: int} */
    public function aplicar(array $preciosBase, ?int $usuarioId = null): array
    {
        $bases = $this->normalizarPreciosBase($preciosBase);
        $loteId = (string) Str::uuid();

        return DB::transaction(function () use ($bases, $loteId, $usuarioId): array {
            $baseProducts = Productos::query()
                ->whereIn('clave', array_keys(self::BASES))
                ->lockForUpdate()
                ->get()
                ->keyBy('clave');

            foreach (self::BASES as $clave => $label) {
                $producto = $baseProducts->get($clave);
                if (! $producto) {
                    throw new InvalidArgumentException("No existe el producto base {$clave}.");
                }
            }

            $actualizados = 0;
            foreach ($baseProducts as $clave => $producto) {
                $base = $bases[$clave];
                $rentaAnterior = (float) $producto->precio_renta_dia;
                $ventaAnterior = (float) $producto->precio_venta;
                $producto->update([
                    'precio_renta_dia' => $base['renta'],
                    'precio_venta' => $base['venta'],
                ]);
                $this->registrarHistorial($loteId, $producto, $producto, $usuarioId, $base['renta'], $base['venta'], $rentaAnterior, $ventaAnterior, null, 'Precio base capturado');
                $actualizados++;
            }

            $filas = $this->previsualizar($bases);
            $omitidos = 0;
            foreach ($filas as $fila) {
                if (! $fila['listo']) {
                    $omitidos++;
                    continue;
                }

                $producto = Productos::query()->lockForUpdate()->find($fila['producto_id']);
                if (! $producto) {
                    continue;
                }

                $baseProducto = $producto->producto_base_id
                    ? Productos::find($producto->producto_base_id)
                    : $this->productoBase($fila['base_clave']);
                $rentaAnterior = (float) $producto->precio_renta_dia;
                $ventaAnterior = (float) $producto->precio_venta;
                $producto->update([
                    'precio_renta_dia' => $fila['renta_nueva'],
                    'precio_venta' => $fila['venta_nueva'],
                ]);
                $this->registrarHistorial($loteId, $producto, $baseProducto, $usuarioId, (float) ($bases[$fila['base_clave']]['renta'] ?? 0), (float) ($bases[$fila['base_clave']]['venta'] ?? 0), $rentaAnterior, $ventaAnterior, (float) $fila['largo_cm'], 'Redondeo bancario: base / 250 * largo');
                $actualizados++;
            }

            return compact('loteId', 'actualizados', 'omitidos') + ['bases' => count($baseProducts)];
        });
    }

    public function calcularPrecio(float $base, float $largoCm): float
    {
        return round(($base / self::LARGO_ESTANDAR_CM) * $largoCm, 0, PHP_ROUND_HALF_EVEN);
    }

    /** @return array<string, array{renta: float, venta: float}> */
    private function normalizarPreciosBase(array $preciosBase): array
    {
        $actuales = $this->obtenerPreciosBase();
        $resultado = [];

        foreach (self::BASES as $clave => $label) {
            $renta = $preciosBase[$clave]['renta'] ?? $actuales[$clave]['renta'] ?? null;
            $venta = $preciosBase[$clave]['venta'] ?? $actuales[$clave]['venta'] ?? null;
            if (! is_numeric($renta) || ! is_numeric($venta) || (float) $renta < 0 || (float) $venta < 0) {
                throw new InvalidArgumentException("Los precios de {$clave} deben ser numéricos y no negativos.");
            }
            $resultado[$clave] = ['renta' => round((float) $renta, 2), 'venta' => round((float) $venta, 2)];
        }

        return $resultado;
    }

    private function productoBase(string $clave): ?Productos
    {
        return Productos::query()->where('clave', $clave)->first();
    }

    private function resolverClaveBase(Productos $producto): ?string
    {
        if ($producto->producto_base_id && $producto->productoBase?->clave && isset(self::BASES[$producto->productoBase->clave])) {
            return $producto->productoBase->clave;
        }

        $clave = strtoupper(trim((string) $producto->clave));
        $grupo = mb_strtolower(trim((string) $producto->grupo));
        $prefijos = [
            'POLINENTERO' => ['POLIN-'],
            'BARROTE-ENTERO' => ['BARROTE-'],
            'TABLA30-ENTERA' => ['TABLA30-'],
            'TABLA25-ENTERA' => ['TABLA25-'],
            'TABLA20-ENTERA' => ['TABLA20-'],
            'TABLA15-ENTERA' => ['TABLA15-'],
            'DUELA10-ENTERA' => ['DUELA-'],
        ];

        foreach ($prefijos as $base => $familiaPrefijos) {
            foreach ($familiaPrefijos as $prefijo) {
                if (str_starts_with($clave, $prefijo)) {
                    return $base;
                }
            }
        }

        return match (true) {
            $grupo === 'polin' => 'POLINENTERO',
            $grupo === 'barrote' => 'BARROTE-ENTERO',
            default => null,
        };
    }

    private function resolverLargoCm(Productos $producto): float
    {
        if ((float) $producto->largo > 0) {
            return (float) $producto->largo;
        }

        $clave = strtoupper(trim((string) $producto->clave));
        if (preg_match('/-(\d+)$/', $clave, $matches) === 1) {
            $digitos = $matches[1];
            return match (strlen($digitos)) {
                1, 2, 3 => (float) $digitos,
                4 => (float) substr($digitos, -2),
                default => (float) substr($digitos, -3),
            };
        }

        $descripcion = strtoupper((string) $producto->descripcion);
        preg_match_all('/\d+(?:[.,]\d+)?/', $descripcion, $matches);
        $numeros = array_map(static fn (string $numero): float => (float) str_replace(',', '.', $numero), $matches[0] ?? []);
        $largo = $numeros ? max($numeros) : 0;
        if ($largo > 0 && preg_match('/MTS?|METROS?/', $descripcion) === 1 && $largo <= 10) {
            $largo *= 100;
        }

        return $largo;
    }

    private function esExcluido(Productos $producto): bool
    {
        $texto = mb_strtolower(implode(' ', [(string) $producto->clave, (string) $producto->descripcion, (string) $producto->grupo]));

        return isset(self::BASES[$producto->clave])
            || str_contains($texto, 'triplay')
            || str_contains($texto, 'm2')
            || str_contains(mb_strtolower((string) $producto->grupo), 'renta');
    }

    private function registrarHistorial(string $loteId, Productos $producto, ?Productos $base, ?int $usuarioId, float $baseRenta, float $baseVenta, float $rentaAnterior, float $ventaAnterior, ?float $largoCm, string $formula): void
    {
        HistorialPreciosMadera::create([
            'lote_id' => $loteId,
            'producto_id' => $producto->id,
            'producto_base_id' => $base?->id,
            'usuario_id' => $usuarioId,
            'tipo' => $producto->id === $base?->id ? 'base' : 'pedaceria',
            'largo_cm' => $largoCm,
            'precio_renta_anterior' => $rentaAnterior,
            'precio_renta_nuevo' => (float) $producto->precio_renta_dia,
            'precio_venta_anterior' => $ventaAnterior,
            'precio_venta_nuevo' => (float) $producto->precio_venta,
            'base_renta' => $baseRenta,
            'base_venta' => $baseVenta,
            'formula' => $formula,
        ]);
    }
}

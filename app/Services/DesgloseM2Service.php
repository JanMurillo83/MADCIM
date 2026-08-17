<?php

namespace App\Services;

use App\Enums\TipoNotaRenta;
use App\Models\Configuracion;
use App\Models\Productos;
use App\Support\Numero;

class DesgloseM2Service
{
    /**
     * @param array<int, array{producto_id?: int|string, cantidad?: float|int}>|null $desglose
     * @return array<int, string>
     */
    public static function validarExistencias(?array $desglose): array
    {
        $cantidades = [];

        foreach ($desglose ?? [] as $fila) {
            $productoId = (int) ($fila['producto_id'] ?? 0);
            $cantidad = (float) ($fila['cantidad'] ?? 0);

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $cantidades[$productoId] = ($cantidades[$productoId] ?? 0) + $cantidad;
        }

        if ($cantidades === []) {
            return [];
        }

        $productos = Productos::query()
            ->whereIn('id', array_keys($cantidades))
            ->get(['id', 'clave', 'descripcion', 'existencia'])
            ->keyBy('id');

        $errores = [];
        foreach ($cantidades as $productoId => $cantidadSolicitada) {
            $producto = $productos->get($productoId);
            $existencia = (float) ($producto?->existencia ?? 0);

            if (!$producto || $existencia + 0.000001 < $cantidadSolicitada) {
                $nombre = $producto
                    ? trim($producto->clave . ' - ' . $producto->descripcion)
                    : "Producto #{$productoId}";

                $errores[] = sprintf(
                    '%s: se requieren %s piezas y solo hay %s disponibles.',
                    $nombre,
                    Numero::formato($cantidadSolicitada, 2),
                    Numero::formato($existencia, 2),
                );
            }
        }

        return $errores;
    }

    /**
     * Genera el desglose sugerido de productos para cubrir los M2 solicitados.
     *
     * @return array<int, array{
     *   producto_id: int,
     *   clave: string,
     *   descripcion: string,
     *   cantidad: float,
     *   m2_cubre: float,
     *   m2_total: float,
     *   tipo_madera: string,
     *   observaciones: string,
     * }>
     */
    public static function generar(TipoNotaRenta $tipo, float $metros, ?array $ajustes = null): array
    {
        if (!$tipo->esMaderaM2() || $metros <= 0) {
            return [];
        }

        $ajustes = self::normalizarAjustes($ajustes);
        $tipoMadera = $tipo->tipoMaderaM2();

        return match ($tipoMadera) {
            'tabla' => self::ajustarM2Total(self::generarTabla($metros, $ajustes), $metros),
            'triplay_15', 'triplay_18' => self::ajustarM2Total(self::generarTriplay($metros, $tipoMadera), $metros),
            default => [],
        };
    }

    /**
     * Conserva cantidades enteras de productos y distribuye solo el M2
     * realmente rentado entre las piezas sugeridas.
     *
     * @param array<int, array> $desglose
     * @return array<int, array>
     */
    private static function ajustarM2Total(array $desglose, float $metros): array
    {
        $restante = round(max(0, $metros), 2);
        $ajustado = [];

        foreach ($desglose as $fila) {
            if ($restante <= 0) {
                break;
            }

            $m2Disponible = round((float) ($fila['m2_total'] ?? 0), 2);
            $m2Asignado = min($m2Disponible, $restante);
            if ($m2Asignado <= 0) {
                continue;
            }

            $fila['m2_total'] = $m2Asignado;
            if ($m2Asignado < $m2Disponible) {
                $fila['observaciones'] = trim(($fila['observaciones'] ?? '') . ' Cobertura parcial para completar M2 solicitados.');
            }

            $ajustado[] = $fila;
            $restante = round($restante - $m2Asignado, 2);
        }

        return $ajustado;
    }

    /**
     * Convierte las filas del repeater a un mapa por producto para conservar
     * cantidades y observaciones editadas por el usuario.
     *
     * @param array<int, array>|null $ajustes
     * @return array<int, array>
     */
    private static function normalizarAjustes(?array $ajustes): array
    {
        $resultado = [];

        foreach ($ajustes ?? [] as $clave => $ajuste) {
            if (!is_array($ajuste)) {
                continue;
            }

            $productoId = $ajuste['producto_id'] ?? (is_numeric($clave) ? (int) $clave : null);
            if ($productoId) {
                $resultado[(int) $productoId] = $ajuste;
            }
        }

        return $resultado;
    }

    /**
     * @param array<int, array{cantidad?: float|int, observaciones?: string}>|null $ajustes
     */
    private static function generarTabla(float $metros, ?array $ajustes): array
    {
        $config = Configuracion::first();
        $porTablaEntera = (float) ($config?->por_tab_com ?? 80);
        $porPedaceria = (float) ($config?->por_tab_ped ?? 20);

        $m2Entera = $metros * ($porTablaEntera / 100);
        $m2Pedaceria = $metros * ($porPedaceria / 100);

        $desglose = [];

        // Tabla entera: 20% TABLA30, 40% TABLA25, 40% TABLA20
        $desglose = array_merge(
            $desglose,
            self::distribuirEnProductos(
                m2Objetivo: $m2Entera * 0.20,
                productos: [47], // TABLA DE 30 USADA ENTERA
                tipoMadera: 'tabla',
                observacion: 'Tabla entera 30',
                ajustes: $ajustes,
            ),
            self::distribuirEnProductos(
                m2Objetivo: $m2Entera * 0.40,
                productos: [65], // TABLA DE 25 USADA ENTERA
                tipoMadera: 'tabla',
                observacion: 'Tabla entera 25',
                ajustes: $ajustes,
            ),
            self::distribuirEnProductos(
                m2Objetivo: $m2Entera * 0.40,
                productos: [83], // TABLA DE 20 USADA ENTERA
                tipoMadera: 'tabla',
                observacion: 'Tabla entera 20',
                ajustes: $ajustes,
            ),
        );

        // Pedacería: proporciones según m2_cubre
        // Usamos productos de pedacería más comunes
        $desglose = array_merge(
            $desglose,
            self::distribuirEnProductos(
                m2Objetivo: $m2Pedaceria,
                productos: [101, 83, 65], // Mix de enteras/pedacería para aproximar
                tipoMadera: 'tabla',
                observacion: 'Pedacería sugerida',
                ajustes: $ajustes,
            ),
        );

        return self::normalizarDesglose($desglose);
    }

    private static function generarTriplay(float $metros, string $tipoMadera): array
    {
        $productoId = $tipoMadera === 'triplay_15' ? 140 : 141;
        $producto = Productos::find($productoId);

        if (!$producto || (float) $producto->m2_cubre <= 0) {
            return [];
        }

        $m2Cubre = (float) $producto->m2_cubre;
        $cantidad = ceil($metros / $m2Cubre);

        return [
            [
                'producto_id' => $producto->id,
                'clave' => $producto->clave,
                'descripcion' => $producto->descripcion,
                'cantidad' => $cantidad,
                'm2_cubre' => $m2Cubre,
                'm2_total' => round($cantidad * $m2Cubre, 2),
                'tipo_madera' => $tipoMadera,
                'observaciones' => 'Triplay sugerido',
            ],
        ];
    }

    /**
     * @param array<int> $productos
     * @param array<int, array{cantidad?: float|int, observaciones?: string}>|null $ajustes
     * @return array<int, array>
     */
    private static function distribuirEnProductos(float $m2Objetivo, array $productos, string $tipoMadera, string $observacion, ?array $ajustes): array
    {
        if ($m2Objetivo <= 0) {
            return [];
        }

        $resultado = [];
        $m2Restante = $m2Objetivo;
        $totalProductos = count($productos);

        foreach ($productos as $index => $productoId) {
            $producto = Productos::find($productoId);
            if (!$producto || (float) $producto->m2_cubre <= 0) {
                continue;
            }

            $m2Cubre = (float) $producto->m2_cubre;
            $esUltimo = $index === $totalProductos - 1;

            if ($esUltimo) {
                $cantidad = max(1, (int) ceil($m2Restante / $m2Cubre));
            } else {
                $m2Asignar = $m2Restante / ($totalProductos - $index);
                $cantidad = max(1, (int) ceil($m2Asignar / $m2Cubre));
                $m2Restante -= ($cantidad * $m2Cubre);
            }

            $ajuste = $ajustes[$productoId] ?? null;
            if ($ajuste && isset($ajuste['cantidad'])) {
                $cantidad = max(0, (float) $ajuste['cantidad']);
            }

            if ($cantidad <= 0) {
                continue;
            }

            $resultado[] = [
                'producto_id' => $producto->id,
                'clave' => $producto->clave,
                'descripcion' => $producto->descripcion,
                'cantidad' => $cantidad,
                'm2_cubre' => $m2Cubre,
                'm2_total' => round($cantidad * $m2Cubre, 2),
                'tipo_madera' => $tipoMadera,
                'observaciones' => $ajuste['observaciones'] ?? $observacion,
            ];
        }

        return $resultado;
    }

    /**
     * @param array<int, array> $desglose
     * @return array<int, array>
     */
    private static function normalizarDesglose(array $desglose): array
    {
        $agrupado = [];

        foreach ($desglose as $fila) {
            $key = $fila['producto_id'];
            if (!isset($agrupado[$key])) {
                $agrupado[$key] = $fila;
            } else {
                $agrupado[$key]['cantidad'] += $fila['cantidad'];
                $agrupado[$key]['m2_total'] = round($agrupado[$key]['cantidad'] * $agrupado[$key]['m2_cubre'], 2);
                $agrupado[$key]['observaciones'] = trim($agrupado[$key]['observaciones'] . '; ' . $fila['observaciones'], '; ');
            }
        }

        return array_values($agrupado);
    }
}

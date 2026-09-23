<?php

namespace Tests\Feature;

use App\Models\HistorialPreciosMadera;
use App\Models\Productos;
use App\Models\User;
use App\Services\ActualizarPreciosMaderaService;
use Tests\TestCase;

class ActualizarPreciosMaderaServiceTest extends TestCase
{
    public function test_previsualiza_precios_de_pedaceria_con_redondeo_bancario(): void
    {
        $base = Productos::create([
            'clave' => 'POLINENTERO',
            'descripcion' => 'Polin entero',
            'grupo' => 'POLIN',
            'linea' => 'MADERA',
            'precio_renta_dia' => 50,
            'precio_venta' => 115,
        ]);
        Productos::create([
            'clave' => 'POLIN-3060',
            'descripcion' => 'Polin de 30 a 60 cm',
            'grupo' => 'POLIN',
            'linea' => 'MADERA',
            'producto_base_id' => $base->id,
            'precio_renta_dia' => 12,
            'precio_venta' => 28,
        ]);

        $filas = app(ActualizarPreciosMaderaService::class)->previsualizar([
            'POLINENTERO' => ['renta' => 50, 'venta' => 115],
        ]);

        $fila = collect($filas)->firstWhere('clave', 'POLIN-3060');
        $this->assertTrue($fila['listo']);
        $this->assertSame(12.0, (float) $fila['renta_nueva']);
        $this->assertSame(28.0, (float) $fila['venta_nueva']);
    }

    public function test_aplica_bases_y_pedaceria_en_una_transaccion_y_audita_el_lote(): void
    {
        $base = Productos::create([
            'clave' => 'POLINENTERO',
            'descripcion' => 'Polin entero',
            'grupo' => 'POLIN',
            'linea' => 'MADERA',
            'precio_renta_dia' => 50,
            'precio_venta' => 115,
        ]);
        foreach (['BARROTE-ENTERO', 'TABLA30-ENTERA', 'TABLA25-ENTERA', 'TABLA20-ENTERA', 'TABLA15-ENTERA', 'DUELA10-ENTERA'] as $clave) {
            Productos::create([
                'clave' => $clave,
                'descripcion' => $clave,
                'grupo' => 'MADERA',
                'linea' => 'MADERA',
                'precio_renta_dia' => 10,
                'precio_venta' => 10,
            ]);
        }
        $pedaceria = Productos::create([
            'clave' => 'POLIN-80100',
            'descripcion' => 'Polin de 80 a 100 cm',
            'grupo' => 'POLIN',
            'linea' => 'MADERA',
            'producto_base_id' => $base->id,
            'precio_renta_dia' => 1,
            'precio_venta' => 1,
        ]);
        $usuario = User::factory()->create();

        $resultado = app(ActualizarPreciosMaderaService::class)->aplicar([
            'POLINENTERO' => ['renta' => 60, 'venta' => 130],
        ], $usuario->id);

        $this->assertSame(8, $resultado['actualizados']);
        $this->assertSame(60.0, (float) $base->fresh()->precio_renta_dia);
        $this->assertSame(130.0, (float) $base->fresh()->precio_venta);
        $this->assertSame(24.0, (float) $pedaceria->fresh()->precio_renta_dia);
        $this->assertSame(52.0, (float) $pedaceria->fresh()->precio_venta);
        $this->assertSame(8, HistorialPreciosMadera::where('lote_id', $resultado['loteId'])->count());
    }
}

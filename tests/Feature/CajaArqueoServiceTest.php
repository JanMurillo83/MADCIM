<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\User;
use App\Services\CajaArqueoService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CajaArqueoServiceTest extends TestCase
{
    public function test_calcula_efectivo_teorico_conteo_y_diferencia(): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update([
            'estatus' => 'Abierta',
            'usuario_apertura_id' => $usuario->id,
            'saldo_inicial_cash' => 100,
        ]);
        DB::table('caja_movimientos')->insert([
            ['caja_id' => $caja->id, 'tipo' => 'Ingreso', 'metodo_pago' => 'Efectivo', 'importe' => 250, 'fecha' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['caja_id' => $caja->id, 'tipo' => 'Egreso', 'metodo_pago' => 'Efectivo', 'importe' => 50, 'fecha' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['caja_id' => $caja->id, 'tipo' => 'Ingreso', 'metodo_pago' => 'Transferencia', 'importe' => 500, 'fecha' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $resultado = app(CajaArqueoService::class)->cerrar($caja, [
            'billete_100' => 2,
            'billete_50' => 1,
            'moneda_10' => 0,
        ], 'Cierre de prueba', $usuario->id);

        $this->assertSame(300.0, $resultado['efectivo_teorico']);
        $this->assertSame(250.0, $resultado['efectivo_contado']);
        $this->assertSame(-50.0, $resultado['diferencia']);
        $this->assertSame(500.0, $resultado['desglose']['Transferencia']['ingresos']);
        $this->assertSame('Cerrada', $caja->fresh()->estatus);
        $this->assertSame(-50.0, (float) $caja->fresh()->total_diferencia);
    }
}

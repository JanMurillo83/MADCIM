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

    public function test_al_abrir_un_nuevo_corte_reinicia_importes_y_excluye_movimientos_anteriores(): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update([
            'estatus' => 'Abierta',
            'fecha_apertura' => now()->subDay(),
            'usuario_apertura_id' => $usuario->id,
            'saldo_inicial_cash' => 100,
        ]);

        DB::table('caja_movimientos')->insert([
            ['caja_id' => $caja->id, 'tipo' => 'Ingreso', 'metodo_pago' => 'Efectivo', 'importe' => 200, 'fecha' => now()->subHour(), 'created_at' => now()->subHour(), 'updated_at' => now()->subHour()],
        ]);

        $service = app(CajaArqueoService::class);
        $service->cerrar($caja, ['billete_100' => 3], null, $usuario->id);
        $service->abrir($caja, 50, $usuario->id);

        $cajaNueva = $caja->fresh();
        $this->assertSame('Abierta', $cajaNueva->estatus);
        $this->assertSame(50.0, (float) $cajaNueva->saldo_inicial_cash);
        $this->assertSame(0.0, (float) $cajaNueva->total_ingresos_cash);
        $this->assertSame(0.0, (float) $cajaNueva->total_egresos_cash);
        $this->assertSame(0.0, (float) $cajaNueva->efectivo_teorico);
        $this->assertSame(0.0, (float) $cajaNueva->efectivo_contado);
        $this->assertSame(0.0, (float) $cajaNueva->total_diferencia);

        DB::table('caja_movimientos')->insert([
            ['caja_id' => $caja->id, 'tipo' => 'Ingreso', 'metodo_pago' => 'Efectivo', 'importe' => 30, 'fecha' => now()->addSecond(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame(80.0, $service->resumen($cajaNueva->fresh())['efectivo_teorico']);
    }
}

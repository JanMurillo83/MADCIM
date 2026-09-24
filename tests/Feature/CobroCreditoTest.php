<?php

namespace Tests\Feature;

use App\Models\NotasVentaRenta;
use App\Models\Pagos;
use App\Models\Clientes;
use App\Models\Caja;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\CobroNotaService;
use Database\Seeders\SatCatalogsSeeder;
use Tests\TestCase;

class CobroCreditoTest extends TestCase
{
    public function test_confirmar_credito_no_crea_pago_y_marca_la_nota_para_cuentas_por_cobrar(): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $nota = NotasVentaRenta::create([
            'serie' => 'NR',
            'folio' => 'CRED-001',
            'fecha_emision' => now(),
            'condicion_pago' => 'credito',
            'estatus' => 'Activa',
            'total' => 1000,
            'saldo_pendiente' => 1000,
        ]);

        $fechaEmision = $nota->fecha_emision;
        $resultado = app(CobroNotaService::class)->confirmarCredito(
            'notas_venta_renta',
            $nota->id,
        );

        $this->assertSame($nota->id, $resultado->id);
        $this->assertNotNull($nota->fresh()->cobro_credito_confirmado_en);
        $this->assertSame(1000.0, (float) $nota->fresh()->saldo_pendiente);
        $this->assertSame(0, Pagos::where('documento_tipo', 'notas_venta_renta')->where('documento_id', $nota->id)->count());
        $this->assertSame($fechaEmision->toDateTimeString(), $nota->fresh()->fecha_emision->toDateTimeString());
    }

    public function test_cobro_no_efectivo_aparece_en_desglose_sin_sumarse_al_efectivo(): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $cliente = Clientes::create([
            'clave' => 'CLI-MIX-001',
            'nombre' => 'Cliente pagos mixtos',
            'rfc' => 'XAXX010101000',
            'folio_ine' => 'INE-MIX-001',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'mix@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);
        $caja = Caja::findOrFail(1);
        $caja->update([
            'nombre' => 'Caja pagos mixtos',
            'estatus' => 'Abierta',
            'usuario_apertura_id' => $usuario->id,
            'saldo_inicial_cash' => 100,
        ]);
        $cajaId = $caja->id;
        $nota = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'serie' => 'NR',
            'folio' => 'MIX-001',
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'estatus' => 'Activa',
            'total' => 250,
            'saldo_pendiente' => 250,
        ]);
        $this->assertSame($cajaId, Caja::query()
            ->where('estatus', 'Abierta')
            ->where('usuario_apertura_id', $usuario->id)
            ->value('id'));
        $this->seed(SatCatalogsSeeder::class);

        app(CobroNotaService::class)->cobrar('notas_venta_renta', $nota->id, [
            'pagos' => [[
                'forma_pago' => '03',
                'importe' => 250,
            ]],
        ], $usuario->id);

        $this->assertDatabaseHas('caja_movimientos', [
            'caja_id' => $cajaId,
            'tipo' => 'Ingreso',
            'metodo_pago' => 'Transferencia',
            'importe' => 250,
        ]);
        $this->assertSame(0.0, (float) DB::table('caja_movimientos')
            ->where('caja_id', $cajaId)
            ->where('tipo', 'Ingreso')
            ->where('metodo_pago', 'Efectivo')
            ->sum('importe'));
    }
}

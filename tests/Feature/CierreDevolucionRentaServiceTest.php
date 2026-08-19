<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Models\Productos;
use App\Models\User;
use App\Services\CierreDevolucionRentaService;
use Database\Seeders\SatCatalogsSeeder;
use Tests\TestCase;

class CierreDevolucionRentaServiceTest extends TestCase
{
    public function test_faltante_de_renta_se_convierte_en_venta_aplica_deposito_y_bloquea_cliente(): void
    {
        $usuario = User::factory()->create();
        $this->seed(SatCatalogsSeeder::class);
        $this->assertDatabaseHas('users', ['id' => $usuario->id]);

        $cliente = Clientes::create([
            'clave' => 'CLI-FALTANTE',
            'nombre' => 'Cliente con faltante',
            'rfc' => 'XAXX010101000',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'cliente@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);

        $producto = Productos::create([
            'clave' => 'EQ-FALTANTE',
            'descripcion' => 'Equipo no devuelto',
            'grupo' => 'EQUIPO',
            'linea' => 'RENTA',
            'precio_venta' => 100,
            'existencia' => 0,
        ]);

        $nota = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 0,
            'saldo_pendiente' => 0,
            'deposito' => 10,
            'estatus' => 'Activa',
            'tipo_nota_renta' => 'equipo',
        ]);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);

        $envio = NotaEnvio::create([
            'nota_venta_renta_id' => $nota->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Vigente',
        ]);

        NotaEnvioPartida::create([
            'nota_envio_id' => $envio->id,
            'producto_id' => $producto->id,
            'descripcion' => $producto->descripcion,
            'cantidad' => 1,
            'cantidad_devuelta' => 0,
            'estado' => 'Activo',
        ]);
        $this->assertDatabaseHas('users', ['id' => $usuario->id]);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);

        $resultado = app(CierreDevolucionRentaService::class)->cerrar($nota, userId: $usuario->id);

        $notaVenta = NotasVentaVenta::findOrFail($resultado['nota_venta_venta_id']);

        $this->assertSame(116.0, (float) $notaVenta->total);
        $this->assertSame(106.0, (float) $notaVenta->saldo_pendiente);
        $this->assertSame(10.0, (float) Pagos::where('documento_id', $notaVenta->id)->sum('importe'));
        $this->assertSame(Clientes::ESTATUS_BLOQUEADO, $cliente->fresh()->estatus_cliente);
        $this->assertSame(0.0, (float) $producto->fresh()->existencia);
        $this->assertSame('Devuelta', $nota->fresh()->estatus);
    }
}

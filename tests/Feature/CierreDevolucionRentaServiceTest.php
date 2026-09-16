<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\ClienteDireccionEntrega;
use App\Models\CierreDevolucionRenta;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Models\User;
use App\Services\CierreDevolucionRentaService;
use Database\Seeders\SatCatalogsSeeder;
use Tests\TestCase;

class CierreDevolucionRentaServiceTest extends TestCase
{
    public function test_obras_completamente_devueltas_conservan_cierre_pendiente_de_caja(): void
    {
        $usuario = User::factory()->create();
        $cliente = Clientes::create([
            'clave' => 'CLI-CIERRE-PENDIENTE',
            'nombre' => 'Cliente cierre pendiente',
            'rfc' => 'XAXX010101000',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'cierre@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);
        $obra = ClienteDireccionEntrega::create([
            'cliente_id' => $cliente->id,
            'nombre_direccion' => 'Obra de prueba',
            'calle' => 'Obra',
            'numero_exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'codigo_postal' => '01000',
            'pais' => 'México',
            'activa' => true,
        ]);
        $producto = Productos::create([
            'clave' => 'EQ-CIERRE-PENDIENTE',
            'descripcion' => 'Equipo devuelto completo',
            'grupo' => 'EQUIPO',
            'linea' => 'RENTA',
            'precio_venta' => 100,
            'existencia' => 0,
        ]);
        $nota = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'direccion_entrega_id' => $obra->id,
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 0,
            'saldo_pendiente' => 0,
            'deposito' => 50,
            'estatus' => 'Activa',
            'tipo_nota_renta' => 'equipo',
        ]);
        RegistroRenta::create([
            'nota_venta_renta_id' => $nota->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'cantidad_devuelta' => 2,
            'dias_renta' => 1,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'importe_renta' => 0,
            'importe_deposito' => 50,
            'estado' => 'Devuelto',
        ]);

        $resultado = app(CierreDevolucionRentaService::class)->cerrarPorObra(
            $cliente->id,
            $obra->id,
            userId: $usuario->id,
        );

        $this->assertSame('PendienteCaja', $resultado['cierre_estatus']);
        $this->assertDatabaseHas('cierres_devolucion_renta', [
            'cliente_id' => $cliente->id,
            'direccion_entrega_id' => $obra->id,
            'estatus' => 'PendienteCaja',
            'deposito_a_devolver' => 50,
        ]);
        $this->assertSame('Devuelta', $nota->fresh()->estatus);

        $segundoIntento = app(CierreDevolucionRentaService::class)->cerrarPorObra($cliente->id, $obra->id, userId: $usuario->id);

        $this->assertSame('PendienteCaja', $segundoIntento['cierre_estatus']);
        $this->assertSame(1, CierreDevolucionRenta::where('cliente_id', $cliente->id)->where('direccion_entrega_id', $obra->id)->count());
    }

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

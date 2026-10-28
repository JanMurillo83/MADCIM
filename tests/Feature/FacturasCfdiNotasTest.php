<?php

namespace Tests\Feature;

use App\Models\FacturasCfdi;
use App\Services\FacturaCfdiNotasService;
use App\Services\FacturaCfdiPacService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FacturasCfdiNotasTest extends TestCase
{
    public function test_solo_muestra_notas_del_mismo_cliente_y_sucursal_activas_y_no_facturadas(): void
    {
        $clienteId = $this->crearCliente('CLI-NOTAS-1');
        $otroClienteId = $this->crearCliente('CLI-NOTAS-2');
        $sucursalId = $this->crearSucursal('Sucursal notas');
        $otraSucursalId = $this->crearSucursal('Otra sucursal');
        $productoId = $this->crearProducto();

        $disponibleId = $this->crearNotaVenta($clienteId, $sucursalId, $productoId, 'Activa');
        DB::table('nota_venta_venta_partidas')
            ->where('nota_venta_venta_id', $disponibleId)
            ->update(['valor_unitario' => 116]);
        $canceladaId = $this->crearNotaVenta($clienteId, $sucursalId, $productoId, 'Cancelada');
        $facturadaId = $this->crearNotaVenta($clienteId, $sucursalId, $productoId, 'Activa');
        $otroClienteNotaId = $this->crearNotaVenta($otroClienteId, $sucursalId, $productoId, 'Activa');
        $otraSucursalNotaId = $this->crearNotaVenta($clienteId, $otraSucursalId, $productoId, 'Activa');
        $facturaId = DB::table('facturas_cfdi')->insertGetId([
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('factura_cfdi_partidas')->insert([
            'factura_cfdi_id' => $facturaId,
            'cantidad' => 1,
            'item' => (string) $productoId,
            'descripcion' => 'Partida ya facturada',
            'no_identificacion' => "NVV:{$facturadaId}:1",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $servicio = app(FacturaCfdiNotasService::class);
        $opciones = $servicio->opcionesNotasVenta($clienteId, $sucursalId);
        $partidas = $servicio->construirPartidas([$disponibleId], [], $clienteId, $sucursalId);

        $this->assertArrayHasKey($disponibleId, $opciones);
        $this->assertArrayNotHasKey($canceladaId, $opciones);
        $this->assertArrayNotHasKey($facturadaId, $opciones);
        $this->assertArrayNotHasKey($otroClienteNotaId, $opciones);
        $this->assertArrayNotHasKey($otraSucursalNotaId, $opciones);
        $this->assertSame("NVV:{$disponibleId}:1", $partidas[0]['no_identificacion']);
        $this->assertSame('Producto de prueba', $partidas[0]['descripcion']);
        $this->assertSame(100.0, $partidas[0]['valor_unitario']);
        $this->assertSame(116.0, $partidas[0]['total']);
    }

    public function test_notas_de_renta_usan_metros_cuadrados_como_cantidad_facturable(): void
    {
        $clienteId = $this->crearCliente('CLI-NOTAS-RENTA');
        $sucursalId = $this->crearSucursal('Sucursal renta');
        $productoId = $this->crearProducto();
        $notaId = DB::table('notas_venta_renta')->insertGetId([
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
            'serie' => 'NR',
            'folio' => 'RENTA-1',
            'fecha_emision' => now(),
            'estatus' => 'Activa',
            'subtotal' => 200,
            'impuestos_total' => 32,
            'total' => 232,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $partidaId = DB::table('nota_venta_renta_partidas')->insertGetId([
            'nota_venta_renta_id' => $notaId,
            'cantidad' => 1,
            'metros_m2' => 3.5,
            'tipo_nota_renta' => 'madera_m2_tabla',
            'item' => (string) $productoId,
            'descripcion' => 'Madera renta',
            'valor_unitario' => 200,
            'subtotal' => 200,
            'impuestos' => 32,
            'total' => 232,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partidas = app(FacturaCfdiNotasService::class)->construirPartidas(
            [],
            [$notaId],
            $clienteId,
            $sucursalId,
        );

        $this->assertSame("NVR:{$notaId}:{$partidaId}", $partidas[0]['no_identificacion']);
        $this->assertSame(3.5, $partidas[0]['cantidad']);
        $this->assertSame(200.0, $partidas[0]['subtotal']);
        $this->assertEqualsWithDelta(200 / 3.5, $partidas[0]['valor_unitario'], 0.00001);
    }

    public function test_valida_nuevamente_que_la_nota_siga_disponible_al_generar_partidas(): void
    {
        $clienteId = $this->crearCliente('CLI-NOTAS-CANCELADA');
        $sucursalId = $this->crearSucursal('Sucursal cancelada');
        $productoId = $this->crearProducto();
        $notaId = $this->crearNotaVenta($clienteId, $sucursalId, $productoId, 'Cancelada');

        $this->expectException(ValidationException::class);

        app(FacturaCfdiNotasService::class)->construirPartidas(
            [$notaId],
            [],
            $clienteId,
            $sucursalId,
        );
    }

    public function test_cancelar_factura_sin_timbre_libera_sus_notas_de_origen(): void
    {
        $clienteId = $this->crearCliente('CLI-NOTAS-LIBERADAS');
        $sucursalId = $this->crearSucursal('Sucursal notas liberadas');
        $productoId = $this->crearProducto();
        $notaId = $this->crearNotaVenta($clienteId, $sucursalId, $productoId, 'Activa');
        $facturaId = DB::table('facturas_cfdi')->insertGetId([
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
            'estatus' => 'Borrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $partidaNotaId = DB::table('nota_venta_venta_partidas')
            ->where('nota_venta_venta_id', $notaId)
            ->value('id');
        DB::table('factura_cfdi_partidas')->insert([
            'factura_cfdi_id' => $facturaId,
            'cantidad' => 1,
            'item' => (string) $productoId,
            'descripcion' => 'Nota origen',
            'no_identificacion' => "NVV:{$notaId}:{$partidaNotaId}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notasService = app(FacturaCfdiNotasService::class);
        $this->assertArrayNotHasKey($notaId, $notasService->opcionesNotasVenta($clienteId, $sucursalId));

        $resultado = app(FacturaCfdiPacService::class)
            ->cancelarSinTimbrar(FacturasCfdi::query()->findOrFail($facturaId));

        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey($notaId, $notasService->opcionesNotasVenta($clienteId, $sucursalId));
        $this->assertCount(
            1,
            $notasService->construirPartidas([$notaId], [], $clienteId, $sucursalId),
        );
    }

    private function crearCliente(string $clave): int
    {
        return DB::table('clientes')->insertGetId([
            'clave' => $clave,
            'nombre' => 'Cliente de prueba',
            'rfc' => 'XAXX010101000',
            'regimen' => '616',
            'codigo' => '01000',
            'calle' => 'Calle prueba',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Ciudad',
            'estado' => 'Estado',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'cliente@example.test',
            'contacto' => 'Contacto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearSucursal(string $nombre): int
    {
        return DB::table('sucursales')->insertGetId([
            'nombre' => $nombre,
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearProducto(): int
    {
        return DB::table('productos')->insertGetId([
            'clave' => 'PROD-1',
            'descripcion' => 'Producto de prueba',
            'grupo' => 'Grupo',
            'linea' => 'Linea',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearNotaVenta(int $clienteId, int $sucursalId, int $productoId, string $estatus): int
    {
        $notaId = DB::table('notas_venta_venta')->insertGetId([
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
            'serie' => 'NV',
            'folio' => (string) random_int(100, 999),
            'fecha_emision' => now(),
            'estatus' => $estatus,
            'subtotal' => 100,
            'impuestos_total' => 16,
            'total' => 116,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nota_venta_venta_partidas')->insert([
            'nota_venta_venta_id' => $notaId,
            'cantidad' => 1,
            'item' => (string) $productoId,
            'descripcion' => 'Producto de prueba',
            'valor_unitario' => 100,
            'subtotal' => 100,
            'impuestos' => 16,
            'total' => 116,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $notaId;
    }
}

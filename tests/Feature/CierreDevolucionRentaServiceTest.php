<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\ClienteDireccionEntrega;
use App\Models\CierreDevolucionRenta;
use App\Models\DevolucionesRenta;
use App\Models\ExistenciaSucursal;
use App\Models\NotaEnvio;
use App\Models\NotaEnvioPartida;
use App\Models\NotaDevolucionRenta;
use App\Models\NotaDevolucionRentaPartida;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\CierreDevolucionRentaService;
use Database\Seeders\SatCatalogsSeeder;
use Tests\TestCase;

class CierreDevolucionRentaServiceTest extends TestCase
{
    private int $sucursalId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sucursalId = (int) Sucursal::create(['nombre' => 'Sucursal de pruebas'])->id;
    }

    public function test_obras_completamente_devueltas_conservan_cierre_pendiente_de_caja(): void
    {
        $usuario = User::factory()->create([
            'role' => 'Supervisor',
            'sucursal_id' => $this->sucursalId,
        ]);
        $this->actingAs($usuario);
        $cliente = Clientes::create([
            'clave' => 'CLI-CIERRE-PENDIENTE',
            'nombre' => 'Cliente cierre pendiente',
            'rfc' => 'XAXX010101000',
            'folio_ine' => 'INE-CIERRE-001',
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
            'folio_ine' => 'INE-VALIDO-001',
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
            'sucursal_id' => $this->sucursalId,
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
        NotaEnvio::create([
            'nota_venta_renta_id' => $nota->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'inicio_vigencia' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Devuelta',
        ]);

        $resultado = app(CierreDevolucionRentaService::class)->cerrarNotaDeObra(
            $cliente->id,
            $obra->id,
            $nota->id,
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

        try {
            app(CierreDevolucionRentaService::class)->procesarDepositoPendiente(
                $resultado['cierre_id'],
                $usuario->id,
                'INE-INCORRECTO',
            );
            $this->fail('Se esperaba rechazar el folio de INE incorrecto.');
        } catch (\DomainException $exception) {
            $this->assertSame('El folio de INE no coincide con el capturado en el cliente.', $exception->getMessage());
        }

        $this->assertSame('PendienteCaja', CierreDevolucionRenta::findOrFail($resultado['cierre_id'])->estatus);

        $this->assertFalse(app(CierreDevolucionRentaService::class)->procesarDepositoPendiente(
            $resultado['cierre_id'],
            $usuario->id,
            'INE-VALIDO-001',
        ));

        $segundoIntento = app(CierreDevolucionRentaService::class)->cerrarNotaDeObra($cliente->id, $obra->id, $nota->id, userId: $usuario->id);

        $this->assertSame('PendienteCaja', $segundoIntento['cierre_estatus']);
        $this->assertSame(1, CierreDevolucionRenta::where('cliente_id', $cliente->id)->where('direccion_entrega_id', $obra->id)->count());
    }

    public function test_usuario_de_sucursal_cierra_obra_completamente_devuelta_sin_importes(): void
    {
        $usuario = User::factory()->create([
            'role' => 'Supervisor',
            'sucursal_id' => $this->sucursalId,
        ]);
        $this->actingAs($usuario);

        $cliente = Clientes::create([
            'clave' => 'CLI-CIERRE-CERO',
            'nombre' => 'Cliente cierre sin importes',
            'rfc' => 'XAXX010101000',
            'folio_ine' => 'INE-CIERRE-CERO',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'Oaxaca',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'cierre-cero@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);
        $obra = ClienteDireccionEntrega::create([
            'cliente_id' => $cliente->id,
            'nombre_direccion' => 'Obra sin importes',
            'calle' => 'Obra',
            'numero_exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'Oaxaca',
            'codigo_postal' => '01000',
            'pais' => 'México',
            'activa' => true,
        ]);
        $producto = Productos::create([
            'clave' => 'EQ-CIERRE-CERO',
            'descripcion' => 'Equipo devuelto',
            'grupo' => 'EQUIPO',
            'linea' => 'RENTA',
            'precio_venta' => 100,
            'existencia' => 1,
        ]);
        $nota = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'sucursal_id' => $this->sucursalId,
            'direccion_entrega_id' => $obra->id,
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 0,
            'saldo_pendiente' => 0,
            'deposito' => 0,
            'estatus' => 'Devuelta',
            'tipo_nota_renta' => 'equipo',
        ]);
        RegistroRenta::create([
            'nota_venta_renta_id' => $nota->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'cantidad_devuelta' => 1,
            'dias_renta' => 1,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'importe_renta' => 0,
            'importe_deposito' => 0,
            'estado' => 'Devuelto',
        ]);
        NotaEnvio::create([
            'nota_venta_renta_id' => $nota->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'inicio_vigencia' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Devuelta',
        ]);

        $productoMadera = Productos::create([
            'clave' => 'MAD-CIERRE-PENDIENTE',
            'descripcion' => 'Madera todavía rentada',
            'grupo' => 'MADERA',
            'linea' => 'RENTA',
            'precio_venta' => 100,
            'existencia' => 0,
        ]);
        $notaMadera = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'sucursal_id' => $this->sucursalId,
            'direccion_entrega_id' => $obra->id,
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 50,
            'saldo_pendiente' => 50,
            'deposito' => 50,
            'estatus' => 'Activa',
            'tipo_nota_renta' => 'madera_pieza',
        ]);
        RegistroRenta::create([
            'nota_venta_renta_id' => $notaMadera->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'producto_id' => $productoMadera->id,
            'cantidad' => 3,
            'cantidad_devuelta' => 0,
            'dias_renta' => 30,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'importe_renta' => 50,
            'importe_deposito' => 50,
            'estado' => 'Activo',
        ]);
        NotaEnvio::create([
            'nota_venta_renta_id' => $notaMadera->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'inicio_vigencia' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Vigente',
        ]);

        $resultado = app(CierreDevolucionRentaService::class)->cerrarNotaDeObra(
            $cliente->id,
            $obra->id,
            $nota->id,
            userId: $usuario->id,
            sucursalId: $this->sucursalId,
        );

        $this->assertSame('Procesado', $resultado['cierre_estatus']);
        $this->assertDatabaseHas('cierres_devolucion_renta', [
            'id' => $resultado['cierre_id'],
            'sucursal_id' => $this->sucursalId,
            'estatus' => 'Procesado',
            'total_faltantes' => 0,
            'deposito_a_devolver' => 0,
        ]);
        $this->assertDatabaseHas('devoluciones_renta', [
            'id' => $resultado['devolucion_renta_id'],
            'sucursal_id' => $this->sucursalId,
            'documento_origen_id' => $nota->id,
        ]);
        $this->assertSame(1, DevolucionesRenta::where('documento_origen_id', $nota->id)->count());
        $this->assertSame('Activa', $notaMadera->fresh()->estatus);
        $this->assertSame(0.0, (float) $notaMadera->registrosRenta()->firstOrFail()->cantidad_devuelta);
        $this->assertSame(50.0, (float) $notaMadera->fresh()->deposito);
        $this->assertSame([$nota->id], CierreDevolucionRenta::findOrFail($resultado['cierre_id'])->nota_ids);
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
            'folio_ine' => 'INE-FALTANTE-001',
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
            'sucursal_id' => $this->sucursalId,
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
            'inicio_vigencia' => now()->toDateString(),
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

        $this->assertSame(100.0, (float) $notaVenta->total);
        $this->assertSame(86.21, (float) $notaVenta->subtotal);
        $this->assertSame(13.79, (float) $notaVenta->impuestos_total);
        $this->assertSame(90.0, (float) $notaVenta->saldo_pendiente);
        $this->assertSame(10.0, (float) Pagos::where('documento_id', $notaVenta->id)->sum('importe'));
        $this->assertSame(Clientes::ESTATUS_BLOQUEADO, $cliente->fresh()->estatus_cliente);
        $this->assertSame(0.0, (float) $producto->fresh()->existencia);
        $this->assertSame('Devuelta', $nota->fresh()->estatus);
    }

    public function test_cancelar_cierre_no_afecta_notas_creadas_despues_en_la_misma_obra(): void
    {
        $usuario = User::factory()->create([
            'role' => 'Supervisor',
            'sucursal_id' => $this->sucursalId,
        ]);
        $this->actingAs($usuario);
        $cliente = Clientes::create([
            'clave' => 'CLI-CIERRE-SNAPSHOT',
            'nombre' => 'Cliente snapshot',
            'rfc' => 'XAXX010101000',
            'folio_ine' => 'INE-SNAPSHOT-001',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'snapshot@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);
        $obra = ClienteDireccionEntrega::create([
            'cliente_id' => $cliente->id,
            'nombre_direccion' => 'Obra snapshot',
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
            'clave' => 'EQ-CIERRE-SNAPSHOT',
            'descripcion' => 'Equipo snapshot',
            'grupo' => 'EQUIPO',
            'linea' => 'RENTA',
            'precio_venta' => 100,
            'existencia' => 2,
        ]);
        ExistenciaSucursal::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalId,
            'existencia' => 1,
        ]);

        $notaInicial = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'sucursal_id' => $this->sucursalId,
            'direccion_entrega_id' => $obra->id,
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 0,
            'saldo_pendiente' => 0,
            'deposito' => 0,
            'estatus' => 'Activa',
            'tipo_nota_renta' => 'equipo',
        ]);
        RegistroRenta::create([
            'nota_venta_renta_id' => $notaInicial->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'cantidad_devuelta' => 1,
            'dias_renta' => 1,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'importe_renta' => 0,
            'importe_deposito' => 0,
            'estado' => 'Devuelto',
        ]);
        NotaEnvio::create([
            'nota_venta_renta_id' => $notaInicial->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'inicio_vigencia' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Devuelta',
        ]);

        $resultado = app(CierreDevolucionRentaService::class)->cerrarNotaDeObra(
            $cliente->id,
            $obra->id,
            $notaInicial->id,
            userId: $usuario->id,
        );

        $cierre = CierreDevolucionRenta::findOrFail($resultado['cierre_id']);
        $this->assertSame([$notaInicial->id], $cierre->nota_ids);

        $notaPosterior = NotasVentaRenta::create([
            'cliente_id' => $cliente->id,
            'sucursal_id' => $this->sucursalId,
            'direccion_entrega_id' => $obra->id,
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'subtotal' => 0,
            'impuestos_total' => 0,
            'total' => 0,
            'saldo_pendiente' => 0,
            'deposito' => 0,
            'estatus' => 'Activa',
            'tipo_nota_renta' => 'equipo',
        ]);
        $registroPosterior = RegistroRenta::create([
            'nota_venta_renta_id' => $notaPosterior->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'cantidad_devuelta' => 0,
            'dias_renta' => 1,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDay()->toDateString(),
            'importe_renta' => 0,
            'importe_deposito' => 0,
            'estado' => 'Activo',
        ]);
        NotaEnvio::create([
            'nota_venta_renta_id' => $notaPosterior->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'inicio_vigencia' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Vigente',
        ]);

        $segundoResultado = app(CierreDevolucionRentaService::class)->cerrarNotaDeObra(
            $cliente->id,
            $obra->id,
            $notaPosterior->id,
            userId: $usuario->id,
        );

        $segundoCierre = CierreDevolucionRenta::findOrFail($segundoResultado['cierre_id']);
        $this->assertSame([$notaPosterior->id], $segundoCierre->nota_ids);
        $this->assertNotSame($cierre->id, $segundoCierre->id);
        $cantidadPosteriorAntesDeCancelar = (float) $registroPosterior->fresh()->cantidad_devuelta;
        $estadoPosteriorAntesDeCancelar = $registroPosterior->fresh()->estado;

        app(CierreDevolucionRentaService::class)->cancelar($cierre, $usuario->id);

        $this->assertSame('Devuelta', $notaPosterior->fresh()->estatus);
        $this->assertSame($cantidadPosteriorAntesDeCancelar, (float) $registroPosterior->fresh()->cantidad_devuelta);
        $this->assertSame($estadoPosteriorAntesDeCancelar, $registroPosterior->fresh()->estado);
        $this->assertSame('Cancelado', $cierre->fresh()->estatus);
    }

    public function test_devolucion_aplica_la_partida_de_renta_exacta_con_producto_repetido(): void
    {
        $usuario = User::factory()->create([
            'role' => 'Supervisor',
            'sucursal_id' => $this->sucursalId,
        ]);
        $this->actingAs($usuario);

        $cliente = Clientes::create([
            'clave' => 'CLI-TRACE-DEV',
            'nombre' => 'Cliente trazabilidad',
            'rfc' => 'XAXX010101000',
            'folio_ine' => 'INE-TRACE-001',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'trace@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);
        $obra = ClienteDireccionEntrega::create([
            'cliente_id' => $cliente->id,
            'nombre_direccion' => 'Obra trazable',
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
            'clave' => 'EQ-TRACE-DEV',
            'descripcion' => 'Equipo repetido',
            'grupo' => 'EQUIPO',
            'linea' => 'RENTA',
            'precio_venta' => 100,
            'existencia' => 0,
        ]);

        $crearNota = function () use ($cliente, $obra): NotasVentaRenta {
            return NotasVentaRenta::create([
                'cliente_id' => $cliente->id,
                'sucursal_id' => $this->sucursalId,
                'direccion_entrega_id' => $obra->id,
                'fecha_emision' => now(),
                'condicion_pago' => 'contado',
                'subtotal' => 0,
                'impuestos_total' => 0,
                'total' => 0,
                'saldo_pendiente' => 0,
                'deposito' => 0,
                'estatus' => 'Activa',
                'tipo_nota_renta' => 'equipo',
            ]);
        };

        $notaUno = $crearNota();
        $notaDos = $crearNota();
        $registroUno = RegistroRenta::create([
            'nota_venta_renta_id' => $notaUno->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'cantidad_devuelta' => 0,
            'dias_renta' => 1,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(2)->toDateString(),
            'estado' => 'Activo',
        ]);
        $registroDos = RegistroRenta::create([
            'nota_venta_renta_id' => $notaDos->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'cantidad_devuelta' => 0,
            'dias_renta' => 1,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(10)->toDateString(),
            'estado' => 'Activo',
        ]);
        NotaEnvio::create([
            'nota_venta_renta_id' => $notaUno->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'inicio_vigencia' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Vigente',
        ]);
        $envioDos = NotaEnvio::create([
            'nota_venta_renta_id' => $notaDos->id,
            'cliente_id' => $cliente->id,
            'fecha_emision' => now()->toDateString(),
            'inicio_vigencia' => now()->toDateString(),
            'estatus' => 'Entregada',
            'estado_renta' => 'Vigente',
        ]);
        $partidaEnvioDos = NotaEnvioPartida::create([
            'nota_envio_id' => $envioDos->id,
            'producto_id' => $producto->id,
            'descripcion' => $producto->descripcion,
            'cantidad' => 1,
            'dias_renta' => 1,
            'fecha_vencimiento' => now()->addDays(10)->toDateString(),
            'cantidad_devuelta' => 0,
            'estado' => 'Activo',
        ]);
        $registroDos->update(['nota_envio_partida_id' => $partidaEnvioDos->id]);
        $devolucion = NotaDevolucionRenta::create([
            'serie' => 'NDR',
            'folio_interno' => 'TRACE-001',
            'nota_venta_renta_id' => $notaDos->id,
            'cliente_id' => $cliente->id,
            'direccion_entrega_id' => $obra->id,
            'fecha_emision' => now()->toDateString(),
            'estatus' => 'Borrador',
        ]);
        $this->assertTrue(NotaDevolucionRenta::whereKey($devolucion->id)->exists());
        NotaDevolucionRentaPartida::create([
            'nota_devolucion_renta_id' => $devolucion->id,
            'registro_renta_id' => $registroDos->id,
            'nota_envio_partida_id' => $partidaEnvioDos->id,
            'producto_id' => $producto->id,
            'descripcion' => $producto->descripcion,
            'cantidad_enviada' => 1,
            'cantidad_devuelta' => 0,
            'cantidad_a_devolver' => 1,
            'cantidad_aplicada' => 0,
        ]);

        $devolucion->aplicarCantidadesRecogidas();

        $this->assertSame(0.0, (float) $registroUno->fresh()->cantidad_devuelta);
        $this->assertSame(1.0, (float) $registroDos->fresh()->cantidad_devuelta);
        $this->assertSame('Activo', $registroUno->fresh()->estado);
        $this->assertSame('Devuelto', $registroDos->fresh()->estado);
        $this->assertSame(1.0, (float) $partidaEnvioDos->fresh()->cantidad_devuelta);
        $this->assertSame('Devuelto', $partidaEnvioDos->fresh()->estado);
        $this->assertSame('Devuelta', $envioDos->fresh()->estado_renta);

        $resultadoCierre = app(CierreDevolucionRentaService::class)->cerrarNotaDeObra(
            $cliente->id,
            $obra->id,
            $notaDos->id,
            userId: auth()->id(),
            sucursalId: $this->sucursalId,
        );
        $cierre = CierreDevolucionRenta::findOrFail($resultadoCierre['cierre_id']);
        app(CierreDevolucionRentaService::class)->cancelar($cierre, auth()->id());

        $this->assertSame(1.0, (float) $registroDos->fresh()->cantidad_devuelta);
        $this->assertSame('Devuelto', $registroDos->fresh()->estado);
        $this->assertSame(1.0, (float) $partidaEnvioDos->fresh()->cantidad_devuelta);
        $this->assertSame('Devuelta', $notaDos->fresh()->estatus);
        $this->assertSame('Devuelta', $envioDos->fresh()->estado_renta);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ResetOperationalDataService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResetOperationalDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_solo_un_administrador_puede_reiniciar_los_datos(): void
    {
        /** @var User $supervisor */
        $supervisor = User::factory()->create([
            'role' => 'Supervisor',
        ]);

        /** @var Authenticatable $authenticatableSupervisor */
        $authenticatableSupervisor = $supervisor;
        $this->actingAs($authenticatableSupervisor);

        $this->expectException(AuthorizationException::class);

        app(ResetOperationalDataService::class)->reset();
    }

    public function test_reinicia_datos_operativos_y_elimina_clientes_y_proveedores(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'Administrador',
        ]);

        /** @var Authenticatable $authenticatableAdmin */
        $authenticatableAdmin = $admin;
        $this->actingAs($authenticatableAdmin);

        $clientId = DB::table('clientes')->insertGetId([
            'clave' => 'CLI-RESET',
            'nombre' => 'Cliente de prueba',
            'rfc' => 'XAXX010101000',
            'regimen' => '616',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Mexico',
            'estado' => 'CDMX',
            'pais' => 'Mexico',
            'telefono' => '5555555555',
            'correo' => 'cliente@example.com',
            'contacto' => 'Contacto',
        ]);
        DB::table('cliente_direcciones_entrega')->insert([
            'cliente_id' => $clientId,
            'nombre_direccion' => 'Entrega principal',
            'calle' => 'Calle',
            'numero_exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Mexico',
            'estado' => 'CDMX',
            'codigo_postal' => '01000',
            'pais' => 'Mexico',
        ]);

        DB::table('proveedores')->insert([
            'clave' => 'PROV-RESET',
            'nombre' => 'Proveedor de prueba',
            'rfc' => 'XAXX010101000',
            'regimen' => '616',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'interior' => '',
            'colonia' => 'Centro',
            'municipio' => 'Mexico',
            'estado' => 'CDMX',
            'pais' => 'Mexico',
            'telefono' => '5555555555',
            'correo' => 'proveedor@example.com',
            'contacto' => 'Contacto',
        ]);

        $productId = DB::table('productos')->insertGetId([
            'clave' => 'PROD-RESET',
            'descripcion' => 'Producto de prueba',
            'grupo' => 'Grupo',
            'linea' => 'Linea',
            'existencia' => 12,
        ]);
        DB::table('movimientos_inventario')->insert([
            'producto_id' => $productId,
            'tipo' => 'entrada',
            'cantidad' => 12,
            'existencia_antes' => 0,
            'existencia_despues' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lineas')->insert([
            'nombre' => 'Linea temporal',
        ]);

        DB::table('grupos')->insert([
            'nombre' => 'Grupo temporal',
        ]);

        DB::table('documento_series')->insert([
            'documento_tipo' => 'prueba',
            'serie' => 'T',
            'ultimo_folio' => 12,
        ]);

        $configurationCount = DB::table('configuracion')->count();

        $tablesReset = app(ResetOperationalDataService::class)->reset();

        $this->assertGreaterThan(0, $tablesReset);
        $this->assertDatabaseMissing('clientes', ['clave' => 'CLI-RESET']);
        $this->assertDatabaseMissing('cliente_direcciones_entrega', ['cliente_id' => $clientId]);
        $this->assertDatabaseMissing('proveedores', ['clave' => 'PROV-RESET']);
        $this->assertDatabaseHas('productos', ['clave' => 'PROD-RESET', 'existencia' => 0]);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame($configurationCount, DB::table('configuracion')->count());
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('lineas', ['nombre' => 'Linea temporal']);
        $this->assertDatabaseHas('grupos', ['nombre' => 'Grupo temporal']);
        $this->assertDatabaseHas('documento_series', [
            'documento_tipo' => 'prueba',
            'serie' => 'T',
            'ultimo_folio' => 0,
        ]);
    }

    public function test_el_comando_reinicia_los_datos_con_force(): void
    {
        $user = User::factory()->create();

        DB::table('lineas')->insert([
            'nombre' => 'Linea temporal',
        ]);

        DB::table('grupos')->insert([
            'nombre' => 'Grupo temporal',
        ]);

        DB::table('documento_series')->insert([
            'documento_tipo' => 'prueba',
            'serie' => 'T',
            'ultimo_folio' => 0,
        ]);

        $this->artisan('sistema:reiniciar-datos', ['--no-interaction' => true])
            ->expectsOutputToContain('Reinicio completado.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('lineas', ['nombre' => 'Linea temporal']);
        $this->assertDatabaseHas('grupos', ['nombre' => 'Grupo temporal']);
        $this->assertDatabaseHas('documento_series', [
            'documento_tipo' => 'prueba',
            'serie' => 'T',
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_reiniciar_datos_limpia_acumulados_de_caja_despues_de_borrar_movimientos(): void
    {
        $cajaId = DB::table('cajas')->insertGetId([
            'nombre' => 'Caja acumulados',
            'estatus' => 'Abierta',
            'saldo_inicial_cash' => 1500,
            'total_ingresos_cash' => 250,
            'total_egresos_cash' => 40,
            'total_diferencia' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('caja_movimientos')->insert([
            'caja_id' => $cajaId,
            'tipo' => 'Ingreso',
            'metodo_pago' => 'Efectivo',
            'importe' => 250,
            'fecha' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ResetOperationalDataService::class)->resetFromCommand();

        $this->assertDatabaseMissing('caja_movimientos', ['caja_id' => $cajaId]);
        $this->assertDatabaseHas('cajas', [
            'id' => $cajaId,
            'total_ingresos_cash' => 0,
            'total_egresos_cash' => 0,
            'total_diferencia' => 0,
        ]);
    }
}

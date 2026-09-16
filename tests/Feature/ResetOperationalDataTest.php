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

    public function test_reinicia_datos_operativos_y_conserva_los_datos_maestros(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'Administrador',
        ]);

        /** @var Authenticatable $authenticatableAdmin */
        $authenticatableAdmin = $admin;
        $this->actingAs($authenticatableAdmin);

        DB::table('clientes')->insert([
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

        DB::table('productos')->insert([
            'clave' => 'PROD-RESET',
            'descripcion' => 'Producto de prueba',
            'grupo' => 'Grupo',
            'linea' => 'Linea',
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
            'ultimo_folio' => 0,
        ]);

        $configurationCount = DB::table('configuracion')->count();

        $tablesReset = app(ResetOperationalDataService::class)->reset();

        $this->assertGreaterThan(0, $tablesReset);
        $this->assertDatabaseHas('clientes', ['clave' => 'CLI-RESET']);
        $this->assertDatabaseHas('proveedores', ['clave' => 'PROV-RESET']);
        $this->assertDatabaseHas('productos', ['clave' => 'PROD-RESET']);
        $this->assertSame($configurationCount, DB::table('configuracion')->count());
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('lineas', ['nombre' => 'Linea temporal']);
        $this->assertDatabaseHas('grupos', ['nombre' => 'Grupo temporal']);
        $this->assertDatabaseHas('documento_series', [
            'documento_tipo' => 'prueba',
            'serie' => 'T',
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

        $this->artisan('sistema:reiniciar-datos', ['--force' => true])
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
}

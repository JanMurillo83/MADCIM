<?php

namespace Tests\Feature;

use App\Models\DispositivoBiometrico;
use App\Models\Empleado;
use App\Models\EmpleadoDispositivoId;
use App\Models\Sucursal;
use App\Models\EventoAdms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recibe_una_checada_y_la_registra_mediante_el_servicio_existente(): void
    {
        [$dispositivo, $empleado] = $this->configurarDispositivo('ADMS-001', '900001');

        $respuesta = $this->postAdms($dispositivo->numero_serie, "900001\t2026-10-21 08:03:00\t0");

        $respuesta->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertContent('OK');
        $this->assertDatabaseHas('checadas', [
            'empleado_id' => $empleado->id,
            'dispositivo' => 'ADMS-001',
            'identificador_externo' => EventoAdms::query()->firstOrFail()->huella_evento,
        ]);
        $this->assertDatabaseHas('eventos_adms', ['estado' => 'procesado']);
    }

    public function test_reenvio_de_la_misma_linea_no_duplica_la_checada(): void
    {
        [$dispositivo] = $this->configurarDispositivo('ADMS-002', '900002');
        $payload = "900002\t2026-10-21 08:04:00\t0";
        $url = "/iclock/cdata?SN={$dispositivo->numero_serie}";

        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'text/plain'], $payload)
            ->assertOk()
            ->assertContent('OK');
        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'text/plain'], $payload)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertContent('OK');

        $this->assertDatabaseCount('checadas', 1);
        $this->assertDatabaseHas('eventos_adms', ['estado' => 'procesado']);
    }

    public function test_empleado_no_vinculado_queda_pendiente(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'MADCIM HUATULCO', 'codigo' => 'HUA', 'activa' => true]);
        $dispositivo = DispositivoBiometrico::create([
            'nombre' => 'Reloj de prueba',
            'numero_serie' => 'ADMS-003',
            'modelo' => 'ZKTeco MB160ID',
            'sucursal_id' => $sucursal->id,
            'token_hash' => 'legacy-token-hash',
            'activo' => true,
        ]);

        $this->postAdms($dispositivo->numero_serie, "900003\t2026-10-21 08:05:00\t0")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertContent('OK');

        $this->assertDatabaseHas('eventos_adms', [
            'identificador' => '900003',
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseCount('checadas', 0);
    }

    public function test_dispositivo_inactivo_no_puede_enviar_checadas(): void
    {
        [$dispositivo] = $this->configurarDispositivo('ADMS-004', '900004');
        $dispositivo->update(['activo' => false]);

        $this->postAdms($dispositivo->numero_serie, "900004\t2026-10-21 08:06:00\t0")
            ->assertUnauthorized();

        $this->assertDatabaseCount('eventos_adms', 0);
        $this->assertDatabaseCount('checadas', 0);
    }

    public function test_linea_malformada_se_conserva_como_rechazada(): void
    {
        [$dispositivo] = $this->configurarDispositivo('ADMS-005', '900005');

        $this->postAdms($dispositivo->numero_serie, 'linea-invalida')
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertContent('ERROR: REJECTED');

        $this->assertDatabaseHas('eventos_adms', [
            'estado' => 'rechazado',
            'error' => 'No fue posible interpretar identificador y fecha de la checada.',
        ]);
    }

    public function test_lote_mixto_confirma_solo_si_no_hay_rechazos(): void
    {
        [$dispositivo] = $this->configurarDispositivo('ADMS-006', '900006');
        $payload = implode("\n", [
            "900006\t2026-10-21 08:07:00\t0",
            'linea-invalida',
        ]);

        $this->postAdms($dispositivo->numero_serie, $payload)
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertContent('ERROR: REJECTED');

        $this->assertDatabaseHas('eventos_adms', ['estado' => 'procesado']);
        $this->assertDatabaseHas('eventos_adms', ['estado' => 'rechazado']);
        $this->assertDatabaseCount('checadas', 1);
    }

    public function test_dispositivo_desconocido_y_serie_ausente_son_rechazados(): void
    {
        $this->postAdms('ADMS-DESCONOCIDO', "900006\t2026-10-21 08:07:00\t0")
            ->assertUnauthorized();

        $this->call('POST', '/iclock/cdata', [], [], [], ['CONTENT_TYPE' => 'text/plain'], "900006\t2026-10-21 08:07:00\t0")
            ->assertUnauthorized();
    }

    /** @return array{DispositivoBiometrico, Empleado} */
    private function configurarDispositivo(string $serie, string $identificador): array
    {
        $sucursal = Sucursal::create(['nombre' => 'MADCIM HUATULCO', 'codigo' => "HUA-{$serie}", 'activa' => true]);
        $empleado = Empleado::create([
            'numero_empleado' => "EMP-{$identificador}",
            'nombre' => 'Empleado',
            'apellido_paterno' => 'Demo',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-01-01',
            'estatus' => 'activo',
        ]);
        $dispositivo = DispositivoBiometrico::create([
            'nombre' => 'Reloj de prueba',
            'numero_serie' => $serie,
            'modelo' => 'ZKTeco MB160ID',
            'sucursal_id' => $sucursal->id,
            'token_hash' => 'legacy-token-hash',
            'activo' => true,
        ]);
        EmpleadoDispositivoId::create([
            'empleado_id' => $empleado->id,
            'dispositivo_biometrico_id' => $dispositivo->id,
            'identificador' => $identificador,
        ]);

        return [$dispositivo, $empleado];
    }

    private function postAdms(string $serie, string $payload)
    {
        return $this->call(
            'POST',
            "/iclock/cdata?SN={$serie}",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $payload,
        );
    }
}

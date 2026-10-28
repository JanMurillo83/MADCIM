<?php

namespace Tests\Feature;

use App\Models\Checada;
use App\Models\DispositivoBiometrico;
use App\Models\Empleado;
use App\Models\EmpleadoDispositivoId;
use App\Models\EventoAdms;
use App\Models\Horario;
use App\Models\Sucursal;
use App\Services\EvaluarAsistenciaService;
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

    public function test_conserva_y_clasifica_cuatro_marcaciones_biometricas(): void
    {
        [$dispositivo, $empleado] = $this->configurarDispositivo('ADMS-007', '900007');
        $horario = Horario::create([
            'nombre' => 'Horario biométrico',
            'sucursal_id' => $empleado->sucursal_id,
            'hora_entrada' => '08:00',
            'hora_salida_comida' => '14:00',
            'hora_regreso_comida' => '15:00',
            'hora_salida' => '18:00',
            'tolerancia_entrada' => 10,
            'tolerancia_regreso_comida' => 10,
            'duracion_comida' => 60,
            'dia_descanso' => 0,
            'activo' => true,
        ]);
        $empleado->update(['horario_id' => $horario->id]);

        foreach ([
            'PIN=900007	TIME=2026-10-21 08:00:00	STATUS=0',
            'PIN=900007	TIME=2026-10-21 14:00:00	STATUS=1',
            'PIN=900007	TIME=2026-10-21 15:00:00	STATUS=0',
            'PIN=900007	TIME=2026-10-21 18:00:00	STATUS=1',
        ] as $linea) {
            $this->postAdms($dispositivo->numero_serie, $linea)->assertOk()->assertContent('OK');
        }

        $this->assertDatabaseCount('checadas', 4);
        $this->assertDatabaseCount('eventos_adms', 4);

        $resultado = app(EvaluarAsistenciaService::class)
            ->evaluar('2026-10-21', $empleado->sucursal_id)
            ->sole();

        $this->assertSame('08:00', $resultado['entrada']->format('H:i'));
        $this->assertSame('14:00', $resultado['salida_comida']->format('H:i'));
        $this->assertSame('15:00', $resultado['regreso_comida']->format('H:i'));
        $this->assertSame('18:00', $resultado['salida']->format('H:i'));
        $this->assertFalse($resultado['requiere_revision']);
    }

    public function test_conserva_y_clasifica_marcaciones_sin_status(): void
    {
        [$dispositivo, $empleado] = $this->configurarDispositivo('ADMS-STATUSLESS', '1001');
        $horario = Horario::create([
            'nombre' => 'Horario sin status',
            'sucursal_id' => $empleado->sucursal_id,
            'hora_entrada' => '08:00',
            'hora_salida_comida' => '14:00',
            'hora_regreso_comida' => '15:00',
            'hora_salida' => '18:00',
            'tolerancia_entrada' => 10,
            'tolerancia_regreso_comida' => 10,
            'duracion_comida' => 60,
            'dia_descanso' => 0,
            'activo' => true,
        ]);
        $empleado->update(['horario_id' => $horario->id]);

        foreach (['08:00', '14:00', '15:00', '18:00'] as $hora) {
            $this->postAdms(
                $dispositivo->numero_serie,
                "PIN=1001	TIME=2026-10-21 {$hora}:00",
            )->assertOk()->assertContent('OK');
        }

        $evento = EventoAdms::query()->where('identificador', '1001')->firstOrFail();
        $this->assertSame('PIN=1001	TIME=2026-10-21 08:00:00', $evento->payload_original);
        $this->assertSame('2026-10-21 08:00:00', $evento->registrada_en->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('checadas', 4);
        $this->assertDatabaseCount('eventos_adms', 4);

        $resultado = app(EvaluarAsistenciaService::class)
            ->evaluar('2026-10-21', $empleado->sucursal_id)
            ->sole();

        $this->assertSame('08:00', $resultado['entrada']->format('H:i'));
        $this->assertSame('14:00', $resultado['salida_comida']->format('H:i'));
        $this->assertSame('15:00', $resultado['regreso_comida']->format('H:i'));
        $this->assertSame('18:00', $resultado['salida']->format('H:i'));
    }

    public function test_recalcula_marcaciones_biometricas_fuera_de_orden_sin_duplicarlas(): void
    {
        [$dispositivo, $empleado] = $this->configurarDispositivo('ADMS-008', '900008');
        $horario = Horario::create([
            'nombre' => 'Horario fuera de orden',
            'sucursal_id' => $empleado->sucursal_id,
            'hora_entrada' => '08:00',
            'hora_salida_comida' => '14:00',
            'hora_regreso_comida' => '15:00',
            'hora_salida' => '18:00',
            'tolerancia_entrada' => 10,
            'tolerancia_regreso_comida' => 10,
            'duracion_comida' => 60,
            'dia_descanso' => 0,
            'activo' => true,
        ]);
        $empleado->update(['horario_id' => $horario->id]);

        foreach ([
            'PIN=900008	TIME=2026-10-21 18:00:00	STATUS=1',
            'PIN=900008	TIME=2026-10-21 08:00:00	STATUS=0',
            'PIN=900008	TIME=2026-10-21 15:00:00	STATUS=0',
            'PIN=900008	TIME=2026-10-21 14:00:00	STATUS=1',
        ] as $linea) {
            $this->postAdms($dispositivo->numero_serie, $linea)->assertOk();
        }

        $this->postAdms($dispositivo->numero_serie, 'PIN=900008	TIME=2026-10-21 14:00:00	STATUS=1')
            ->assertOk();

        $this->assertDatabaseCount('checadas', 4);
        $resultado = app(EvaluarAsistenciaService::class)
            ->evaluar('2026-10-21', $empleado->sucursal_id)
            ->sole();

        $this->assertSame('14:00', $resultado['salida_comida']->format('H:i'));
        $this->assertSame('15:00', $resultado['regreso_comida']->format('H:i'));
        $this->assertSame('18:00', $resultado['salida']->format('H:i'));
    }

    public function test_marcaciones_faltantes_se_conservan_y_quedan_en_revision(): void
    {
        [$dispositivo, $empleado] = $this->configurarDispositivo('ADMS-009', '900009');
        $horario = Horario::create([
            'nombre' => 'Horario con comida incompleta',
            'sucursal_id' => $empleado->sucursal_id,
            'hora_entrada' => '08:00',
            'hora_salida_comida' => '14:00',
            'hora_regreso_comida' => '15:00',
            'hora_salida' => '18:00',
            'tolerancia_entrada' => 10,
            'tolerancia_regreso_comida' => 10,
            'duracion_comida' => 60,
            'dia_descanso' => 0,
            'activo' => true,
        ]);
        $empleado->update(['horario_id' => $horario->id]);

        $this->postAdms($dispositivo->numero_serie, 'PIN=900009	TIME=2026-10-21 14:00:00	STATUS=1')->assertOk();
        $this->postAdms($dispositivo->numero_serie, 'PIN=900009	TIME=2026-10-21 18:00:00	STATUS=1')->assertOk();

        $resultado = app(EvaluarAsistenciaService::class)
            ->evaluar('2026-10-21', $empleado->sucursal_id)
            ->sole();

        $this->assertSame('revision', $resultado['estado']);
        $this->assertTrue($resultado['requiere_revision']);
        $this->assertDatabaseCount('checadas', 2);
    }

    public function test_clasifica_una_jornada_nocturna_con_salida_al_dia_siguiente(): void
    {
        [$dispositivo, $empleado] = $this->configurarDispositivo('ADMS-010', '900010');
        $horario = Horario::create([
            'nombre' => 'Horario nocturno',
            'sucursal_id' => $empleado->sucursal_id,
            'hora_entrada' => '22:00',
            'hora_salida' => '06:00',
            'tolerancia_entrada' => 10,
            'dia_descanso' => 0,
            'activo' => true,
        ]);
        $empleado->update(['horario_id' => $horario->id]);

        $this->postAdms($dispositivo->numero_serie, 'PIN=900010	TIME=2026-10-21 22:00:00	STATUS=0')->assertOk();
        $this->postAdms($dispositivo->numero_serie, 'PIN=900010	TIME=2026-10-22 06:00:00	STATUS=1')->assertOk();

        $resultado = app(EvaluarAsistenciaService::class)
            ->evaluar('2026-10-21', $empleado->sucursal_id)
            ->sole();

        $this->assertSame('22:00', $resultado['entrada']->format('H:i'));
        $this->assertSame('06:00', $resultado['salida']->format('H:i'));
        $this->assertCount(2, Checada::query()->where('empleado_id', $empleado->id)->get());
    }

    public function test_conserva_marcaciones_de_empleados_distintos(): void
    {
        [$dispositivoUno, $empleadoUno] = $this->configurarDispositivo('ADMS-011', '900011');
        [$dispositivoDos, $empleadoDos] = $this->configurarDispositivo('ADMS-012', '900012');

        $this->postAdms($dispositivoUno->numero_serie, 'PIN=900011	TIME=2026-10-21 08:00:00	STATUS=0')->assertOk();
        $this->postAdms($dispositivoDos->numero_serie, 'PIN=900012	TIME=2026-10-21 08:05:00	STATUS=0')->assertOk();

        $this->assertDatabaseHas('checadas', ['empleado_id' => $empleadoUno->id]);
        $this->assertDatabaseHas('checadas', ['empleado_id' => $empleadoDos->id]);
        $this->assertDatabaseCount('checadas', 2);
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

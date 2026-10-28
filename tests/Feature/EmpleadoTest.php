<?php

namespace Tests\Feature;

use App\Enums\EstadoIncidencia;
use App\Enums\MetodoChecada;
use App\Enums\TipoChecada;
use App\Enums\TipoIncidencia;
use App\Filament\Resources\Empleados\EmpleadoResource;
use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Incidencia;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\EvaluarAsistenciaService;
use App\Services\GenerarReporteAsistenciaService;
use App\Services\GestionarIncidenciaService;
use App\Services\RegistrarChecadaService;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EmpleadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_empleado_guarda_su_relacion_de_sucursal_y_nombre_completo(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);

        $empleado = Empleado::create([
            'numero_empleado' => 'RH-001',
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Santos',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);

        $this->assertSame('Ana López Santos', $empleado->nombre_completo);
        $this->assertSame('MADCIM HUATULCO', $empleado->sucursal->nombre);
    }

    public function test_roles_de_rh_pueden_acceder_al_catalogo_de_empleados(): void
    {
        $resource = EmpleadoResource::class;

        $this->assertTrue(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_RH));
        $this->assertTrue(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_NOMINA));
        $this->assertTrue(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_GERENCIA));
        $this->assertFalse(RolePermissions::canAccessResource($resource, RolePermissions::ROLE_EMPLEADO));
    }

    public function test_empleado_puede_tener_un_horario_configurable(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);

        $horario = Horario::create([
            'nombre' => 'Horario estándar',
            'sucursal_id' => $sucursal->id,
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

        $empleado = Empleado::create([
            'numero_empleado' => 'RH-002',
            'nombre' => 'Luis',
            'apellido_paterno' => 'García',
            'sucursal_id' => $sucursal->id,
            'horario_id' => $horario->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);

        $this->assertSame('Horario estándar', $empleado->fresh()->horario->nombre);
        $this->assertSame(10, $horario->tolerancia_entrada);
        $this->assertSame(60, $horario->duracion_comida);
    }

    public function test_registra_checadas_con_servicio_y_no_permite_modificar_el_original(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $empleado = Empleado::create([
            'numero_empleado' => 'RH-003',
            'nombre' => 'Marta',
            'apellido_paterno' => 'Pérez',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);

        $checada = app(RegistrarChecadaService::class)->registrar([
            'empleado_id' => $empleado->id,
            'sucursal_id' => $sucursal->id,
            'tipo' => TipoChecada::Entrada,
            'metodo' => MetodoChecada::Huella,
            'dispositivo' => 'Reloj HUA-01',
            'registrada_en' => '2026-10-20 08:03:00',
        ]);

        $this->assertSame(TipoChecada::Entrada, $checada->tipo);
        $this->assertSame(MetodoChecada::Huella, $checada->metodo);
        $this->assertSame('Reloj HUA-01', $checada->dispositivo);

        $this->expectException(LogicException::class);
        $checada->update(['dispositivo' => 'alterado']);
    }

    public function test_evalua_retardo_comida_excedida_salida_anticipada_y_tiempo_efectivo(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $horario = Horario::create([
            'nombre' => 'Horario estándar',
            'sucursal_id' => $sucursal->id,
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
        $empleado = Empleado::create([
            'numero_empleado' => 'RH-004',
            'nombre' => 'Carlos',
            'apellido_paterno' => 'Ramírez',
            'sucursal_id' => $sucursal->id,
            'horario_id' => $horario->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);
        $registrar = app(RegistrarChecadaService::class);

        foreach ([
            [TipoChecada::Entrada, '08:15'],
            [TipoChecada::SalidaComida, '14:00'],
            [TipoChecada::RegresoComida, '15:15'],
            [TipoChecada::Salida, '17:45'],
        ] as [$tipo, $hora]) {
            $registrar->registrar([
                'empleado_id' => $empleado->id,
                'sucursal_id' => $sucursal->id,
                'tipo' => $tipo,
                'metodo' => MetodoChecada::Nip,
                'registrada_en' => "2026-10-20 {$hora}:00",
            ]);
        }

        $resultado = app(EvaluarAsistenciaService::class)
            ->evaluar('2026-10-20', $sucursal->id)
            ->sole();

        $this->assertSame('retardo', $resultado['estado']);
        $this->assertTrue($resultado['es_retardo']);
        $this->assertSame(30, $resultado['minutos_retardo']);
        $this->assertSame(15, $resultado['minutos_comida_excedidos']);
        $this->assertSame(15, $resultado['minutos_salida_anticipada']);
        $this->assertSame(495, $resultado['minutos_efectivos']);
    }

    public function test_retardo_se_acumula_aunque_falte_la_salida(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $horario = Horario::create([
            'nombre' => 'Horario con tolerancia',
            'sucursal_id' => $sucursal->id,
            'hora_entrada' => '08:00',
            'hora_salida' => '18:00',
            'tolerancia_entrada' => 10,
            'dia_descanso' => 0,
            'activo' => true,
        ]);
        $empleado = Empleado::create([
            'numero_empleado' => 'RH-RET-001',
            'nombre' => 'Empleado',
            'apellido_paterno' => 'Retardo',
            'sucursal_id' => $sucursal->id,
            'horario_id' => $horario->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);

        app(RegistrarChecadaService::class)->registrar([
            'empleado_id' => $empleado->id,
            'sucursal_id' => $sucursal->id,
            'tipo' => TipoChecada::Entrada,
            'metodo' => MetodoChecada::Manual,
            'registrada_en' => '2026-10-20 08:15:00',
        ]);

        $fila = app(EvaluarAsistenciaService::class)->evaluar('2026-10-20', $sucursal->id)->sole();
        $reporte = app(GenerarReporteAsistenciaService::class)->generar('2026-10-20', '2026-10-20', $sucursal->id)->sole();

        $this->assertSame('sin_salida', $fila['estado']);
        $this->assertTrue($fila['es_retardo']);
        $this->assertSame(15, $fila['minutos_retardo']);
        $this->assertSame(1, $reporte['retardos']);
    }

    public function test_incidente_pendiente_puede_aprobarse_y_queda_bloqueado(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $empleado = Empleado::create([
            'numero_empleado' => 'RH-005',
            'nombre' => 'Sofía',
            'apellido_paterno' => 'Mendoza',
            'sucursal_id' => $sucursal->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);
        $usuario = User::factory()->create([
            'role' => 'Recursos Humanos',
            'sucursal_id' => $sucursal->id,
        ]);

        $incidencia = Incidencia::create([
            'empleado_id' => $empleado->id,
            'sucursal_id' => $sucursal->id,
            'tipo' => TipoIncidencia::PermisoSinGoce,
            'fecha_inicio' => '2026-10-20',
            'fecha_fin' => '2026-10-20',
            'dias' => 1,
            'motivo' => 'Trámite personal',
        ]);

        $this->assertSame(EstadoIncidencia::Pendiente, $incidencia->estatus);

        $aprobada = app(GestionarIncidenciaService::class)->aprobar(
            $incidencia,
            $usuario->id,
            'Revisado por RH',
        );

        $this->assertSame(EstadoIncidencia::Aprobada, $aprobada->estatus);
        $this->assertSame($usuario->id, $aprobada->autorizada_por_id);

        $this->expectException(LogicException::class);
        $aprobada->update(['motivo' => 'Modificación no permitida']);
    }

    public function test_reporte_acumula_asistencia_y_horas_extra_aprobadas(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'MADCIM HUATULCO',
            'codigo' => 'HUA',
            'activa' => true,
        ]);
        $horario = Horario::create([
            'nombre' => 'Horario estándar',
            'sucursal_id' => $sucursal->id,
            'hora_entrada' => '08:00',
            'hora_salida' => '18:00',
            'tolerancia_entrada' => 10,
            'tolerancia_regreso_comida' => 10,
            'duracion_comida' => 60,
            'dia_descanso' => 0,
            'activo' => true,
        ]);
        $empleado = Empleado::create([
            'numero_empleado' => 'RH-006',
            'nombre' => 'Jorge',
            'apellido_paterno' => 'Luna',
            'sucursal_id' => $sucursal->id,
            'horario_id' => $horario->id,
            'fecha_ingreso' => '2026-10-20',
            'estatus' => 'activo',
        ]);
        $registrar = app(RegistrarChecadaService::class);
        foreach ([[TipoChecada::Entrada, '08:00'], [TipoChecada::Salida, '18:00']] as [$tipo, $hora]) {
            $registrar->registrar([
                'empleado_id' => $empleado->id,
                'sucursal_id' => $sucursal->id,
                'tipo' => $tipo,
                'metodo' => MetodoChecada::Manual,
                'registrada_en' => "2026-10-20 {$hora}:00",
            ]);
        }

        $usuario = User::factory()->create([
            'role' => 'Recursos Humanos',
            'sucursal_id' => $sucursal->id,
        ]);
        Incidencia::create([
            'empleado_id' => $empleado->id,
            'sucursal_id' => $sucursal->id,
            'tipo' => TipoIncidencia::HoraExtra,
            'estatus' => EstadoIncidencia::Aprobada,
            'fecha_inicio' => '2026-10-20',
            'minutos' => 90,
            'motivo' => 'Inventario autorizado',
            'autorizada_por_id' => $usuario->id,
            'autorizada_en' => now(),
        ]);

        $resultado = app(GenerarReporteAsistenciaService::class)
            ->generar('2026-10-20', '2026-10-20', $sucursal->id)
            ->sole();

        $this->assertSame(1, $resultado['dias_programados']);
        $this->assertSame(1, $resultado['dias_laborados']);
        $this->assertSame(1, $resultado['puntuales']);
        $this->assertSame(90, $resultado['horas_extra_autorizadas']);
    }
}

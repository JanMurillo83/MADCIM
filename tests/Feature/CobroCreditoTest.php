<?php

namespace Tests\Feature;

use App\Filament\Pages\CobrosPendientes;
use App\Filament\Resources\Pagos\Pages\CreatePagos;
use App\Models\Caja;
use App\Models\Clientes;
use App\Models\NotasVentaRenta;
use App\Models\NotasVentaVenta;
use App\Models\Pagos;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\CobroNotaService;
use Database\Seeders\SatCatalogsSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CobroCreditoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $sucursal = Sucursal::create(['nombre' => 'Sucursal caja', 'codigo' => 'CAJA']);
        Caja::findOrFail(1)->update(['sucursal_id' => $sucursal->id]);
    }

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
            'sucursal_id' => $caja->sucursal_id,
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

    #[DataProvider('tiposDocumento')]
    public function test_cobra_en_la_caja_de_la_sucursal_abierta_por_otro_usuario(string $documentoTipo, string $modelo): void
    {
        $usuarioApertura = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update([
            'estatus' => 'Abierta',
            'usuario_apertura_id' => $usuarioApertura->id,
        ]);
        $usuarioCobro = User::factory()->create([
            'role' => 'Cajero',
            'sucursal_id' => $caja->sucursal_id,
        ]);
        $nota = $this->crearNotaPendiente($modelo, $caja->sucursal_id);
        $this->actingAs($usuarioCobro);
        $this->seed(SatCatalogsSeeder::class);

        $pago = app(CobroNotaService::class)->cobrar($documentoTipo, $nota->id, [
            'pagos' => [
                ['forma_pago' => '01', 'importe' => 200, 'importe_recibido' => 300],
                ['forma_pago' => '03', 'importe' => 50],
            ],
        ], $usuarioCobro->id);

        $this->assertSame($caja->id, $pago->caja_id);
        $this->assertSame($usuarioCobro->id, $pago->user_id);
        $this->assertSame(100.0, (float) $pago->cambio);
        $this->assertSame(0.0, (float) $nota->fresh()->saldo_pendiente);
        $this->assertSame($usuarioApertura->id, $caja->fresh()->usuario_apertura_id);
        $this->assertDatabaseHas('caja_movimientos', [
            'caja_id' => $caja->id,
            'user_id' => $usuarioCobro->id,
            'metodo_pago' => 'Efectivo',
            'importe' => 200,
        ]);
        $this->assertDatabaseHas('caja_movimientos', [
            'caja_id' => $caja->id,
            'user_id' => $usuarioCobro->id,
            'metodo_pago' => 'Transferencia',
            'importe' => 50,
        ]);
    }

    #[DataProvider('cajasNoDisponibles')]
    public function test_rechaza_efectivo_sin_caja_abierta_en_la_sucursal_de_la_nota(
        string $documentoTipo,
        string $modelo,
        string $estatus,
        bool $otraSucursal,
    ): void {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        Caja::query()->update(['estatus' => 'Cerrada']);
        $caja->update(['estatus' => $estatus, 'usuario_apertura_id' => $usuario->id]);
        $sucursalId = $otraSucursal
            ? Sucursal::create(['nombre' => 'Otra sucursal', 'codigo' => 'OTRA'])->id
            : $caja->sucursal_id;
        $nota = $this->crearNotaPendiente($modelo, $sucursalId);
        $this->actingAs($usuario);

        try {
            app(CobroNotaService::class)->cobrar($documentoTipo, $nota->id, [
                'pagos' => [['forma_pago' => '01', 'importe' => 250, 'importe_recibido' => 250]],
            ], $usuario->id);
            $this->fail('El cobro en efectivo debe requerir una caja abierta en la sucursal de la nota.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'No hay una caja abierta en la sucursal de la nota para recibir efectivo.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('caja_movimientos', 0);
        $this->assertSame(250.0, (float) $nota->fresh()->saldo_pendiente);
    }

    #[DataProvider('tiposDocumento')]
    public function test_permite_transferencia_sin_caja_abierta(string $documentoTipo, string $modelo): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        Caja::query()->update(['estatus' => 'Cerrada']);
        $nota = $this->crearNotaPendiente($modelo, Caja::findOrFail(1)->sucursal_id);
        $this->actingAs($usuario);
        $this->seed(SatCatalogsSeeder::class);

        $pago = app(CobroNotaService::class)->cobrar($documentoTipo, $nota->id, [
            'pagos' => [['forma_pago' => '03', 'importe' => 250]],
        ], $usuario->id);

        $this->assertNull($pago->caja_id);
        $this->assertSame(0.0, (float) $nota->fresh()->saldo_pendiente);
        $this->assertDatabaseCount('caja_movimientos', 0);
    }

    #[DataProvider('tiposDocumento')]
    public function test_formulario_de_pagos_asigna_caja_de_la_sucursal_abierta_por_otro_usuario(string $documentoTipo, string $modelo): void
    {
        $usuarioApertura = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update(['estatus' => 'Abierta', 'usuario_apertura_id' => $usuarioApertura->id]);
        $nota = $this->crearNotaPendiente($modelo, $caja->sucursal_id);
        $usuarioCobro = User::factory()->create(['role' => 'Cajero', 'sucursal_id' => $caja->sucursal_id]);
        $this->actingAs($usuarioCobro);
        $pagina = new class extends CreatePagos
        {
            public function prepararPago(array $data): array
            {
                return $this->mutateFormDataBeforeCreate($data);
            }
        };

        $data = $pagina->prepararPago([
            'documento_tipo' => $documentoTipo,
            'documento_id' => $nota->id,
        ]);

        $this->assertSame($caja->id, $data['caja_id']);
        $this->assertSame($usuarioCobro->id, $data['user_id']);
    }

    public function test_pago_desde_preview_usa_caja_abierta_por_otro_usuario(): void
    {
        $usuarioApertura = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update(['estatus' => 'Abierta', 'usuario_apertura_id' => $usuarioApertura->id]);
        $nota = $this->crearNotaPendiente(NotasVentaRenta::class, $caja->sucursal_id);
        $usuarioCobro = User::factory()->create(['role' => 'Cajero', 'sucursal_id' => $caja->sucursal_id]);
        $this->actingAs($usuarioCobro);
        $this->seed(SatCatalogsSeeder::class);

        $this->withSession(['_token' => 'cobro-preview-token'])
            ->postJson(route('notas-venta-renta.registrar-pago', $nota->id), [
                '_token' => 'cobro-preview-token',
                'metodo_pago' => 'Efectivo',
                'importe_recibido' => 300,
            ])->assertOk()->assertJson(['success' => true, 'saldo_pendiente' => 0]);

        $this->assertDatabaseHas('pagos', [
            'documento_tipo' => 'notas_venta_renta',
            'documento_id' => $nota->id,
            'caja_id' => $caja->id,
            'user_id' => $usuarioCobro->id,
            'importe' => 250,
            'cambio' => 50,
        ]);
        $this->assertDatabaseHas('caja_movimientos', [
            'caja_id' => $caja->id,
            'user_id' => $usuarioCobro->id,
            'metodo_pago' => 'Efectivo',
            'importe' => 250,
        ]);
    }

    #[DataProvider('tiposDocumento')]
    public function test_cuenta_por_cobrar_abre_formulario_y_se_convierte_a_contado_al_pagar(string $documentoTipo, string $modelo): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update(['estatus' => 'Abierta', 'usuario_apertura_id' => $usuario->id]);
        $nota = $this->crearNotaPendiente($modelo, $caja->sucursal_id);
        $nota->update(['condicion_pago' => 'credito', 'fecha_emision' => now()->subDay()]);
        $this->actingAs($usuario);
        $this->seed(SatCatalogsSeeder::class);

        Livewire::test(CobrosPendientes::class)
            ->set('pestana', 'anteriores')
            ->call('abrirPago', $documentoTipo, $nota->id)
            ->assertActionMounted('pagar')
            ->assertSchemaComponentExists('pagos', checkComponentUsing: fn (Repeater $component): bool => array_values($component->getState()) === [
                ['forma_pago' => '01', 'importe' => 250.0, 'importe_recibido' => 250.0],
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors()
            ->assertNotified('Pago registrado');

        $this->assertSame('contado', $nota->fresh()->condicion_pago);
        $this->assertSame('Pagada', $nota->fresh()->estatus);
        $this->assertSame(0.0, (float) $nota->fresh()->saldo_pendiente);
        $this->assertDatabaseHas('pagos', [
            'documento_tipo' => $documentoTipo,
            'documento_id' => $nota->id,
            'caja_id' => $caja->id,
            'importe' => 250,
        ]);
    }

    #[DataProvider('tiposDocumento')]
    public function test_confirmacion_inicial_no_se_repite_y_permite_cobrar_el_credito_del_dia(string $documentoTipo, string $modelo): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $nota = $this->crearNotaPendiente($modelo, Caja::findOrFail(1)->sucursal_id);
        $nota->update(['condicion_pago' => 'credito', 'fecha_vencimiento_pago' => now()->subDay()]);
        $this->actingAs($usuario);
        $this->seed(SatCatalogsSeeder::class);

        $pagina = Livewire::test(CobrosPendientes::class)
            ->call('abrirPago', $documentoTipo, $nota->id)
            ->assertSchemaComponentExists('confirmacion_credito')
            ->callMountedAction()
            ->assertNotified('Crédito confirmado');

        $this->assertSame('credito', $nota->fresh()->condicion_pago);
        $this->assertNotNull($nota->fresh()->cobro_credito_confirmado_en);
        $this->assertDatabaseCount('pagos', 0);

        $pagina->set('pestana', 'anteriores')
            ->call('abrirPago', $documentoTipo, $nota->id)
            ->assertSchemaComponentExists('pagos', checkComponentUsing: fn (Repeater $component): bool => array_values($component->getState()) === [
                ['forma_pago' => '01', 'importe' => 250.0, 'importe_recibido' => 250.0],
            ])
            ->set('mountedActions.0.data.pagos', [['forma_pago' => '03', 'importe' => 100]])
            ->assertStatus(200)
            ->assertActionMounted('pagar')
            ->callMountedAction()
            ->assertHasNoErrors()
            ->assertHasNoFormErrors()
            ->assertNotified('Pago registrado');

        $this->assertSame('contado', $nota->fresh()->condicion_pago);
        $this->assertSame(150.0, (float) $nota->fresh()->saldo_pendiente);
        $this->assertSame(Clientes::ESTATUS_ACTIVO, $nota->cliente->fresh()->estatus_cliente);
        $this->assertDatabaseHas('pagos', [
            'documento_tipo' => $documentoTipo,
            'documento_id' => $nota->id,
            'importe' => 100,
        ]);
    }

    #[DataProvider('tiposDocumento')]
    public function test_credito_no_cambia_a_contado_si_falla_una_forma_de_pago(string $documentoTipo, string $modelo): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update(['estatus' => 'Abierta', 'usuario_apertura_id' => $usuario->id]);
        $nota = $this->crearNotaPendiente($modelo, $caja->sucursal_id);
        $nota->update(['condicion_pago' => 'credito']);
        $this->actingAs($usuario);
        $this->seed(SatCatalogsSeeder::class);

        try {
            app(CobroNotaService::class)->cobrar($documentoTipo, $nota->id, [
                'pagos' => [
                    ['forma_pago' => '03', 'importe' => 100],
                    ['forma_pago' => '01', 'importe' => 150, 'importe_recibido' => 100],
                ],
            ], $usuario->id);
            $this->fail('El cobro debe rechazar efectivo insuficiente.');
        } catch (RuntimeException $exception) {
            $this->assertSame('El efectivo recibido es menor al importe aplicado.', $exception->getMessage());
        }

        $this->assertSame('credito', $nota->fresh()->condicion_pago);
        $this->assertSame(250.0, (float) $nota->fresh()->saldo_pendiente);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('caja_movimientos', 0);
    }

    public function test_cobra_solo_el_saldo_del_cierre_sin_duplicar_el_deposito_aplicado(): void
    {
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $caja = Caja::findOrFail(1);
        $caja->update(['estatus' => 'Abierta', 'usuario_apertura_id' => $usuario->id]);
        $notaRenta = $this->crearNotaPendiente(NotasVentaRenta::class, $caja->sucursal_id);
        $notaVenta = (new NotasVentaVenta([
            'cliente_id' => $notaRenta->cliente_id,
            'sucursal_id' => $caja->sucursal_id,
            'documento_origen_id' => $notaRenta->id,
            'serie' => 'M',
            'folio' => 'CIERRE-001',
            'fecha_emision' => now()->subDay(),
            'condicion_pago' => 'credito',
            'forma_pago' => '99',
            'total' => 2212,
            'saldo_pendiente' => 2212,
            'estatus' => 'Activa',
        ]))->omitirValidacionEstatusCliente();
        $notaVenta->save();
        $this->actingAs($usuario);
        $this->seed(SatCatalogsSeeder::class);
        $deposito = Pagos::create([
            'documento_tipo' => 'notas_venta_venta',
            'documento_id' => $notaVenta->id,
            'cliente_id' => $notaVenta->cliente_id,
            'fecha_pago' => now()->subDay(),
            'forma_pago' => '99',
            'importe' => 642,
            'user_id' => $usuario->id,
            'referencia' => 'Aplicación de depósito del cierre',
        ]);
        $this->assertSame(1570.0, (float) $notaVenta->fresh()->saldo_pendiente);

        Livewire::test(CobrosPendientes::class)
            ->set('pestana', 'anteriores')
            ->call('abrirPago', 'notas_venta_venta', $notaVenta->id)
            ->assertSchemaComponentExists('pagos', checkComponentUsing: fn (Repeater $component): bool => array_values($component->getState()) === [
                ['forma_pago' => '01', 'importe' => 1570.0, 'importe_recibido' => 1570.0],
            ])
            ->callMountedAction()
            ->assertHasNoErrors()
            ->assertNotified('Pago registrado');

        $this->assertSame('contado', $notaVenta->fresh()->condicion_pago);
        $this->assertSame(0.0, (float) $notaVenta->fresh()->saldo_pendiente);
        $this->assertSame(642.0, (float) $deposito->fresh()->importe);
        $this->assertDatabaseCount('pagos', 2);
        $this->assertDatabaseHas('caja_movimientos', [
            'caja_id' => $caja->id,
            'metodo_pago' => 'Efectivo',
            'importe' => 1570,
        ]);
    }

    public static function tiposDocumento(): array
    {
        return [
            'renta' => ['notas_venta_renta', NotasVentaRenta::class],
            'venta' => ['notas_venta_venta', NotasVentaVenta::class],
        ];
    }

    public static function cajasNoDisponibles(): array
    {
        $casos = [];
        foreach (self::tiposDocumento() as $tipo => [$documentoTipo, $modelo]) {
            $casos[$tipo.' cerrada'] = [$documentoTipo, $modelo, 'Cerrada', false];
            $casos[$tipo.' bloqueada'] = [$documentoTipo, $modelo, 'Bloqueada', false];
            $casos[$tipo.' otra sucursal'] = [$documentoTipo, $modelo, 'Abierta', true];
        }

        return $casos;
    }

    /** @param class-string<Model> $modelo */
    private function crearNotaPendiente(string $modelo, int $sucursalId): Model
    {
        $cliente = Clientes::create([
            'clave' => 'CLI-CAJA',
            'nombre' => 'Cliente caja compartida',
            'rfc' => 'XAXX010101000',
            'folio_ine' => 'INE-CAJA-001',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado' => 'Oaxaca',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'caja@example.com',
            'contacto' => 'Contacto',
            'saldo' => 0,
        ]);

        return $modelo::create([
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursalId,
            'serie' => 'N',
            'folio' => 'CAJA-001',
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'estatus' => 'Activa',
            'total' => 250,
            'saldo_pendiente' => 250,
        ]);
    }
}

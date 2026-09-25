<?php

namespace Tests\Feature;

use App\Filament\Pages\ProductosRentaPorVencer;
use App\Filament\Pages\ProductosRentaVencidos;
use App\Models\Clientes;
use App\Models\NotasVentaRenta;
use App\Models\Productos;
use App\Models\RegistroRenta;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProductosRentaConsultasTest extends TestCase
{
    private int $folio = 100;

    public function test_separan_productos_por_vencer_vencidos_devuelto_y_cancelados(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 10:00:00'));
        /** @var \Illuminate\Contracts\Auth\Authenticatable $usuario */
        $usuario = User::factory()->create(['role' => 'Administrador']);
        $this->actingAs($usuario);

        $producto = Productos::create([
            'clave' => 'EQ-CONSULTA',
            'descripcion' => 'Equipo de consulta',
            'grupo' => 'EQUIPO',
            'linea' => 'EQUIPO',
        ]);
        $cliente = Clientes::create([
            'clave' => 'CLI-CONSULTA',
            'nombre' => 'Cliente de consulta',
            'rfc' => 'XAXX010101000',
            'regimen' => '601',
            'codigo' => '01000',
            'calle' => 'Calle',
            'exterior' => '1',
            'interior' => '1',
            'colonia' => 'Centro',
            'municipio' => 'Alcaldia',
            'estado' => 'CDMX',
            'pais' => 'MEX',
            'telefono' => '5555555555',
            'correo' => 'consulta@example.com',
            'contacto' => 'Contacto',
        ]);

        $porVencer = $this->crearRegistro($producto, $cliente, now()->addDays(3));
        $this->crearRegistro($producto, $cliente, now()->addDays(10));
        $vencido = $this->crearRegistro($producto, $cliente, now()->subDay());
        $this->crearRegistro($producto, $cliente, now()->subDay(), cantidadDevuelta: 1, cantidad: 1, estado: 'Devuelto');
        $this->crearRegistro($producto, $cliente, now()->addDays(2), estatusNota: 'Cancelada');

        $consultaPorVencer = new ProductosRentaPorVencer();
        $consultaVencidos = new ProductosRentaVencidos();

        $this->assertSame([$porVencer->id], $consultaPorVencer->productos()->pluck('id')->all());
        $this->assertSame([$vencido->id], $consultaVencidos->productos()->pluck('id')->all());
    }

    private function crearRegistro(
        Productos $producto,
        Clientes $cliente,
        Carbon $fechaVencimiento,
        int $cantidadDevuelta = 0,
        int $cantidad = 1,
        string $estado = 'Activo',
        string $estatusNota = 'Activa',
    ): RegistroRenta {
        $nota = NotasVentaRenta::create([
            'serie' => 'NR',
            'folio' => (string) ++$this->folio,
            'fecha_emision' => now(),
            'estatus' => $estatusNota,
            'subtotal' => 100,
            'impuestos_total' => 16,
            'total' => 116,
            'saldo_pendiente' => 116,
        ]);

        return RegistroRenta::create([
            'nota_venta_renta_id' => $nota->id,
            'producto_id' => $producto->id,
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre,
            'cantidad' => $cantidad,
            'cantidad_devuelta' => $cantidadDevuelta,
            'dias_renta' => 7,
            'fecha_renta' => now()->toDateString(),
            'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            'estado' => $estado,
        ]);
    }
}

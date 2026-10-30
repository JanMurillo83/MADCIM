<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RentalClosuresMigrationTest extends TestCase
{
    public function test_migracion_de_cierres_se_puede_revertir_y_aplicar_en_sqlite(): void
    {
        $migracion = require database_path('migrations/2026_10_28_173000_allow_multiple_rental_closures_per_obra.php');
        $indiceUnico = 'cierres_devolucion_renta_cliente_id_direccion_entrega_id_unique';
        $indiceCompuesto = 'cierres_devolucion_renta_cliente_direccion_idx';
        $llavesForaneas = Schema::getForeignKeys('cierres_devolucion_renta');

        $this->assertFalse(Schema::hasIndex('cierres_devolucion_renta', $indiceUnico));
        $this->assertTrue(Schema::hasIndex('cierres_devolucion_renta', $indiceCompuesto));
        $this->assertTrue(collect(Schema::getColumns('cierres_devolucion_renta'))->firstWhere('name', 'direccion_entrega_id')['nullable']);

        $migracion->down();

        $this->assertTrue(Schema::hasIndex('cierres_devolucion_renta', $indiceUnico, 'unique'));
        $this->assertFalse(Schema::hasIndex('cierres_devolucion_renta', $indiceCompuesto));
        $this->assertFalse(collect(Schema::getColumns('cierres_devolucion_renta'))->firstWhere('name', 'direccion_entrega_id')['nullable']);
        $this->assertEqualsCanonicalizing($llavesForaneas, Schema::getForeignKeys('cierres_devolucion_renta'));

        $migracion->up();
        $migracion->up();

        $this->assertFalse(Schema::hasIndex('cierres_devolucion_renta', $indiceUnico));
        $this->assertTrue(Schema::hasIndex('cierres_devolucion_renta', $indiceCompuesto));
        $this->assertTrue(collect(Schema::getColumns('cierres_devolucion_renta'))->firstWhere('name', 'direccion_entrega_id')['nullable']);
        $this->assertEqualsCanonicalizing($llavesForaneas, Schema::getForeignKeys('cierres_devolucion_renta'));
    }
}

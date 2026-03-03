<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('sat_tipo_comprobante')->upsert(
            [
                ['clave' => 'I', 'descripcion' => 'Ingreso', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'E', 'descripcion' => 'Egreso', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'T', 'descripcion' => 'Traslado', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'N', 'descripcion' => 'Nómina', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'P', 'descripcion' => 'Pago', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );

        DB::table('sat_moneda')->upsert(
            [
                ['clave' => 'MXN', 'descripcion' => 'Peso Mexicano', 'decimales' => 2, 'porcentaje_variacion' => null, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'decimales', 'porcentaje_variacion', 'updated_at']
        );

        DB::table('sat_exportacion')->upsert(
            [
                ['clave' => '01', 'descripcion' => 'No aplica', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '02', 'descripcion' => 'Definitiva', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '03', 'descripcion' => 'Temporal', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );

        DB::table('sat_uso_cfdi')->upsert(
            [
                ['clave' => 'CP01', 'descripcion' => 'Pagos', 'aplica_fisica' => true, 'aplica_moral' => true, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'aplica_fisica', 'aplica_moral', 'updated_at']
        );

        DB::table('sat_forma_pago')->upsert(
            [
                ['clave' => '01', 'descripcion' => 'Efectivo', 'bancarizado' => false, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '02', 'descripcion' => 'Cheque nominativo', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '03', 'descripcion' => 'Transferencia electrónica de fondos', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '04', 'descripcion' => 'Tarjeta de crédito', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '28', 'descripcion' => 'Tarjeta de débito', 'bancarizado' => true, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '99', 'descripcion' => 'Por definir', 'bancarizado' => false, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'bancarizado', 'updated_at']
        );

        DB::table('sat_metodo_pago')->upsert(
            [
                ['clave' => 'PUE', 'descripcion' => 'Pago en una sola exhibición', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'PPD', 'descripcion' => 'Pago en parcialidades o diferido', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );
    }
}

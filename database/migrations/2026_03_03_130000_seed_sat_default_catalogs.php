<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('sat_tipo_comprobante')) {
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
        }

        if (Schema::hasTable('sat_moneda')) {
            DB::table('sat_moneda')->upsert(
                [
                    ['clave' => 'MXN', 'descripcion' => 'Peso Mexicano', 'decimales' => 2, 'porcentaje_variacion' => null, 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'decimales', 'porcentaje_variacion', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_exportacion')) {
            DB::table('sat_exportacion')->upsert(
                [
                    ['clave' => '01', 'descripcion' => 'No aplica', 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_uso_cfdi')) {
            DB::table('sat_uso_cfdi')->upsert(
                [
                    ['clave' => 'CP01', 'descripcion' => 'Pagos', 'aplica_fisica' => true, 'aplica_moral' => true, 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'aplica_fisica', 'aplica_moral', 'updated_at']
            );
        }

        if (Schema::hasTable('sat_forma_pago')) {
            DB::table('sat_forma_pago')->upsert(
                [
                    ['clave' => '01', 'descripcion' => 'Efectivo', 'bancarizado' => false, 'created_at' => $now, 'updated_at' => $now],
                ],
                ['clave'],
                ['descripcion', 'bancarizado', 'updated_at']
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sat_tipo_comprobante')) {
            DB::table('sat_tipo_comprobante')->whereIn('clave', ['I', 'E', 'T', 'N', 'P'])->delete();
        }

        if (Schema::hasTable('sat_moneda')) {
            DB::table('sat_moneda')->where('clave', 'MXN')->delete();
        }

        if (Schema::hasTable('sat_exportacion')) {
            DB::table('sat_exportacion')->where('clave', '01')->delete();
        }

        if (Schema::hasTable('sat_uso_cfdi')) {
            DB::table('sat_uso_cfdi')->where('clave', 'CP01')->delete();
        }

        if (Schema::hasTable('sat_forma_pago')) {
            DB::table('sat_forma_pago')->where('clave', '01')->delete();
        }
    }
};

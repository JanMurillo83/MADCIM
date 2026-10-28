<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('sat_objeto_imp')->upsert(
            [
                ['clave' => '01', 'descripcion' => 'No objeto de impuesto', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '02', 'descripcion' => 'Sí objeto de impuesto', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '03', 'descripcion' => 'Sí objeto de impuesto y no obligado al desglose', 'created_at' => $now, 'updated_at' => $now],
                ['clave' => '04', 'descripcion' => 'Sí objeto del impuesto y no causa impuesto', 'created_at' => $now, 'updated_at' => $now],
            ],
            ['clave'],
            ['descripcion', 'updated_at']
        );
    }

    public function down(): void {}
};

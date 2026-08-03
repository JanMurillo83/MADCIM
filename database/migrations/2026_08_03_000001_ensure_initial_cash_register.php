<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('cajas')
            ->where('nombre', 'Caja 1')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('cajas')->insert([
            'nombre' => 'Caja 1',
            'estatus' => 'Cerrada',
            'saldo_inicial_cash' => 0,
            'total_ingresos_cash' => 0,
            'total_egresos_cash' => 0,
            'total_diferencia' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('cajas')
            ->where('nombre', 'Caja 1')
            ->where('estatus', 'Cerrada')
            ->where('saldo_inicial_cash', 0)
            ->where('total_ingresos_cash', 0)
            ->where('total_egresos_cash', 0)
            ->where('total_diferencia', 0)
            ->delete();
    }
};

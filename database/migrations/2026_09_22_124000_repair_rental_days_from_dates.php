<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notas_venta_renta')->orderBy('id')->eachById(function (object $nota): void {
            if (!$nota->fecha_emision || !$nota->fecha_vencimiento) {
                return;
            }

            $dias = max(1, Carbon::parse($nota->fecha_emision)->diffInDays(Carbon::parse($nota->fecha_vencimiento), true));
            DB::table('notas_venta_renta')
                ->where('id', $nota->id)
                ->update(['dias_renta' => $dias]);
        });

        DB::table('nota_venta_renta_partidas')->orderBy('id')->eachById(function (object $partida): void {
            if ($partida->dias_renta !== null || !$partida->fecha_vencimiento) {
                return;
            }

            $nota = DB::table('notas_venta_renta')
                ->where('id', $partida->nota_venta_renta_id)
                ->first(['fecha_emision']);
            if (!$nota?->fecha_emision) {
                return;
            }

            $dias = max(1, Carbon::parse($nota->fecha_emision)->diffInDays(Carbon::parse($partida->fecha_vencimiento), true));
            DB::table('nota_venta_renta_partidas')
                ->where('id', $partida->id)
                ->update(['dias_renta' => $dias]);
        });
    }

    public function down(): void
    {
        // La reparación conserva los días válidos derivados de las fechas.
    }
};

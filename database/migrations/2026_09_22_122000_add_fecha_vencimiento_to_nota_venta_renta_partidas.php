<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('nota_venta_renta_partidas', 'fecha_vencimiento')) {
            Schema::table('nota_venta_renta_partidas', function (Blueprint $table): void {
                $table->date('fecha_vencimiento')->nullable()->after('dias_renta');
            });
        }

        DB::table('nota_venta_renta_partidas')->orderBy('id')->eachById(function (object $partida): void {
            if ($partida->fecha_vencimiento) {
                return;
            }

            $nota = DB::table('notas_venta_renta')
                ->where('id', $partida->nota_venta_renta_id)
                ->first(['fecha_emision', 'dias_renta']);
            if (!$nota || !$nota->fecha_emision) {
                return;
            }

            $dias = max(1, (int) ($partida->dias_renta ?? $nota->dias_renta ?? 1));
            DB::table('nota_venta_renta_partidas')
                ->where('id', $partida->id)
                ->update([
                    'fecha_vencimiento' => Carbon::parse($nota->fecha_emision)->addDays($dias)->toDateString(),
                ]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('nota_venta_renta_partidas', 'fecha_vencimiento')) {
            Schema::table('nota_venta_renta_partidas', function (Blueprint $table): void {
                $table->dropColumn('fecha_vencimiento');
            });
        }
    }
};

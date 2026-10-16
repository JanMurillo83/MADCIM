<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('cajas', 'corte_id')) {
            Schema::table('cajas', function (Blueprint $table): void {
                $table->uuid('corte_id')->nullable()->after('fecha_apertura')->index();
            });
        }

        if (! Schema::hasColumn('caja_movimientos', 'corte_id')) {
            Schema::table('caja_movimientos', function (Blueprint $table): void {
                $table->uuid('corte_id')->nullable()->after('caja_id')->index();
            });
        }

        $cajas = \Illuminate\Support\Facades\DB::table('cajas')->get(['id', 'corte_id', 'fecha_apertura']);
        foreach ($cajas as $caja) {
            $corteId = $caja->corte_id ?: (string) Str::uuid();
            \Illuminate\Support\Facades\DB::table('cajas')
                ->where('id', $caja->id)
                ->update(['corte_id' => $corteId]);

            if ($caja->fecha_apertura) {
                \Illuminate\Support\Facades\DB::table('caja_movimientos')
                    ->where('caja_id', $caja->id)
                    ->whereNull('corte_id')
                    ->where('fecha', '>=', $caja->fecha_apertura)
                    ->update(['corte_id' => $corteId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('caja_movimientos', 'corte_id')) {
            Schema::table('caja_movimientos', function (Blueprint $table): void {
                $table->dropColumn('corte_id');
            });
        }

        if (Schema::hasColumn('cajas', 'corte_id')) {
            Schema::table('cajas', function (Blueprint $table): void {
                $table->dropColumn('corte_id');
            });
        }
    }
};

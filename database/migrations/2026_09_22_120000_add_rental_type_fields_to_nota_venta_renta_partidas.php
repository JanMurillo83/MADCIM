<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_venta_renta_partidas', function (Blueprint $table): void {
            if (!Schema::hasColumn('nota_venta_renta_partidas', 'tipo_nota_renta')) {
                $table->string('tipo_nota_renta', 30)->nullable()->after('item')->index();
            }
            if (!Schema::hasColumn('nota_venta_renta_partidas', 'tipo_renta')) {
                $table->string('tipo_renta', 20)->nullable()->after('tipo_nota_renta');
            }
            if (!Schema::hasColumn('nota_venta_renta_partidas', 'duracion_renta')) {
                $table->unsignedInteger('duracion_renta')->nullable()->after('tipo_renta');
            }
            if (!Schema::hasColumn('nota_venta_renta_partidas', 'dias_renta')) {
                $table->unsignedInteger('dias_renta')->nullable()->after('duracion_renta');
            }
            if (!Schema::hasColumn('nota_venta_renta_partidas', 'metros_m2')) {
                $table->decimal('metros_m2', 12, 2)->nullable()->after('dias_renta');
            }
            if (!Schema::hasColumn('nota_venta_renta_partidas', 'deposito')) {
                $table->decimal('deposito', 18, 8)->default(0)->after('total');
            }
        });

        $notas = DB::table('notas_venta_renta')
            ->select('id', 'tipo_nota_renta', 'tipo_renta', 'duracion_renta', 'dias_renta', 'metros_m2')
            ->get()
            ->keyBy('id');

        DB::table('nota_venta_renta_partidas')->orderBy('id')->eachById(function (object $partida) use ($notas): void {
            $nota = $notas->get($partida->nota_venta_renta_id);
            if (!$nota) {
                return;
            }

            DB::table('nota_venta_renta_partidas')
                ->where('id', $partida->id)
                ->update([
                    'tipo_nota_renta' => $partida->tipo_nota_renta ?? $nota->tipo_nota_renta ?? 'equipo',
                    'tipo_renta' => $partida->tipo_renta ?? $nota->tipo_renta ?? 'dia',
                    'duracion_renta' => $partida->duracion_renta ?? $nota->duracion_renta ?? 1,
                    'dias_renta' => $partida->dias_renta ?? $nota->dias_renta ?? 1,
                    'metros_m2' => $partida->metros_m2 ?? $nota->metros_m2,
                    'deposito' => $partida->deposito ?? 0,
                ]);
        });

        if (Schema::hasTable('nota_venta_renta_m2_desglose')
            && !Schema::hasColumn('nota_venta_renta_m2_desglose', 'nota_venta_renta_partida_id')) {
            Schema::table('nota_venta_renta_m2_desglose', function (Blueprint $table): void {
                $table->foreignId('nota_venta_renta_partida_id')
                    ->nullable()
                    ->after('nota_venta_renta_id')
                    ->constrained('nota_venta_renta_partidas')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nota_venta_renta_m2_desglose')
            && Schema::hasColumn('nota_venta_renta_m2_desglose', 'nota_venta_renta_partida_id')) {
            Schema::table('nota_venta_renta_m2_desglose', function (Blueprint $table): void {
                $table->dropForeign(['nota_venta_renta_partida_id']);
                $table->dropColumn('nota_venta_renta_partida_id');
            });
        }

        Schema::table('nota_venta_renta_partidas', function (Blueprint $table): void {
            foreach (['deposito', 'metros_m2', 'dias_renta', 'duracion_renta', 'tipo_renta', 'tipo_nota_renta'] as $column) {
                if (Schema::hasColumn('nota_venta_renta_partidas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

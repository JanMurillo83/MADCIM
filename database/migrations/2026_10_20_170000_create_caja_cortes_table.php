<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('caja_cortes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('corte_id')->unique();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->timestamp('fecha_apertura')->nullable();
            $table->timestamp('fecha_cierre')->nullable()->index();
            $table->foreignId('usuario_apertura_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_cierre_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('saldo_inicial_cash', 12, 2)->default(0);
            $table->decimal('total_ingresos_cash', 12, 2)->default(0);
            $table->decimal('total_egresos_cash', 12, 2)->default(0);
            $table->decimal('efectivo_teorico', 12, 2)->default(0);
            $table->decimal('efectivo_contado', 12, 2)->default(0);
            $table->decimal('total_diferencia', 12, 2)->default(0);
            $table->json('denominaciones_efectivo')->nullable();
            $table->text('observaciones_cierre')->nullable();
            $table->timestamps();
        });

        DB::table('cajas')->where('estatus', 'Cerrada')->get()->each(function (object $caja): void {
            DB::table('caja_cortes')->insert([
                'corte_id' => $caja->corte_id ?: (string) Str::uuid(),
                'caja_id' => $caja->id,
                'sucursal_id' => $caja->sucursal_id,
                'fecha_apertura' => $caja->fecha_apertura,
                'fecha_cierre' => $caja->fecha_cierre ?? $caja->updated_at,
                'usuario_apertura_id' => $caja->usuario_apertura_id,
                'usuario_cierre_id' => $caja->usuario_cierre_id,
                'saldo_inicial_cash' => $caja->saldo_inicial_cash,
                'total_ingresos_cash' => $caja->total_ingresos_cash,
                'total_egresos_cash' => $caja->total_egresos_cash,
                'efectivo_teorico' => $caja->efectivo_teorico ?? 0,
                'efectivo_contado' => $caja->efectivo_contado ?? 0,
                'total_diferencia' => $caja->total_diferencia,
                'denominaciones_efectivo' => $caja->denominaciones_efectivo ? json_encode($caja->denominaciones_efectivo) : null,
                'observaciones_cierre' => $caja->observaciones_cierre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_cortes');
    }
};

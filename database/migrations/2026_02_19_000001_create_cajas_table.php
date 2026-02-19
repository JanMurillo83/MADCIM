<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->foreignId('sucursal_id')->nullable()->index();
            $table->timestamp('fecha_apertura')->nullable();
            $table->foreignId('usuario_apertura_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('saldo_inicial_cash', 12, 2)->default(0);
            $table->enum('estatus', ['Abierta', 'Cerrada', 'Bloqueada'])->default('Abierta')->index();
            $table->timestamp('fecha_cierre')->nullable();
            $table->foreignId('usuario_cierre_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_ingresos_cash', 12, 2)->default(0);
            $table->decimal('total_egresos_cash', 12, 2)->default(0);
            $table->decimal('total_diferencia', 12, 2)->default(0);
            $table->text('observaciones_cierre')->nullable();
            $table->timestamps();
        });

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
        Schema::dropIfExists('cajas');
    }
};

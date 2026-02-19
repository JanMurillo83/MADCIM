<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->enum('tipo', ['Ingreso', 'Egreso', 'Ajuste'])->index();
            $table->string('fuente')->nullable(); // pago, deposito, retiro, ajuste, otros
            $table->string('metodo_pago')->nullable(); // Efectivo, Transferencia, etc.
            $table->decimal('importe', 12, 2);
            $table->string('referencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha')->useCurrent();
            // Polimórfica opcional al origen del movimiento
            $table->nullableMorphs('movimentable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('movimientos_inventario')) {
            Schema::create('movimientos_inventario', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('tipo', ['entrada', 'salida', 'ajuste']);
                $table->decimal('cantidad', 18, 4)->default(0);
                $table->decimal('existencia_antes', 18, 4)->default(0);
                $table->decimal('existencia_despues', 18, 4)->default(0);
                $table->text('motivo')->nullable();
                $table->string('documento_referencia', 50)->nullable();
                $table->timestamp('fecha_movimiento')->useCurrent();
                $table->timestamps();

                $table->index('producto_id');
                $table->index('tipo');
                $table->index('fecha_movimiento');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_devolucion_renta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('direccion_entrega_id')->constrained('cliente_direcciones_entrega')->restrictOnDelete();
            $table->enum('estatus', ['Pendiente', 'PendienteCaja', 'Procesado', 'Cancelado'])->default('Pendiente')->index();
            $table->decimal('deposito_acumulado', 18, 2)->default(0);
            $table->decimal('deposito_aplicado', 18, 2)->default(0);
            $table->decimal('deposito_a_devolver', 18, 2)->default(0);
            $table->decimal('total_faltantes', 18, 2)->default(0);
            $table->decimal('saldo_por_cobrar', 18, 2)->default(0);
            $table->foreignId('nota_venta_venta_id')->nullable()->constrained('notas_venta_venta')->nullOnDelete();
            $table->foreignId('devolucion_renta_id')->nullable()->constrained('devoluciones_renta')->nullOnDelete();
            $table->foreignId('caja_movimiento_id')->nullable()->constrained('caja_movimientos')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamps();
            $table->unique(['cliente_id', 'direccion_entrega_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_devolucion_renta');
    }
};

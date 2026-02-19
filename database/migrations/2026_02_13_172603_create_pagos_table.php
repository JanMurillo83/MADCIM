<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('documento_tipo', 50); // 'notas_venta_renta', 'notas_venta_venta', 'facturas_cfdi'
            $table->unsignedBigInteger('documento_id'); // ID de la nota o factura
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->date('fecha_pago');
            $table->string('forma_pago', 50); // Efectivo, Tarjeta, Transferencia, etc
            $table->decimal('importe', 15, 2);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['documento_tipo', 'documento_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};

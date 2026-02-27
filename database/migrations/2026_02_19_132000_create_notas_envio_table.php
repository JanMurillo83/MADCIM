<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_envio', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 10)->nullable();
            $table->string('folio', 50)->nullable()->index();
            $table->foreignId('nota_venta_renta_id')->constrained('notas_venta_renta')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('direccion_entrega_id')->nullable()->constrained('cliente_direcciones_entrega')->nullOnDelete();
            $table->date('fecha_emision')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estatus', ['Pendiente', 'Enviada', 'Entregada', 'Cancelada'])->default('Pendiente')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nota_envio_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_envio_id')->constrained('notas_envio')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_envio_partidas');
        Schema::dropIfExists('notas_envio');
    }
};

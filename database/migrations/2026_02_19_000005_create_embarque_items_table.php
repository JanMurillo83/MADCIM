<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('embarque_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('embarque_id')->constrained('embarques')->cascadeOnDelete();
            $table->string('documento_tipo'); // notas_venta_renta, etc.
            $table->unsignedBigInteger('documento_id');
            $table->decimal('cantidad_programada', 12, 2)->nullable();
            $table->boolean('entregado')->default(false);
            $table->timestamp('fecha_entrega_real')->nullable();
            $table->string('evidencia_url')->nullable();
            $table->string('recibido_por')->nullable();
            $table->text('observaciones_entrega')->nullable();
            $table->timestamps();
            $table->index(['documento_tipo', 'documento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embarque_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')
                ->constrained('ordenes_compra')
                ->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion');
            $table->decimal('cantidad', 18, 8)->default(0);
            $table->decimal('precio_unitario', 18, 8)->default(0);
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('impuestos', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_partidas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_cfdi_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_cfdi_id')->constrained('facturas_cfdi')->cascadeOnDelete();
            $table->decimal('cantidad', 18, 8)->default(0);
            $table->string('item');
            $table->string('descripcion');
            $table->decimal('valor_unitario', 18, 8)->default(0);
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('impuestos', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_cfdi_partidas');
    }
};

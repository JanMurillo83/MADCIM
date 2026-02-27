<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisiciones_compra', function (Blueprint $table) {
            $table->id();
            $table->string('serie')->nullable();
            $table->string('folio')->nullable();
            $table->dateTime('fecha_emision')->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->decimal('tipo_cambio', 18, 6)->default(1);
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('impuestos_total', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->string('estatus')->default('Nueva');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisiciones_compra');
    }
};

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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('clave');
            $table->string('descripcion');
            $table->decimal('m2_cubre',18,8)->default(0);
            $table->decimal('precio_venta',18,8)->default(0);
            $table->decimal('precio_renta_mes',18,8)->default(0);
            $table->decimal('precio_renta_dia',18,8)->default(0);
            $table->decimal('precio_renta_semana',18,8)->default(0);
            $table->decimal('existencia',18,8)->default(0);
            $table->string('grupo');
            $table->string('linea');
            $table->decimal('largo',18,8)->default(0);
            $table->decimal('ancho',18,8)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

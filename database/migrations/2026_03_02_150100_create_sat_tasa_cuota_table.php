<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_tasa_cuota', function (Blueprint $table) {
            $table->id();
            $table->string('impuesto_clave', 3)->nullable();
            $table->string('tipo_factor_clave', 10)->nullable();
            $table->decimal('tasa_o_cuota', 18, 6)->nullable();
            $table->decimal('minimo', 18, 6)->nullable();
            $table->decimal('maximo', 18, 6)->nullable();
            $table->boolean('traslado')->default(false);
            $table->boolean('retencion')->default(false);
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();

            $table->foreign('impuesto_clave')->references('clave')->on('sat_impuesto')->nullOnDelete();
            $table->foreign('tipo_factor_clave')->references('clave')->on('sat_tipo_factor')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_tasa_cuota');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_partida_impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('partida_type');
            $table->unsignedBigInteger('partida_id');
            $table->string('tipo', 10); // Traslado | Retencion
            $table->string('impuesto', 3)->nullable();
            $table->string('tipo_factor', 10)->nullable();
            $table->decimal('tasa_o_cuota', 18, 6)->nullable();
            $table->decimal('base', 18, 6)->default(0);
            $table->decimal('importe', 18, 6)->default(0);
            $table->timestamps();

            $table->index(['partida_type', 'partida_id']);
            $table->foreign('impuesto')->references('clave')->on('sat_impuesto')->nullOnDelete();
            $table->foreign('tipo_factor')->references('clave')->on('sat_tipo_factor')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_partida_impuestos');
    }
};

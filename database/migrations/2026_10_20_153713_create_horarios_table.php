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
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->time('hora_entrada');
            $table->time('hora_salida_comida')->nullable();
            $table->time('hora_regreso_comida')->nullable();
            $table->time('hora_salida');
            $table->unsignedSmallInteger('tolerancia_entrada')->default(10);
            $table->unsignedSmallInteger('tolerancia_regreso_comida')->default(10);
            $table->unsignedSmallInteger('duracion_comida')->default(60);
            $table->unsignedTinyInteger('dia_descanso')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};

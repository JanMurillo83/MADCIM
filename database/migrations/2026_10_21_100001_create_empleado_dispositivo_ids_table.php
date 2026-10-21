<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_dispositivo_ids', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('dispositivo_biometrico_id')->constrained('dispositivos_biometricos')->cascadeOnDelete();
            $table->string('identificador');
            $table->timestamps();
            $table->unique(['dispositivo_biometrico_id', 'identificador'], 'empleado_dispositivo_identificador_unique');
            $table->unique(['empleado_id', 'dispositivo_biometrico_id'], 'empleado_dispositivo_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_dispositivo_ids');
    }
};

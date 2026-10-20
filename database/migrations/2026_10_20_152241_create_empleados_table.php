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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('numero_empleado', 50)->unique();
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->string('departamento')->nullable();
            $table->string('puesto')->nullable();
            $table->date('fecha_ingreso');
            $table->string('tipo_contrato', 50)->nullable();
            $table->decimal('sueldo_diario', 12, 2)->nullable();
            $table->decimal('sueldo_semanal', 12, 2)->nullable();
            $table->string('estatus', 30)->default('activo');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};

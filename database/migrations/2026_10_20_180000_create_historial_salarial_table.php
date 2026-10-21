<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_salarial', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('sueldo_diario', 12, 2);
            $table->decimal('sueldo_semanal', 12, 2)->nullable();
            $table->string('motivo');
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['empleado_id', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_salarial');
    }
};

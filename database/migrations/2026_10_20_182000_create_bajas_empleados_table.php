<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bajas_empleados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->date('fecha_baja');
            $table->string('tipo', 40);
            $table->text('motivo');
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['empleado_id', 'fecha_baja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bajas_empleados');
    }
};

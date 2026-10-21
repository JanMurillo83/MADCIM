<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('nominas_semanales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('periodo_nomina_id')->constrained('periodos_nomina')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->decimal('sueldo_base', 12, 2)->default(0);
            $table->decimal('horas_extra', 12, 2)->default(0);
            $table->decimal('bonos', 12, 2)->default(0);
            $table->decimal('descuentos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['periodo_nomina_id', 'empleado_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('nominas_semanales'); }
};

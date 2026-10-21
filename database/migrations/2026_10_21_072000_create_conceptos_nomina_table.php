<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('conceptos_nomina', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('periodo_nomina_id')->constrained('periodos_nomina')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->string('concepto', 120);
            $table->decimal('importe', 12, 2);
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['periodo_nomina_id', 'empleado_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('conceptos_nomina'); }
};

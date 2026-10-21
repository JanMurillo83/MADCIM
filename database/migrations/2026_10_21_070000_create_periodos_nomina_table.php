<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('periodos_nomina', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estatus', 20)->default('abierto');
            $table->foreignId('generada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generada_en')->nullable();
            $table->timestamps();
            $table->unique(['fecha_inicio', 'fecha_fin']);
        });
    }
    public function down(): void { Schema::dropIfExists('periodos_nomina'); }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('saldos_vacaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->decimal('dias_asignados', 6, 2);
            $table->decimal('dias_usados', 6, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['empleado_id', 'anio']);
        });
    }
    public function down(): void { Schema::dropIfExists('saldos_vacaciones'); }
};

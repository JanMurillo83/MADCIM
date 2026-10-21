<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('solicitudes_vacaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->foreignId('saldo_vacaciones_id')->constrained('saldos_vacaciones')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('dias', 6, 2);
            $table->string('estatus', 20)->default('pendiente');
            $table->text('motivo')->nullable();
            $table->foreignId('solicitada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('autorizada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('autorizada_en')->nullable();
            $table->text('comentario_autorizacion')->nullable();
            $table->timestamps();
            $table->index(['empleado_id', 'fecha_inicio']);
        });
    }
    public function down(): void { Schema::dropIfExists('solicitudes_vacaciones'); }
};

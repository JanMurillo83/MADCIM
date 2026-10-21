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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('checada_id')->nullable()->constrained('checadas')->nullOnDelete();
            $table->string('tipo', 40);
            $table->string('estatus', 20)->default('pendiente');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->unsignedInteger('minutos')->nullable();
            $table->decimal('dias', 8, 2)->nullable();
            $table->text('motivo');
            $table->text('observaciones')->nullable();
            $table->boolean('afecta_nomina')->default(false);
            $table->foreignId('solicitada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('autorizada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('autorizada_en')->nullable();
            $table->text('comentario_autorizacion')->nullable();
            $table->index(['empleado_id', 'fecha_inicio']);
            $table->index(['sucursal_id', 'estatus']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};

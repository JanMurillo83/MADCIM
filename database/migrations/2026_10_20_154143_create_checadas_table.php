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
        Schema::create('checadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->dateTime('registrada_en');
            $table->string('tipo', 30);
            $table->string('metodo', 30);
            $table->string('dispositivo')->nullable();
            $table->string('identificador_externo')->nullable();
            $table->foreignId('autorizada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_autorizacion')->nullable();
            $table->text('observacion')->nullable();
            $table->index(['empleado_id', 'registrada_en']);
            $table->index(['sucursal_id', 'registrada_en']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checadas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_adms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dispositivo_biometrico_id')->nullable()->constrained('dispositivos_biometricos')->nullOnDelete();
            $table->string('numero_serie')->index();
            $table->string('huella_evento')->unique();
            $table->string('identificador')->nullable();
            $table->dateTime('registrada_en')->nullable();
            $table->string('tipo_operacion', 30);
            $table->string('estado', 30)->default('pendiente');
            $table->foreignId('checada_id')->nullable()->constrained('checadas')->nullOnDelete();
            $table->text('payload_original')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('recibida_en');
            $table->timestamp('procesada_en')->nullable();
            $table->timestamps();
            $table->index(['dispositivo_biometrico_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_adms');
    }
};

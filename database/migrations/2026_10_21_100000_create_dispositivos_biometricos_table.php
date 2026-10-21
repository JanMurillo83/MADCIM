<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos_biometricos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('numero_serie')->unique();
            $table->string('modelo')->nullable();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->string('token_hash');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultima_conexion_en')->nullable();
            $table->timestamp('ultima_checada_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos_biometricos');
    }
};

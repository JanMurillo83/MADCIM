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
        Schema::create('cliente_direcciones_entrega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('nombre_direccion'); // Ej: "Oficina Principal", "Almacén 2"
            $table->string('calle');
            $table->string('numero_exterior');
            $table->string('numero_interior')->nullable();
            $table->string('colonia');
            $table->string('municipio');
            $table->string('estado');
            $table->string('codigo_postal');
            $table->string('pais')->default('México');
            $table->text('referencias')->nullable(); // Referencias adicionales para encontrar el lugar
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_telefono')->nullable();
            $table->boolean('es_principal')->default(false); // Marcar dirección principal
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_direcciones_entrega');
    }
};

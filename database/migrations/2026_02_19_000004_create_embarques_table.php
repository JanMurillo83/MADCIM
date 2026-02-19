<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('embarques', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->nullable()->index();
            $table->timestamp('fecha_programada')->nullable();
            $table->string('vehiculo')->nullable();
            $table->foreignId('chofer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estatus', ['Programado', 'En ruta', 'Entregado', 'Parcial', 'Rechazado', 'Cancelado'])->default('Programado')->index();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('direccion_entrega_id')->nullable()->constrained('cliente_direcciones_entrega')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id_creador')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embarques');
    }
};

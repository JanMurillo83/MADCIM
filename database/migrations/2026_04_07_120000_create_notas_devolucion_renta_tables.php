<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_devolucion_renta', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 10)->nullable();
            $table->string('folio', 50)->nullable()->index();
            $table->foreignId('nota_envio_id')->constrained('notas_envio')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->date('fecha_emision')->nullable();
            $table->enum('estatus', ['Borrador', 'Aplicada'])->default('Borrador')->index();
            $table->text('observaciones')->nullable();
            $table->timestamp('aplicada_en')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nota_devolucion_renta_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_devolucion_renta_id')->constrained('notas_devolucion_renta')->cascadeOnDelete();
            $table->foreignId('nota_envio_partida_id')->nullable()->constrained('nota_envio_partidas')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad_programada', 12, 2)->default(0);
            $table->decimal('cantidad_recogida', 12, 2)->default(0);
            $table->decimal('cantidad_aplicada', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_devolucion_renta_partidas');
        Schema::dropIfExists('notas_devolucion_renta');
    }
};

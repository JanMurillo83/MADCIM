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
        Schema::create('registro_rentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_venta_renta_id')->constrained('notas_venta_renta')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->integer('cantidad')->default(1);
            $table->integer('dias_renta')->default(1);
            $table->date('fecha_renta');
            $table->date('fecha_vencimiento');
            $table->decimal('importe_renta', 10, 2)->default(0);
            $table->decimal('importe_deposito', 10, 2)->default(0);
            $table->enum('estado', ['Activo', 'Devuelto', 'Vencido'])->default('Activo');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_rentas');
    }
};

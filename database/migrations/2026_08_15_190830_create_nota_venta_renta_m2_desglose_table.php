<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nota_venta_renta_m2_desglose')) {
            Schema::create('nota_venta_renta_m2_desglose', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('nota_venta_renta_id');
                $table->unsignedBigInteger('producto_id');
                $table->string('clave', 50)->nullable();
                $table->string('descripcion', 255)->nullable();
                $table->decimal('cantidad', 18, 8)->default(0);
                $table->decimal('m2_cubre', 18, 8)->default(0);
                $table->decimal('m2_total', 18, 8)->default(0);
                $table->string('tipo_madera', 50)->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('nota_venta_renta_m2_desglose', function (Blueprint $table) {
            $table->foreign('nota_venta_renta_id', 'nvr_m2_nota_fk')
                ->references('id')
                ->on('notas_venta_renta')
                ->cascadeOnDelete();
            $table->foreign('producto_id', 'nvr_m2_producto_fk')
                ->references('id')
                ->on('productos')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_venta_renta_m2_desglose');
    }
};

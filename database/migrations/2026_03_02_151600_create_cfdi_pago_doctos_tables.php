<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_pago_doctos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnDelete();
            $table->string('documento_type')->nullable();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->string('uuid', 36)->nullable();
            $table->string('moneda_dr', 3)->nullable();
            $table->decimal('equivalencia_dr', 18, 6)->default(1);
            $table->unsignedInteger('num_parcialidad')->default(1);
            $table->decimal('imp_saldo_ant', 18, 2)->default(0);
            $table->decimal('imp_pagado', 18, 2)->default(0);
            $table->decimal('imp_saldo_insoluto', 18, 2)->default(0);
            $table->string('objeto_imp_dr', 2)->nullable();
            $table->timestamps();

            $table->index(['documento_type', 'documento_id']);
            $table->foreign('moneda_dr')->references('clave')->on('sat_moneda')->nullOnDelete();
            $table->foreign('objeto_imp_dr')->references('clave')->on('sat_objeto_imp')->nullOnDelete();
        });

        Schema::create('cfdi_pago_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_docto_id')->constrained('cfdi_pago_doctos')->cascadeOnDelete();
            $table->string('tipo', 10); // Traslado | Retencion
            $table->string('impuesto', 3)->nullable();
            $table->string('tipo_factor', 10)->nullable();
            $table->decimal('tasa_o_cuota', 18, 6)->nullable();
            $table->decimal('base', 18, 6)->default(0);
            $table->decimal('importe', 18, 6)->default(0);
            $table->timestamps();

            $table->foreign('impuesto')->references('clave')->on('sat_impuesto')->nullOnDelete();
            $table->foreign('tipo_factor')->references('clave')->on('sat_tipo_factor')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_pago_impuestos');
        Schema::dropIfExists('cfdi_pago_doctos');
    }
};

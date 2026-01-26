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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->string('serie')->nullable();
            $table->string('folio')->nullable();
            $table->dateTime('fecha_emision')->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->decimal('tipo_cambio', 18, 6)->default(1);
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('impuestos_total', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->string('estatus')->default('borrador');
            $table->string('uso_cfdi', 10)->nullable();
            $table->string('forma_pago', 5)->nullable();
            $table->string('metodo_pago', 5)->nullable();
            $table->string('regimen_fiscal_receptor', 5)->nullable();
            $table->string('rfc_emisor', 13)->nullable();
            $table->string('rfc_receptor', 13)->nullable();
            $table->string('razon_social_receptor')->nullable();
            $table->string('cfdi_uuid', 36)->nullable();
            $table->foreignId('documento_origen_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};

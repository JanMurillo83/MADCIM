<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_regimen_fiscal', function (Blueprint $table) {
            $table->string('clave', 5)->primary();
            $table->string('descripcion');
            $table->boolean('aplica_fisica')->default(false);
            $table->boolean('aplica_moral')->default(false);
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_uso_cfdi', function (Blueprint $table) {
            $table->string('clave', 10)->primary();
            $table->string('descripcion');
            $table->boolean('aplica_fisica')->default(false);
            $table->boolean('aplica_moral')->default(false);
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_forma_pago', function (Blueprint $table) {
            $table->string('clave', 5)->primary();
            $table->string('descripcion');
            $table->boolean('bancarizado')->default(false);
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_metodo_pago', function (Blueprint $table) {
            $table->string('clave', 5)->primary();
            $table->string('descripcion');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_moneda', function (Blueprint $table) {
            $table->string('clave', 3)->primary();
            $table->string('descripcion');
            $table->unsignedSmallInteger('decimales')->default(2);
            $table->decimal('porcentaje_variacion', 8, 5)->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_tipo_comprobante', function (Blueprint $table) {
            $table->string('clave', 1)->primary();
            $table->string('descripcion');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_exportacion', function (Blueprint $table) {
            $table->string('clave', 2)->primary();
            $table->string('descripcion');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_objeto_imp', function (Blueprint $table) {
            $table->string('clave', 2)->primary();
            $table->string('descripcion');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_impuesto', function (Blueprint $table) {
            $table->string('clave', 3)->primary();
            $table->string('descripcion');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_tipo_factor', function (Blueprint $table) {
            $table->string('clave', 10)->primary();
            $table->string('descripcion');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_tipo_relacion', function (Blueprint $table) {
            $table->string('clave', 2)->primary();
            $table->string('descripcion');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_motivo_cancelacion', function (Blueprint $table) {
            $table->string('clave', 2)->primary();
            $table->string('descripcion');
            $table->boolean('requiere_folio_sustitucion')->default(false);
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_clave_prod_serv', function (Blueprint $table) {
            $table->string('clave', 8)->primary();
            $table->string('descripcion');
            $table->string('palabras_similares')->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_clave_unidad', function (Blueprint $table) {
            $table->string('clave', 3)->primary();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('simbolo')->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('sat_codigo_postal', function (Blueprint $table) {
            $table->string('clave', 5)->primary();
            $table->string('estado')->nullable();
            $table->string('municipio')->nullable();
            $table->string('localidad')->nullable();
            $table->string('colonia')->nullable();
            $table->string('descripcion')->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_codigo_postal');
        Schema::dropIfExists('sat_clave_unidad');
        Schema::dropIfExists('sat_clave_prod_serv');
        Schema::dropIfExists('sat_motivo_cancelacion');
        Schema::dropIfExists('sat_tipo_relacion');
        Schema::dropIfExists('sat_tipo_factor');
        Schema::dropIfExists('sat_impuesto');
        Schema::dropIfExists('sat_objeto_imp');
        Schema::dropIfExists('sat_exportacion');
        Schema::dropIfExists('sat_tipo_comprobante');
        Schema::dropIfExists('sat_moneda');
        Schema::dropIfExists('sat_metodo_pago');
        Schema::dropIfExists('sat_forma_pago');
        Schema::dropIfExists('sat_uso_cfdi');
        Schema::dropIfExists('sat_regimen_fiscal');
    }
};

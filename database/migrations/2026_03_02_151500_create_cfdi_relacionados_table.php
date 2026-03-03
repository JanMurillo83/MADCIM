<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_relacionados', function (Blueprint $table) {
            $table->id();
            $table->string('documento_type');
            $table->unsignedBigInteger('documento_id');
            $table->string('tipo_relacion', 2)->nullable();
            $table->string('uuid_relacionado', 36)->nullable();
            $table->string('documento_relacionado_type')->nullable();
            $table->unsignedBigInteger('documento_relacionado_id')->nullable();
            $table->timestamps();

            $table->index(['documento_type', 'documento_id'], 'cfdi_rel_documento_idx');
            $table->index(['documento_relacionado_type', 'documento_relacionado_id'], 'cfdi_rel_doc_rel_idx');
            $table->foreign('tipo_relacion')->references('clave')->on('sat_tipo_relacion')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_relacionados');
    }
};

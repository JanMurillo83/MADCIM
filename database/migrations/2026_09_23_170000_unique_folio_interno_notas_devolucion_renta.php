<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table): void {
            $table->unique('folio_interno', 'notas_devolucion_renta_folio_interno_unique');
        });
    }

    public function down(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table): void {
            $table->dropUnique('notas_devolucion_renta_folio_interno_unique');
        });
    }
};

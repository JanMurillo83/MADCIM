<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            if (!Schema::hasColumn('notas_devolucion_renta', 'folio_interno')) {
                $table->string('folio_interno', 100)
                    ->nullable()
                    ->after('folio')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas_devolucion_renta', function (Blueprint $table) {
            if (Schema::hasColumn('notas_devolucion_renta', 'folio_interno')) {
                $table->dropIndex(['folio_interno']);
                $table->dropColumn('folio_interno');
            }
        });
    }
};

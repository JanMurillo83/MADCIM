<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('devoluciones_renta', 'folio_interno')) {
            Schema::table('devoluciones_renta', function (Blueprint $table): void {
                $table->string('folio_interno')->nullable()->after('folio');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('devoluciones_renta', 'folio_interno')) {
            Schema::table('devoluciones_renta', function (Blueprint $table): void {
                $table->dropColumn('folio_interno');
            });
        }
    }
};

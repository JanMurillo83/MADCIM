<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clientes') || Schema::hasColumn('clientes', 'folio_ine')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table): void {
            $table->string('folio_ine', 30)->nullable()->after('ine');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clientes') || ! Schema::hasColumn('clientes', 'folio_ine')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropColumn('folio_ine');
        });
    }
};

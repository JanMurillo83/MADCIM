<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clientes')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('clientes', 'curp')) {
                $table->string('curp', 18)->nullable()->after('rfc');
            }

            if (! Schema::hasColumn('clientes', 'ine')) {
                $table->string('ine')->nullable()->after('curp');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('clientes')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table): void {
            if (Schema::hasColumn('clientes', 'ine')) {
                $table->dropColumn('ine');
            }

            if (Schema::hasColumn('clientes', 'curp')) {
                $table->dropColumn('curp');
            }
        });
    }
};

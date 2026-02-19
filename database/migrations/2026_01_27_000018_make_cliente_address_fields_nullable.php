<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('calle')->nullable()->change();
            $table->string('exterior')->nullable()->change();
            $table->string('interior')->nullable()->change();
            $table->string('colonia')->nullable()->change();
            $table->string('municipio')->nullable()->change();
            $table->string('estado')->nullable()->change();
            $table->string('pais')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('calle')->nullable(false)->change();
            $table->string('exterior')->nullable(false)->change();
            $table->string('interior')->nullable(false)->change();
            $table->string('colonia')->nullable(false)->change();
            $table->string('municipio')->nullable(false)->change();
            $table->string('estado')->nullable(false)->change();
            $table->string('pais')->nullable(false)->change();
        });
    }
};

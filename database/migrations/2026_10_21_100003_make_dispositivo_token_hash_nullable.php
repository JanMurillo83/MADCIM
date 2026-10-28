<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispositivos_biometricos', function (Blueprint $table): void {
            $table->string('token_hash')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dispositivos_biometricos', function (Blueprint $table): void {
            $table->string('token_hash')->nullable(false)->change();
        });
    }
};

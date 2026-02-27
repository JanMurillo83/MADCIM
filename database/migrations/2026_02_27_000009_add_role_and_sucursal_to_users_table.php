<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Vendedor')->after('password');
            $table->foreignId('sucursal_id')
                ->nullable()
                ->after('role')
                ->constrained('sucursales')
                ->nullOnDelete();
        });

        DB::table('users')
            ->where('email', 'admin@madcim.com')
            ->update(['role' => 'Administrador']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sucursal_id']);
            $table->dropColumn(['role', 'sucursal_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_rentas', function (Blueprint $table) {
            $table->decimal('cantidad_devuelta', 18, 8)->default(0)->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('registro_rentas', function (Blueprint $table) {
            $table->dropColumn('cantidad_devuelta');
        });
    }
};

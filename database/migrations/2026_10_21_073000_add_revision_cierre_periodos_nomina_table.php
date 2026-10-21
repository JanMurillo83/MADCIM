<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('periodos_nomina', function (Blueprint $table): void {
            $table->foreignId('revisada_por_id')->nullable()->constrained('users')->nullOnDelete()->after('generada_en');
            $table->timestamp('revisada_en')->nullable()->after('revisada_por_id');
            $table->foreignId('cerrada_por_id')->nullable()->constrained('users')->nullOnDelete()->after('revisada_en');
            $table->timestamp('cerrada_en')->nullable()->after('cerrada_por_id');
        });
    }
    public function down(): void {
        Schema::table('periodos_nomina', function (Blueprint $table): void {
            $table->dropForeign(['revisada_por_id']);
            $table->dropForeign(['cerrada_por_id']);
            $table->dropColumn(['revisada_por_id', 'revisada_en', 'cerrada_por_id', 'cerrada_en']);
        });
    }
};

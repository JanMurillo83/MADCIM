<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['lugar_expedicion', 'domicilio_fiscal_receptor'] as $column) {
            $foreignKey = collect(Schema::getForeignKeys('facturas_cfdi'))
                ->first(fn (array $key): bool => in_array($column, $key['columns'], true)
                    && $key['foreign_table'] === 'sat_codigo_postal');

            if ($foreignKey === null) {
                continue;
            }

            Schema::table('facturas_cfdi', function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign($foreignKey['name'] ?? $foreignKey['columns']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('facturas_cfdi', function (Blueprint $table) {
            $table->foreign('lugar_expedicion')
                ->references('clave')
                ->on('sat_codigo_postal')
                ->nullOnDelete();
            $table->foreign('domicilio_fiscal_receptor')
                ->references('clave')
                ->on('sat_codigo_postal')
                ->nullOnDelete();
        });
    }
};

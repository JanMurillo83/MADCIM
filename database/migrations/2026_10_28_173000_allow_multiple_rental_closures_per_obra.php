<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = DB::select('SHOW INDEX FROM cierres_devolucion_renta');
        $uniqueExists = collect($indexes)->contains(
            fn (object $index): bool => $index->Key_name === 'cierres_devolucion_renta_cliente_id_direccion_entrega_id_unique',
        );
        $compositeIndexExists = collect($indexes)->contains(
            fn (object $index): bool => $index->Key_name === 'cierres_devolucion_renta_cliente_direccion_idx',
        );

        // The old unique index also supports the cliente_id foreign key, so
        // replace it before dropping it.
        if (! $compositeIndexExists) {
            DB::statement('ALTER TABLE cierres_devolucion_renta ADD INDEX cierres_devolucion_renta_cliente_direccion_idx (cliente_id, direccion_entrega_id)');
        }

        if ($uniqueExists) {
            $directionForeignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'cierres_devolucion_renta')
                ->where('CONSTRAINT_NAME', 'cierres_devolucion_renta_direccion_entrega_id_foreign')
                ->exists();

            if ($directionForeignKeyExists) {
                DB::statement('ALTER TABLE cierres_devolucion_renta DROP FOREIGN KEY cierres_devolucion_renta_direccion_entrega_id_foreign');
            }

            DB::statement('ALTER TABLE cierres_devolucion_renta DROP INDEX cierres_devolucion_renta_cliente_id_direccion_entrega_id_unique');
        }

        DB::statement('ALTER TABLE cierres_devolucion_renta MODIFY direccion_entrega_id BIGINT UNSIGNED NULL');

        $directionForeignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'cierres_devolucion_renta')
            ->where('CONSTRAINT_NAME', 'cierres_devolucion_renta_direccion_entrega_id_foreign')
            ->exists();

        if (! $directionForeignKeyExists) {
            DB::statement('ALTER TABLE cierres_devolucion_renta ADD CONSTRAINT cierres_devolucion_renta_direccion_entrega_id_foreign FOREIGN KEY (direccion_entrega_id) REFERENCES cliente_direcciones_entrega (id)');
        }
    }

    public function down(): void
    {
        if (DB::table('cierres_devolucion_renta')->whereNull('direccion_entrega_id')->exists()) {
            throw new RuntimeException('No se puede revertir: hay cierres de obras sin dirección registrada.');
        }

        $duplicados = DB::table('cierres_devolucion_renta')
            ->select('cliente_id', 'direccion_entrega_id')
            ->groupBy('cliente_id', 'direccion_entrega_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicados) {
            throw new RuntimeException('No se puede revertir: ya hay varias clausuras para una obra.');
        }

        DB::statement('ALTER TABLE cierres_devolucion_renta DROP FOREIGN KEY cierres_devolucion_renta_direccion_entrega_id_foreign');
        DB::statement('ALTER TABLE cierres_devolucion_renta DROP FOREIGN KEY cierres_devolucion_renta_cliente_id_foreign');
        DB::statement('ALTER TABLE cierres_devolucion_renta DROP INDEX cierres_devolucion_renta_cliente_direccion_idx');
        DB::statement('ALTER TABLE cierres_devolucion_renta MODIFY direccion_entrega_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE cierres_devolucion_renta ADD UNIQUE INDEX cierres_devolucion_renta_cliente_id_direccion_entrega_id_unique (cliente_id, direccion_entrega_id)');
        DB::statement('ALTER TABLE cierres_devolucion_renta ADD CONSTRAINT cierres_devolucion_renta_cliente_id_foreign FOREIGN KEY (cliente_id) REFERENCES clientes (id)');
        DB::statement('ALTER TABLE cierres_devolucion_renta ADD CONSTRAINT cierres_devolucion_renta_direccion_entrega_id_foreign FOREIGN KEY (direccion_entrega_id) REFERENCES cliente_direcciones_entrega (id)');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE notas_devolucion_renta MODIFY estatus ENUM('Borrador','Aplicada','Cancelada') NOT NULL DEFAULT 'Borrador'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE notas_devolucion_renta MODIFY estatus ENUM('Borrador','Aplicada') NOT NULL DEFAULT 'Borrador'");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Expandir enum primero para permitir los nuevos valores antes de convertir datos.
        DB::statement("ALTER TABLE notas_devolucion_renta MODIFY estatus ENUM('Pendiente','Parcial','Devuelta','Cancelada','Borrador','Aplicada') NOT NULL DEFAULT 'Pendiente'");

        DB::statement("UPDATE notas_devolucion_renta SET estatus = 'Pendiente' WHERE estatus = 'Borrador'");
        DB::statement("UPDATE notas_devolucion_renta SET estatus = 'Devuelta' WHERE estatus = 'Aplicada'");

        DB::statement("ALTER TABLE notas_envio MODIFY estado_renta ENUM('Pendiente','Parcial','Devuelta','Vigente','Vencido') NOT NULL DEFAULT 'Pendiente'");

        DB::statement("UPDATE notas_envio SET estado_renta = 'Pendiente' WHERE estado_renta = 'Vigente'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Asegurar que los valores legacy existen en enum antes de mapear en reversa.
        DB::statement("ALTER TABLE notas_devolucion_renta MODIFY estatus ENUM('Pendiente','Parcial','Devuelta','Cancelada','Borrador','Aplicada') NOT NULL DEFAULT 'Pendiente'");

        DB::statement("UPDATE notas_envio SET estado_renta = 'Vigente' WHERE estado_renta IN ('Pendiente','Parcial')");
        DB::statement("ALTER TABLE notas_envio MODIFY estado_renta ENUM('Vigente','Vencido','Devuelta') NOT NULL DEFAULT 'Vigente'");

        DB::statement("UPDATE notas_devolucion_renta SET estatus = 'Borrador' WHERE estatus = 'Pendiente'");
        DB::statement("UPDATE notas_devolucion_renta SET estatus = 'Aplicada' WHERE estatus IN ('Parcial','Devuelta')");
        DB::statement("ALTER TABLE notas_devolucion_renta MODIFY estatus ENUM('Borrador','Aplicada','Cancelada') NOT NULL DEFAULT 'Borrador'");
    }
};

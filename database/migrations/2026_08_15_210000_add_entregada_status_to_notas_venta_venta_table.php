<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE notas_venta_venta MODIFY estatus_envio ENUM('Pendiente de Envío','Enviada','Entregada') NOT NULL DEFAULT 'Pendiente de Envío'");
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("UPDATE notas_venta_venta SET estatus_envio = 'Enviada' WHERE estatus_envio = 'Entregada'");
        DB::statement("ALTER TABLE notas_venta_venta MODIFY estatus_envio ENUM('Pendiente de Envío','Enviada') NOT NULL DEFAULT 'Pendiente de Envío'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notas_envio MODIFY COLUMN estatus ENUM('Pendiente', 'Enviada', 'En Tránsito', 'Entregada', 'Devuelta', 'Cancelada') DEFAULT 'Pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notas_envio MODIFY COLUMN estatus ENUM('Pendiente', 'Enviada', 'Entregada', 'Cancelada') DEFAULT 'Pendiente'");
    }
};

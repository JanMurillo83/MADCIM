<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->string('nombre');
            $table->string('rfc');
            $table->string('regimen');
            $table->string('codigo');
            $table->string('calle');
            $table->string('exterior');
            $table->string('interior');
            $table->string('colonia');
            $table->string('municipio');
            $table->string('estado');
            $table->string('pais');
            $table->string('telefono');
            $table->string('correo');
            $table->decimal('descuento', 18, 8)->default(0);
            $table->integer('lista')->default(1);
            $table->string('contacto');
            $table->integer('dias_credito')->default(0);
            $table->decimal('saldo', 18, 8)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->foreignId('recepcion_compra_id')->unique()->constrained('recepciones_compra')->cascadeOnDelete();
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->decimal('tipo_cambio', 18, 6)->default(1);
            $table->decimal('importe', 18, 8)->default(0);
            $table->decimal('saldo_pendiente', 18, 8)->default(0);
            $table->string('estatus')->default('Pendiente');
            $table->timestamps();
        });

        $recepcionesCerradas = DB::table('recepciones_compra as recepciones')
            ->join('proveedores', 'proveedores.id', '=', 'recepciones.proveedor_id')
            ->where('recepciones.estatus', 'Cerrada')
            ->select([
                'recepciones.id',
                'recepciones.proveedor_id',
                'recepciones.fecha_emision',
                'recepciones.moneda',
                'recepciones.tipo_cambio',
                'recepciones.total',
                'proveedores.dias_credito',
            ])
            ->get();

        foreach ($recepcionesCerradas as $recepcion) {
            $fechaEmision = $recepcion->fecha_emision
                ? substr((string) $recepcion->fecha_emision, 0, 10)
                : now()->toDateString();

            DB::table('cuentas_por_pagar')->insert([
                'proveedor_id' => $recepcion->proveedor_id,
                'recepcion_compra_id' => $recepcion->id,
                'fecha_emision' => $fechaEmision,
                'fecha_vencimiento' => date('Y-m-d', strtotime($fechaEmision . ' +' . max(0, (int) $recepcion->dias_credito) . ' days')),
                'moneda' => $recepcion->moneda,
                'tipo_cambio' => $recepcion->tipo_cambio,
                'importe' => $recepcion->total,
                'saldo_pendiente' => $recepcion->total,
                'estatus' => 'Pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($recepcionesCerradas->pluck('proveedor_id')->unique() as $proveedorId) {
            DB::table('proveedores')->where('id', $proveedorId)->update([
                'saldo' => DB::table('cuentas_por_pagar')
                    ->where('proveedor_id', $proveedorId)
                    ->where('estatus', '!=', 'Cancelada')
                    ->sum('saldo_pendiente'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
    }
};

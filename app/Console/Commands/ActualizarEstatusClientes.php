<?php

namespace App\Console\Commands;

use App\Models\Clientes;
use Illuminate\Console\Command;

class ActualizarEstatusClientes extends Command
{
    protected $signature = 'clientes:actualizar-estatus';

    protected $description = 'Actualiza los estatus de clientes con cuentas por cobrar vencidas';

    public function handle(): int
    {
        $actualizados = 0;

        Clientes::query()->eachById(function (Clientes $cliente) use (&$actualizados): void {
            $estatusAnterior = $cliente->estatus_cliente;
            $cliente->actualizarEstatusPorVencimiento();

            if ($cliente->estatus_cliente !== $estatusAnterior) {
                $cliente->saveQuietly();
                $actualizados++;
            }
        });

        $this->info("Estatus de clientes actualizados: {$actualizados}.");

        return self::SUCCESS;
    }
}

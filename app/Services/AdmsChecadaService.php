<?php

namespace App\Services;

use App\Enums\MetodoChecada;
use App\Enums\TipoChecada;
use App\Models\DispositivoBiometrico;
use App\Models\EmpleadoDispositivoId;
use App\Models\EventoAdms;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdmsChecadaService
{
    public function inicializar(DispositivoBiometrico $dispositivo): string
    {
        $dispositivo->update(['ultima_conexion_en' => now()]);

        return "GET OPTION FROM: {$dispositivo->numero_serie}\r\nStamp=0\r\n";
    }

    /**
     * @return array{recibidos: int, aceptados: int, duplicados: int, pendientes: int, rechazados: int}
     */
    public function recibir(DispositivoBiometrico $dispositivo, string $payload, string $operacion = 'cdata'): array
    {
        $dispositivo->update(['ultima_conexion_en' => now()]);
        $lineas = preg_split('/\r\n|\r|\n/', trim($payload), -1, PREG_SPLIT_NO_EMPTY);
        $resultado = ['recibidos' => 0, 'aceptados' => 0, 'duplicados' => 0, 'pendientes' => 0, 'rechazados' => 0];

        foreach ($lineas as $linea) {
            $resultado['recibidos']++;
            $estado = $this->procesarLinea($dispositivo, trim($linea), $operacion);
            $resultado[$estado]++;
        }

        return $resultado;
    }

    private function procesarLinea(DispositivoBiometrico $dispositivo, string $linea, string $operacion): string
    {
        $huella = hash('sha256', "{$dispositivo->numero_serie}|{$linea}");
        $evento = EventoAdms::query()->where('huella_evento', $huella)->first();

        if ($evento) {
            return 'duplicados';
        }

        $datos = $this->interpretarLinea($linea);
        $evento = EventoAdms::create([
            'dispositivo_biometrico_id' => $dispositivo->id,
            'numero_serie' => $dispositivo->numero_serie,
            'huella_evento' => $huella,
            'identificador' => $datos['identificador'] ?? null,
            'registrada_en' => $datos['registrada_en'] ?? null,
            'tipo_operacion' => $operacion,
            'payload_original' => $linea,
            'recibida_en' => now(),
        ]);

        if (!$datos['identificador'] || !$datos['registrada_en']) {
            return $this->rechazar($evento, 'No fue posible interpretar identificador y fecha de la checada.');
        }

        $vinculo = EmpleadoDispositivoId::query()
            ->where('dispositivo_biometrico_id', $dispositivo->id)
            ->where('identificador', $datos['identificador'])
            ->with('empleado')
            ->first();

        if (!$vinculo || !$vinculo->empleado) {
            $evento->update(['estado' => 'pendiente', 'error' => 'Empleado no vinculado.']);

            return 'pendientes';
        }

        try {
            $checada = DB::transaction(function () use ($dispositivo, $evento, $datos, $vinculo): \App\Models\Checada {
                $checada = app(RegistrarChecadaService::class)->registrar([
                    'empleado_id' => $vinculo->empleado_id,
                    'sucursal_id' => $dispositivo->sucursal_id ?? $vinculo->empleado->sucursal_id,
                    'registrada_en' => $datos['registrada_en'],
                    'tipo' => $datos['tipo'],
                    'metodo' => MetodoChecada::Huella,
                    'dispositivo' => $dispositivo->numero_serie,
                    'identificador_externo' => $evento->huella_evento,
                    'observacion' => 'Recibida mediante ADMS/iClock.',
                ]);

                $evento->update([
                    'estado' => 'procesado',
                    'checada_id' => $checada->id,
                    'procesada_en' => now(),
                    'error' => null,
                ]);

                return $checada;
            });

            $dispositivo->update(['ultima_checada_en' => $checada->registrada_en]);

            return 'aceptados';
        } catch (Throwable $exception) {
            Log::warning('Error procesando checada ADMS', [
                'dispositivo' => $dispositivo->numero_serie,
                'evento' => $evento->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->rechazar($evento, 'Error al registrar la checada en el módulo existente.');
        }
    }

    /**
     * @return array{identificador: ?string, registrada_en: ?Carbon, tipo: string}
     */
    private function interpretarLinea(string $linea): array
    {
        $campos = str_contains($linea, "\t") ? explode("\t", $linea) : str_getcsv($linea);
        $pares = [];

        foreach ($campos as $campo) {
            if (str_contains($campo, '=')) {
                [$clave, $valor] = explode('=', $campo, 2);
                $pares[strtolower(trim($clave))] = trim($valor);
            }
        }

        $identificador = $pares['pin'] ?? $pares['userid'] ?? trim($campos[0] ?? '');
        $fechaTexto = $pares['time'] ?? $pares['timestamp'] ?? $pares['datetime'] ?? ($campos[1] ?? null);
        $registradaEn = null;

        if ($fechaTexto) {
            try {
                $registradaEn = Carbon::parse(trim($fechaTexto));
            } catch (Throwable) {
                $registradaEn = null;
            }
        }

        $estado = strtolower($pares['status'] ?? $pares['state'] ?? ($campos[2] ?? '0'));
        $tipo = in_array($estado, ['1', 'out', 'salida'], true)
            ? TipoChecada::Salida->value
            : TipoChecada::Entrada->value;

        return [
            'identificador' => $identificador !== '' ? $identificador : null,
            'registrada_en' => $registradaEn,
            'tipo' => $tipo,
        ];
    }

    private function rechazar(EventoAdms $evento, string $mensaje): string
    {
        $evento->update(['estado' => 'rechazado', 'error' => $mensaje, 'procesada_en' => now()]);

        return 'rechazados';
    }
}

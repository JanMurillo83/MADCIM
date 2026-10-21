<?php

namespace App\Http\Controllers;

use App\Models\DispositivoBiometrico;
use App\Services\AdmsChecadaService;
use Illuminate\Http\Request;

class AdmsController extends Controller
{
    public function __construct(
        private readonly AdmsChecadaService $adms,
    ) {
    }

    public function cdata(Request $request)
    {
        $dispositivo = $this->dispositivoActivo($request);

        if (!$dispositivo) {
            return $this->rechazado();
        }

        if ($request->isMethod('GET')) {
            return response($this->adms->inicializar($dispositivo))->header('Content-Type', 'text/plain');
        }

        $payload = (string) $request->getContent();
        if (strlen($payload) > 1_000_000) {
            return response('ERROR: PAYLOAD TOO LARGE', 413)->header('Content-Type', 'text/plain');
        }

        $operacion = strtoupper((string) $request->query('table', 'ATTLOG'));
        if (!in_array($operacion, ['ATTLOG', 'OPERLOG'], true)) {
            return response('ERROR: UNSUPPORTED OPERATION', 422)->header('Content-Type', 'text/plain');
        }

        $resultado = $this->adms->recibir(
            $dispositivo,
            $payload,
            $operacion,
        );

        if ($resultado['rechazados'] > 0) {
            return response('ERROR: REJECTED', 422)->header('Content-Type', 'text/plain');
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    public function getRequest(Request $request)
    {
        $dispositivo = $this->dispositivoActivo($request);

        return $dispositivo
            ? response("OK\r\n", 200)->header('Content-Type', 'text/plain')
            : $this->rechazado();
    }

    public function deviceCmd(Request $request)
    {
        $dispositivo = $this->dispositivoActivo($request);

        return $dispositivo
            ? response("OK\r\n", 200)->header('Content-Type', 'text/plain')
            : $this->rechazado();
    }

    private function dispositivoActivo(Request $request): ?DispositivoBiometrico
    {
        $serie = (string) $request->query('SN', $request->query('sn', ''));

        if ($serie === '' || preg_match('/^[A-Za-z0-9._-]{1,120}$/', $serie) !== 1) {
            return null;
        }

        return DispositivoBiometrico::query()
            ->where('numero_serie', $serie)
            ->where('activo', true)
            ->first();
    }

    private function rechazado()
    {
        return response('ERROR: DEVICE NOT REGISTERED', 401)->header('Content-Type', 'text/plain');
    }
}

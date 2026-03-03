<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrController extends Controller
{
    public function generar_2($texto, $archivo)
    {
        QrCode::format('svg')
            ->generate($texto,$archivo);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuracion';
    protected $fillable = ['razon_social','rfc','regimen','codigo',
    'calle','exterior','interior','colonia','municipio','estado',
    'pais','sello_cer','sello_key','sello_pass','api_key','logo',
    'por_tab_com','por_tab_ped','imp_tabla_met','imp_tabla_dep',
    'imp_triqui_met','imp_triqui_dep','imp_tridie_met','imp_tridie_dep'];
}

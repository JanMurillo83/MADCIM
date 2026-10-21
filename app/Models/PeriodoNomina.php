<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PeriodoNomina extends Model
{
    protected $table = 'periodos_nomina';

    protected $fillable = ['fecha_inicio', 'fecha_fin', 'estatus', 'generada_por_id', 'generada_en', 'revisada_por_id', 'revisada_en', 'cerrada_por_id', 'cerrada_en'];
    protected function casts(): array { return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'generada_en' => 'datetime', 'revisada_en' => 'datetime', 'cerrada_en' => 'datetime']; }
    public function nominas(): HasMany { return $this->hasMany(NominaSemanal::class); }
}

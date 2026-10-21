<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PeriodoNomina extends Model
{
    protected $fillable = ['fecha_inicio', 'fecha_fin', 'estatus', 'generada_por_id', 'generada_en'];
    protected function casts(): array { return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'generada_en' => 'datetime']; }
    public function nominas(): HasMany { return $this->hasMany(NominaSemanal::class); }
}

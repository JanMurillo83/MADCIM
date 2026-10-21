<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class NominaSemanal extends Model
{
    use BelongsToSucursalScope;
    protected $fillable = ['periodo_nomina_id', 'empleado_id', 'sueldo_base', 'faltas', 'minutos_retardo', 'descuento_faltas', 'descuento_retardos', 'otros_descuentos', 'horas_extra', 'bonos', 'descuentos', 'total', 'observaciones'];
    protected function casts(): array { return ['faltas' => 'integer', 'minutos_retardo' => 'integer', 'sueldo_base' => 'decimal:2', 'descuento_faltas' => 'decimal:2', 'descuento_retardos' => 'decimal:2', 'otros_descuentos' => 'decimal:2', 'horas_extra' => 'decimal:2', 'bonos' => 'decimal:2', 'descuentos' => 'decimal:2', 'total' => 'decimal:2']; }
    public function periodo(): BelongsTo { return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id'); }
    public function empleado(): BelongsTo { return $this->belongsTo(Empleado::class); }
}

<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SaldoVacacion extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'saldos_vacaciones';

    protected $fillable = ['empleado_id', 'anio', 'dias_asignados', 'dias_usados', 'observaciones'];
    protected function casts(): array { return ['anio' => 'integer', 'dias_asignados' => 'decimal:2', 'dias_usados' => 'decimal:2']; }
    public function empleado(): BelongsTo { return $this->belongsTo(Empleado::class); }
    public function getDiasDisponiblesAttribute(): float { return max(0, (float) $this->dias_asignados - (float) $this->dias_usados); }
}

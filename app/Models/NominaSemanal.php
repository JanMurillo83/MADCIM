<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class NominaSemanal extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'nominas_semanales';

    protected $fillable = ['periodo_nomina_id', 'empleado_id', 'sueldo_base', 'dias_programados', 'dias_trabajados', 'dias_descanso_laborados', 'dias_festivos', 'importe_descanso_laborado', 'importe_dia_festivo', 'faltas', 'minutos_retardo', 'descuento_faltas', 'descuento_retardos', 'otros_descuentos', 'horas_extra', 'bonos', 'aguinaldo', 'apoyos', 'anticipos', 'prestamos', 'descuentos', 'total', 'observaciones'];
    protected function casts(): array { return ['dias_programados' => 'integer', 'dias_trabajados' => 'integer', 'dias_descanso_laborados' => 'integer', 'dias_festivos' => 'integer', 'faltas' => 'integer', 'minutos_retardo' => 'integer', 'sueldo_base' => 'decimal:2', 'importe_descanso_laborado' => 'decimal:2', 'importe_dia_festivo' => 'decimal:2', 'descuento_faltas' => 'decimal:2', 'descuento_retardos' => 'decimal:2', 'otros_descuentos' => 'decimal:2', 'horas_extra' => 'decimal:2', 'bonos' => 'decimal:2', 'aguinaldo' => 'decimal:2', 'apoyos' => 'decimal:2', 'anticipos' => 'decimal:2', 'prestamos' => 'decimal:2', 'descuentos' => 'decimal:2', 'total' => 'decimal:2']; }
    public function periodo(): BelongsTo { return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id'); }
    public function empleado(): BelongsTo { return $this->belongsTo(Empleado::class); }
}

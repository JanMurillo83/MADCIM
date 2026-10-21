<?php
namespace App\Models;
use App\Enums\EstadoVacacion;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SolicitudVacacion extends Model
{
    use BelongsToSucursalScope;
    protected $fillable = ['empleado_id', 'saldo_vacaciones_id', 'fecha_inicio', 'fecha_fin', 'dias', 'estatus', 'motivo', 'solicitada_por_id', 'autorizada_por_id', 'autorizada_en', 'comentario_autorizacion'];
    protected function casts(): array { return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'dias' => 'decimal:2', 'estatus' => EstadoVacacion::class, 'autorizada_en' => 'datetime']; }
    public function empleado(): BelongsTo { return $this->belongsTo(Empleado::class); }
    public function saldo(): BelongsTo { return $this->belongsTo(SaldoVacacion::class, 'saldo_vacaciones_id'); }
    public function solicitadaPor(): BelongsTo { return $this->belongsTo(User::class, 'solicitada_por_id'); }
    public function autorizadaPor(): BelongsTo { return $this->belongsTo(User::class, 'autorizada_por_id'); }
}

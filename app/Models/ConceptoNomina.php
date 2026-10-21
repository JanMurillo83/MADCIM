<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSucursalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;
class ConceptoNomina extends Model
{
    use BelongsToSucursalScope;

    protected $table = 'conceptos_nomina';

    protected $fillable = ['periodo_nomina_id', 'empleado_id', 'tipo', 'cantidad', 'concepto', 'importe', 'observaciones', 'registrado_por_id'];
    protected function casts(): array { return ['cantidad' => 'decimal:2', 'importe' => 'decimal:2']; }
    protected static function booted(): void
    {
        static::creating(fn (self $concepto) => $concepto->registrado_por_id ??= Auth::id());
        static::updating(function (self $concepto): void {
            if ($concepto->periodo?->estatus !== 'abierto') {
                throw new LogicException('No se pueden modificar conceptos de una nómina generada, revisada o cerrada.');
            }
        });
        static::deleting(function (self $concepto): void {
            if ($concepto->periodo?->estatus !== 'abierto') {
                throw new LogicException('No se pueden eliminar conceptos de una nómina generada, revisada o cerrada.');
            }
        });
    }
    public function periodo(): BelongsTo { return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id'); }
    public function empleado(): BelongsTo { return $this->belongsTo(Empleado::class); }
}

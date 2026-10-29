<?php

namespace App\Filament\Resources\FacturasCfdi\Pages;

use App\Filament\Concerns\NormalizesMountedActions;
use App\Filament\Resources\FacturasCfdi\FacturasCfdiResource;
use App\Models\Clientes;
use App\Models\Configuracion;
use App\Models\FacturasCfdi;
use App\Services\FacturaCfdiDatosFiscales;
use App\Services\FacturaCfdiNotasService;
use App\Services\FacturaCfdiPacService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateFacturasCfdi extends CreateRecord
{
    use NormalizesMountedActions;

    protected static string $resource = FacturasCfdiResource::class;

    public function hydrate(): void
    {
        parent::hydrate();
        $this->normalizeMountedActions();
    }

    protected function afterActionCalled(Action $action): void
    {
        parent::afterActionCalled($action);
        $this->normalizeMountedActions();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! (auth()->user()?->isAdmin() ?? false)) {
            $data['sucursal_id'] = auth()->user()?->sucursal_id;
        }

        $notasVentaIds = array_values(array_filter(
            (array) ($data['notas_venta_ids'] ?? []),
            static fn (mixed $id): bool => is_numeric($id) && (int) $id > 0,
        ));
        $notasRentaIds = array_values(array_filter(
            (array) ($data['notas_renta_ids'] ?? []),
            static fn (mixed $id): bool => is_numeric($id) && (int) $id > 0,
        ));
        $esquemaImpuestos = $notasRentaIds !== [] && $notasVentaIds === []
            && ($data['esquema_impuestos'] ?? null) === 'persona_moral'
            ? 'persona_moral'
            : 'persona_fisica';
        unset($data['notas_venta_ids'], $data['notas_renta_ids']);

        $partidasNotas = app(FacturaCfdiNotasService::class)->construirPartidas(
            $notasVentaIds,
            $notasRentaIds,
            filled($data['cliente_id'] ?? null) ? (int) $data['cliente_id'] : null,
            filled($data['sucursal_id'] ?? null) ? (int) $data['sucursal_id'] : null,
        );
        $partidasManuales = collect((array) ($this->data['partidas'] ?? $data['partidas'] ?? []))
            ->filter(static function (mixed $partida): bool {
                if (! is_array($partida)) {
                    return false;
                }

                $identificador = (string) ($partida['no_identificacion'] ?? '');

                return ! str_starts_with($identificador, 'NVV:')
                    && ! str_starts_with($identificador, 'NVR:')
                    && ! FacturaCfdiNotasService::esConceptoDepositoGarantia($partida);
            })
            ->values()
            ->all();
        $partidas = [...$partidasNotas, ...$partidasManuales];
        $this->data['partidas'] = $partidas;
        unset($data['partidas']);

        $data['subtotal'] = round(array_sum(array_map(
            static fn (array $partida): float => (float) ($partida['subtotal'] ?? 0),
            $partidas,
        )), 2);
        $data['impuestos_total'] = round(array_sum(array_map(
            static fn (array $partida): float => (float) ($partida['impuestos'] ?? 0),
            $partidas,
        )), 2);
        $data['total'] = round(array_sum(array_map(
            static fn (array $partida): float => (float) ($partida['total'] ?? 0),
            $partidas,
        )), 2);
        $retenidoTotal = $esquemaImpuestos === 'persona_moral'
            ? round(array_sum(array_map(static function (array $partida): float {
                if (! str_starts_with((string) ($partida['no_identificacion'] ?? ''), 'NVR:')
                    || (float) ($partida['impuestos'] ?? 0) <= 0) {
                    return 0.0;
                }
                return round((float) ($partida['subtotal'] ?? 0) * 0.106667, 2);
            }, $partidas)), 2)
            : 0.0;
        $data['esquema_impuestos'] = $esquemaImpuestos;
        $data['impuestos_retenidos_total'] = $retenidoTotal;
        $data['total'] = round($data['total'] - $retenidoTotal, 2);

        $datosFiscales = app(FacturaCfdiDatosFiscales::class);
        $emisor = $datosFiscales->datosEmisor(Configuracion::query()->first());

        foreach ($emisor as $campo => $valor) {
            if (filled($valor)) {
                $data[$campo] = $valor;
            }
        }

        $cliente = Clientes::find($data['cliente_id'] ?? null);
        if ($cliente) {
            $data = array_replace($data, $datosFiscales->datosReceptor($cliente));
            $data['uso_cfdi'] = $cliente->regimen === '616' ? 'S01' : ($data['uso_cfdi'] ?? 'G01');
        }

        $data['saldo_pendiente'] = (float) ($data['total'] ?? 0);
        $data['user_id'] = auth()->id();
        $data['estatus'] = 'Borrador';

        return $data;
    }

    protected function afterCreate(): void
    {
        $facturaId = $this->record?->getKey();

        if (! $facturaId) {
            return;
        }

        // El XML builder consume los impuestos SAT a nivel de concepto. Guarda el IVA trasladado
        // de las partidas de renta y, cuando aplica, la retención de 10.6667% sobre su base.
        $this->record->load('partidas');
        foreach ($this->record->partidas as $partida) {
            if (! str_starts_with((string) $partida->no_identificacion, 'NVR:')) {
                continue;
            }
            $base = max(0, (float) $partida->subtotal - (float) $partida->descuento);
            $iva = (float) $partida->impuestos;
            if ($base <= 0 || $iva <= 0) {
                continue;
            }

            $partida->impuestos()->delete();
            $partida->impuestos()->create([
                'tipo' => 'Traslado', 'impuesto' => '002', 'tipo_factor' => 'Tasa',
                'tasa_o_cuota' => 0.160000, 'base' => $base, 'importe' => $iva,
            ]);

            if ($this->record->esquema_impuestos === 'persona_moral') {
                $partida->impuestos()->create([
                    'tipo' => 'Retencion', 'impuesto' => '002', 'tipo_factor' => 'Tasa',
                    'tasa_o_cuota' => 0.106667, 'base' => $base,
                    'importe' => round($base * 0.106667, 2),
                ]);
            }
        }

        DB::afterCommit(function () use ($facturaId): void {
            $factura = FacturasCfdi::query()->find($facturaId);

            if (! $factura) {
                Notification::make()
                    ->title('No se pudo iniciar el timbrado')
                    ->body('La factura ya no está disponible.')
                    ->danger()
                    ->send();

                return;
            }

            $resultado = app(FacturaCfdiPacService::class)->timbrar($factura);
            $notificacion = Notification::make()
                ->title($resultado['success'] ? 'Factura timbrada' : 'Factura guardada sin timbre')
                ->body($resultado['message']);

            if ($resultado['success']) {
                $notificacion->success()->send();

                return;
            }

            $notificacion->danger()->send();
        });
    }
}

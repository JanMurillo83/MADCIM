<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Models\CierreDevolucionRenta;
use App\Services\CierreDevolucionRentaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class CierresDevolucionRenta extends Page implements HasActions
{
    use HasRolePageAccess;
    use InteractsWithActions;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Cierres de Obra';
    protected static ?string $title = 'Cierres de Obra';
    protected static string|null|\UnitEnum $navigationGroup = 'Devoluciones';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.cierres-devolucion-renta';

    #[Computed]
    public function cierres(): Collection
    {
        return CierreDevolucionRenta::query()
            ->with(['cliente', 'direccionEntrega', 'notaVentaVenta', 'user'])
            ->latest('id')
            ->get();
    }

    public function cancelarCierre(int $cierreId): void
    {
        try {
            app(CierreDevolucionRentaService::class)->cancelar(
                CierreDevolucionRenta::findOrFail($cierreId),
                Auth::id(),
            );

            Notification::make()
                ->title('Cierre cancelado')
                ->body('El cierre fue revertido y las notas de renta quedaron disponibles nuevamente.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()
                ->title('No se pudo cancelar el cierre')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function devolucionExtemporanea(int $cierreId): void
    {
        $this->mountAction('integrarDevolucion', ['cierreId' => $cierreId]);
    }

    public function integrarDevolucionAction(): Action
    {
        return Action::make('integrarDevolucion')
            ->modalHeading('Integrar devolución extemporánea')
            ->modalDescription('Capture únicamente los productos que el cliente devolvió después del cierre.')
            ->modalWidth('3xl')
            ->form(function (array $arguments): array {
                $cierre = CierreDevolucionRenta::with('notaVentaVenta.partidas.producto')->findOrFail($arguments['cierreId']);
                $partidas = $cierre->notaVentaVenta?->partidas ?? collect();

                return [
                    Repeater::make('partidas')
                        ->label('Productos devueltos')
                        ->schema([
                            Select::make('producto_id')
                                ->label('Producto')
                                ->options($partidas->mapWithKeys(fn ($partida): array => [
                                    $partida->item => ($partida->producto?->clave ?? 'Producto #' . $partida->item)
                                        . ' - ' . ($partida->descripcion ?? $partida->producto?->descripcion ?? 'Producto'),
                                ])->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('cantidad')
                                ->label('Cantidad devuelta')
                                ->numeric()
                                ->minValue(0.01)
                                ->required(),
                        ])
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->addActionLabel('Agregar producto'),
                    Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(3),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                try {
                    $cantidades = collect($data['partidas'] ?? [])
                        ->filter(fn (array $partida): bool => (float) ($partida['cantidad'] ?? 0) > 0)
                        ->groupBy(fn (array $partida): string => (string) ($partida['producto_id'] ?? ''))
                        ->map(fn (Collection $partidas): float => $partidas->sum(fn (array $partida): float => (float) $partida['cantidad']))
                        ->all();

                    $resultado = app(CierreDevolucionRentaService::class)->integrarDevolucionExtemporanea(
                        CierreDevolucionRenta::findOrFail($arguments['cierreId']),
                        $cantidades,
                        $data['observaciones'] ?? null,
                        Auth::id(),
                    );

                    Notification::make()
                        ->title('Devolución integrada')
                        ->body('Se reintegraron ' . number_format($resultado['cantidad'], 2) . ' unidades y se ajustó el faltante por $' . number_format($resultado['importe'], 2) . '.')
                        ->success()
                        ->send();
                } catch (\Throwable $exception) {
                    report($exception);
                    Notification::make()
                        ->title('No se pudo integrar la devolución')
                        ->body($exception->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}

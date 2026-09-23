<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRolePageAccess;
use App\Services\ActualizarPreciosMaderaService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ActualizarPreciosMadera extends Page
{
    use HasRolePageAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationLabel = 'Actualizar precios de madera';
    protected static ?string $title = 'Actualización masiva de precios de madera';
    protected static string|null|\UnitEnum $navigationGroup = 'Catalogos';
    protected static ?string $navigationParentItem = 'Productos';
    protected static ?int $navigationSort = 20;
    protected string $view = 'filament.pages.actualizar-precios-madera';

    /** @var array<int, array{clave: string, label: string, producto_id: int|null, renta: float, venta: float, disponible: bool}> */
    public array $precios = [];

    /** @var array<int, array<string, mixed>> */
    public array $filas = [];

    public string $loteAplicado = '';
    public int $actualizados = 0;
    public int $omitidos = 0;

    public function mount(): void
    {
        $this->cargarPreciosBase();
        $this->previsualizar();
    }

    public function updatedPrecios(): void
    {
        $this->previsualizar();
    }

    public function cargarPreciosBase(): void
    {
        $this->precios = array_values(app(ActualizarPreciosMaderaService::class)->obtenerPreciosBase());
    }

    public function previsualizar(): void
    {
        try {
            $this->filas = app(ActualizarPreciosMaderaService::class)->previsualizar($this->preciosComoMapa());
        } catch (InvalidArgumentException $exception) {
            $this->filas = [];
            Notification::make()->title('Precios inválidos')->body($exception->getMessage())->warning()->send();
        }
    }

    public function aplicarPrecios(): void
    {
        try {
            $resultado = app(ActualizarPreciosMaderaService::class)->aplicar(
                $this->preciosComoMapa(),
                Auth::id(),
            );

            $this->loteAplicado = $resultado['loteId'];
            $this->actualizados = (int) $resultado['actualizados'];
            $this->omitidos = (int) $resultado['omitidos'];
            $this->cargarPreciosBase();
            $this->previsualizar();

            Notification::make()
                ->title('Precios actualizados')
                ->body("Se actualizaron {$this->actualizados} productos. Lote: {$this->loteAplicado}")
                ->success()
                ->persistent()
                ->send();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('No se pudo actualizar')->body($exception->getMessage())->danger()->send();
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()->title('No se pudo actualizar')->body('La operación fue cancelada y no se aplicaron cambios parciales.')->danger()->persistent()->send();
        }
    }

    public function filasListas(): int
    {
        return count(array_filter($this->filas, static fn (array $fila): bool => (bool) ($fila['listo'] ?? false)));
    }

    private function preciosComoMapa(): array
    {
        return collect($this->precios)
            ->filter(fn (array $precio): bool => filled($precio['clave'] ?? null))
            ->mapWithKeys(fn (array $precio): array => [
                $precio['clave'] => [
                    'renta' => $precio['renta'] ?? null,
                    'venta' => $precio['venta'] ?? null,
                ],
            ])
            ->all();
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\FacturasCfdi\Pages\CreateFacturasCfdi;
use App\Filament\Resources\FacturasCfdi\Pages\ListFacturasCfdi;
use Filament\Actions\Action;
use ReflectionMethod;
use Tests\TestCase;

class FacturasCfdiActionHydrationTest extends TestCase
{
    public function test_elimina_valores_invalidos_de_la_pila_de_acciones_al_hidratar(): void
    {
        $page = new ListFacturasCfdi;
        $mountedAction = [
            'name' => 'edit',
            'arguments' => [],
            'context' => [
                'table' => true,
                'recordKey' => '1',
            ],
        ];
        $page->mountedActions = [
            'edit' => $mountedAction,
            'subtotal' => 1.16,
        ];

        $page->hydrate();
        $page->bootedInteractsWithActions();

        $this->assertSame([$mountedAction], $page->mountedActions);
    }

    public function test_limpia_la_pila_despues_de_acciones_montadas_en_creacion_y_listado(): void
    {
        $mountedAction = [
            'name' => 'save',
            'arguments' => [],
            'context' => [],
        ];

        foreach ([CreateFacturasCfdi::class, ListFacturasCfdi::class] as $pageClass) {
            $page = new $pageClass;
            $page->mountedActions = [$mountedAction, 1.16];

            $afterActionCalled = new ReflectionMethod($page, 'afterActionCalled');
            $afterActionCalled->invoke($page, Action::make('save'));

            $this->assertSame([$mountedAction], $page->mountedActions);
        }
    }

    public function test_normaliza_la_pila_al_finalizar_una_accion_montada(): void
    {
        foreach ([CreateFacturasCfdi::class, ListFacturasCfdi::class] as $pageClass) {
            $page = new $pageClass;
            $page->cacheAction(Action::make('save')
                ->action(function () use ($page): void {
                    $page->mountedActions[] = 1.16;
                }));

            $page->mountAction('save');

            $this->assertSame([], $page->mountedActions);
        }
    }
}

<?php

namespace App\Providers;

use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'notas_venta_renta' => \App\Models\NotasVentaRenta::class,
            'notas_venta_venta' => \App\Models\NotasVentaVenta::class,
            'facturas_cfdi' => \App\Models\FacturasCfdi::class,
        ]);

        Number::useLocale(config('app.number_locale', 'en_US'));

        Table::configureUsing(
            fn (Table $table): Table => $table->defaultNumberLocale(config('app.number_locale', 'en_US')),
        );

        TextInput::configureUsing(function (TextInput $input): void {
            $name = strtolower($input->getName());

            if (in_array($name, ['cantidad', 'dias_renta', 'duracion_renta', 'm2', 'metros', 'metros_m2', 'total_m2', 'porcentaje'], true)) {
                return;
            }

            $esCampoMonetario = preg_match(
                '/(^|[._])(importe|precio|costo|subtotal|total|iva|impuestos|imp_[a-z0-9_]+|valor_unitario|deposito|descuento|saldo|monto|tipo_cambio|efectivo|cash|diferencia)([._]|$)/',
                $name,
            ) === 1;

            $esPrecioDeAyuda = preg_match('/^cons_(mad|equi)_(renta(_[dsm])?|venta)$/', $name) === 1;

            if (! $esCampoMonetario && ! $esPrecioDeAyuda) {
                return;
            }

            $input
                ->mask(RawJs::make("\$money(\$input, '.', ',')"))
                ->stripCharacters(',');
        });
    }
}

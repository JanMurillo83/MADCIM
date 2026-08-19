<?php

namespace App\Providers;

use Filament\Tables\Table;
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
        Number::useLocale(config('app.number_locale', 'en_US'));

        Table::configureUsing(
            fn (Table $table): Table => $table->defaultNumberLocale(config('app.number_locale', 'en_US')),
        );
    }
}

<?php

namespace Fabamb\LaravelTabulator;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class TabulatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tabulator.php', 'tabulator');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tabulator');

        // Usable in any Blade view as <x-tabulator-table ... />
        Blade::component(\Fabamb\LaravelTabulator\View\Components\TabulatorTable::class, 'tabulator-table');

        $this->publishes([
            __DIR__.'/../config/tabulator.php' => config_path('tabulator.php'),
        ], 'tabulator-config');
    }
}

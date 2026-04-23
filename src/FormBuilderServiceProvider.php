<?php

namespace SatuForm\FormBuilder;

use Illuminate\Support\ServiceProvider;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/formbuilder.php', 'formbuilder');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'formbuilder');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/formbuilder.php' => config_path('formbuilder.php'),
        ], 'formbuilder-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/formbuilder'),
        ], 'formbuilder-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'formbuilder-migrations');
    }
}

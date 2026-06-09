<?php

namespace Zonneplan\ModuleLoader;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;
use Zonneplan\ModuleLoader\Support\ModuleRepository;
use Zonneplan\ModuleLoader\Support\ModuleRouteLoader;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/module-loader.php', 'module-loader');

        $this->app->singleton(ModuleRepositoryContract::class, ModuleRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/module-loader.php' => config_path('module-loader.php'),
        ], 'module-loader-config');

        $loader = new ModuleRouteLoader();

        Router::macro('module', function (string $name, ?string $type = 'routes') use ($loader) {
            $loader->load($name, $type);
        });

        Router::macro('modules', function (?string $type = 'routes') use ($loader) {
            $loader->loadAll($type);
        });
    }
}

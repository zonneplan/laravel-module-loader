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
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ModuleRepositoryContract::class, static function () {
            return new ModuleRepository();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $loader = new ModuleRouteLoader;

        Router::macro('module', function (string $name, ?string $type = 'routes') use ($loader) {
            $loader->load($name, $type);
        });

        Router::macro('modules', function (?string $type = 'routes') use ($loader) {
            $loader->loadAll($type);
        });
    }
}

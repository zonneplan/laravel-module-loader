<?php

namespace Zonneplan\ModuleLoader;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionException;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleContract;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;

/**
 * Class ModuleLoader.
 *
 * The module loader acts as an opinionated ServiceProvider and will automatically try to load migrations,
 * views, etc. from a predefined folder structure.
 */
abstract class Module extends ServiceProvider implements ModuleContract
{
    /** @var array */
    protected $policies = [];

    /** @var array */
    protected $middleware = [];

    /** @var array */
    protected $listen = [];

    /** @var array */
    protected $subscribe = [];

    /** @var string */
    protected $modulePath;

    /**
     * Register the module.
     *
     * @throws ReflectionException
     *
     * @return void
     */
    public function register(): void
    {
        // Register this module in the repository
        app(ModuleRepositoryContract::class)->register($this->getModuleNamespace(), $this->getModulePath());

        $this->registerCommands();
    }

    /**
     * Boot the module.
     *
     * @throws ReflectionException
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadCommandSchedule();
        $this->loadMigrations();
        $this->loadViews();
        $this->loadTranslations();
        $this->loadConfigs();
        $this->registerPolicies();
        $this->registerListeners();
        $this->registerMiddleware();
        $this->registerFactories();
    }

    /**
     * @return void
     */
    protected function loadCommandSchedule(): void
    {
        $this->app->booted(function () {
            /** @var $schedule Schedule */
            $schedule = $this->app->make(Schedule::class);
            $this->scheduleCommands($schedule);
        });
    }

    /**
     * @throws ReflectionException
     *
     * @return void
     */
    protected function loadMigrations(): void
    {
        $file = "{$this->getModulePath()}/Database/Migrations";

        if (file_exists($file)) {
            $this->loadMigrationsFrom($file);
        }
    }

    /**
     * @throws ReflectionException
     *
     * @return void
     */
    protected function loadViews(): void
    {
        $file = "{$this->getModulePath()}/Resources/views";

        if (file_exists($file)) {
            $this->loadViewsFrom($file, $this->getModuleNamespace());
        }
    }

    /**
     * @throws ReflectionException
     *
     * @return void
     */
    protected function loadTranslations(): void
    {
        $file = "{$this->getModulePath()}/Resources/lang";

        if (file_exists($file)) {
            $this->loadTranslationsFrom($file, $this->getModuleNamespace());
        }
    }

    /**
     * @throws ReflectionException
     *
     * @return void
     */
    protected function loadConfigs(): void
    {
        $configPath = sprintf('%s/Config', $this->getModulePath());
        $configFilePattern = sprintf('%s/*.php', $configPath);

        if (file_exists($configPath) && empty(($files = glob($configFilePattern))) === false) {
            foreach ($files as $file) {
                $path = sprintf('%s/%s', $configPath, basename($file));
                $filename = pathinfo($path, PATHINFO_FILENAME);
                $configKey = "{$this->getModuleNamespace()}.$filename";

                $this->mergeConfigFrom($path, $configKey);
            }
        }
    }

    /**
     * @return void
     */
    protected function registerPolicies(): void
    {
        foreach ($this->policies as $class => $policy) {
            Gate::policy($class, $policy);
        }
    }

    /**
     * @return void
     */
    protected function registerListeners(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);
        }
    }

    /**
     * @throws ReflectionException
     *
     * @return string
     */
    protected function getModulePath(): string
    {
        if ($this->modulePath === null) {
            // Since we will be calling this from an extending class, __DIR__ will not cut it.
            $reflector = new ReflectionClass($this);
            $filename = $reflector->getFileName();
            $this->modulePath = dirname($filename);
        }

        return $this->modulePath;
    }

    /**
     * @return array
     */
    protected function getCommands(): array
    {
        return [];
    }

    /**
     * @param Schedule $schedule
     *
     * @return void
     */
    protected function scheduleCommands(Schedule $schedule): void
    {
        //
    }

    /**
     * Registers the commands of the schedule.
     *
     * @return void
     */
    private function registerCommands(): void
    {
        $this->commands($this->getCommands());
    }

    /**
     * Registers middleware.
     *
     * @return void
     */
    private function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        foreach ($this->middleware as $group => $middleware) {
            $router->pushMiddlewareToGroup($group, $middleware);
        }
    }

    /**
     * Registers factories.
     *
     * @return void
     */
    private function registerFactories(): void
    {
        $this->app->afterResolving(Factory::class, function (Factory $faker) {
            $faker->load($this->getModulePath().'/Database/Factories');
        });
    }

    /**
     * Get the view/config namespace of the current module.
     *
     * @return string
     */
    abstract public function getModuleNamespace(): string;
}

<?php

namespace Zonneplan\ModuleLoader\Support;

use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;
use Zonneplan\ModuleLoader\Support\Exceptions\ModuleNotFoundException;

class ModuleRouteLoader
{
    private const FALLBACK_ROUTE_TYPE = 'routes';

    private const ROUTE_TYPES = [
        self::FALLBACK_ROUTE_TYPE, 'api', 'channels', 'console', 'web', 'mcp',
    ];

    /**
     * @param string $type
     *
     * @return void
     */
    public function loadAll(string $type): void
    {
        foreach ($this->getRepository()->getAll() as $name => $path) {
            $this->loadRoutes($path, $type);
        }
    }

    /**
     * @param string $moduleName
     * @param string $type
     *
     * @throws ModuleNotFoundException
     *
     * @return void
     */
    public function load(string $moduleName, string $type): void
    {
        $this->loadRoutes($this->getRepository()->get($moduleName), $type);
    }

    /**
     * Load the routes file in a given module path.
     *
     * @param string $path
     * @param string $type
     *
     * @return void
     */
    protected function loadRoutes(string $path, string $type): void
    {
        if (in_array($type, self::ROUTE_TYPES, true) === false) {
            return;
        }

        $file = $type === self::FALLBACK_ROUTE_TYPE
            ? "{$path}/{$type}.php"
            : "{$path}/Routes/{$type}.php";

        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            require $file;
        }
    }

    /**
     * @return ModuleRepository
     */
    protected function getRepository(): ModuleRepository
    {
        return app(ModuleRepositoryContract::class);
    }
}

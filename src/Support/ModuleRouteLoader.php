<?php

namespace Zonneplan\ModuleLoader\Support;

use Illuminate\Support\Arr;
use LogicException;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;
use Zonneplan\ModuleLoader\Support\Exceptions\ModuleNotFoundException;

class ModuleRouteLoader
{
    private const FALLBACK_ROUTE_TYPE = 'routes';

    private const ROUTE_TYPES = [
        self::FALLBACK_ROUTE_TYPE, 'api', 'channels', 'console', 'web', 'mcp',
    ];

    /**
     * Route types whose file Module::boot() already loads without a route group.
     */
    private const AUTOLOADED_ROUTE_TYPES = ['api', 'web', 'mcp'];

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
     * @throws LogicException
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
            if (in_array($type, self::AUTOLOADED_ROUTE_TYPES, true)) {
                $this->ensureGroupKeepsRouteUris($file);
            }

            /** @noinspection PhpIncludeInspection */
            require $file;
        }
    }

    /**
     * A group with a prefix or domain gives the routes a different URI, so the copy Module::boot() registered
     * without middleware stays reachable next to the grouped one.
     *
     * @param string $file
     *
     * @throws LogicException
     *
     * @return void
     */
    protected function ensureGroupKeepsRouteUris(string $file): void
    {
        $group = Arr::last(app('router')->getGroupStack()) ?? [];

        if (filled($group['prefix'] ?? null) || filled($group['domain'] ?? null)) {
            throw new LogicException(
                "{$file} is already loaded by the module without a route group. Loading it again in a group with a "
                .'prefix or domain leaves that copy reachable without the group\'s middleware. Declare the '
                .'middleware, prefix and domain in the route file instead.'
            );
        }
    }

    protected function getRepository(): ModuleRepositoryContract
    {
        return app(ModuleRepositoryContract::class);
    }
}

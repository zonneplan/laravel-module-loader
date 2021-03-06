<?php

namespace Zonneplan\ModuleLoader\Support;

use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;
use Zonneplan\ModuleLoader\Support\Exceptions\ModuleNotFoundException;

/**
 * Class ModuleRepository.
 *
 * The module repository acts as a global store for registered modules.
 */
class ModuleRepository implements ModuleRepositoryContract
{
    protected array $modules = [];

    /**
     * Registers a new module with a path.
     *
     * @param string $module
     * @param string $path
     *
     * @return $this
     */
    public function register(string $module, string $path): self
    {
        $this->modules[$module] = $path;

        return $this;
    }

    /**
     * Returns a list of all loaded modules.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->modules;
    }

    /**
     * Check if the module is loaded.
     *
     * @param string $module
     *
     * @return bool
     */
    public function isLoaded(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    /**
     * Retrieves the path from the module.
     *
     * @param string $module
     *
     * @throws ModuleNotFoundException
     *
     * @return string
     */
    public function get(string $module): string
    {
        if ($this->isLoaded($module) === false) {
            throw new ModuleNotFoundException("Module '{$module}' was not found");
        }

        return $this->modules[$module];
    }
}

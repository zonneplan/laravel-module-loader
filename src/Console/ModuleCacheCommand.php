<?php

namespace Zonneplan\ModuleLoader\Console;

use Illuminate\Console\Command;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;
use Zonneplan\ModuleLoader\Support\ModuleManifest;
use Zonneplan\ModuleLoader\Support\ModuleRepository;

class ModuleCacheCommand extends Command
{
    protected $signature = 'module:cache';

    protected $description = 'Create a cache file for faster module loading';

    public function handle(ModuleManifest $manifest, ModuleRepositoryContract $repository): void
    {
        /** @var ModuleRepository $repository */
        $manifest->clear();

        $modules = $manifest->build($repository);

        $manifest->write($modules);

        $this->components->info(
            sprintf('Module manifest cached successfully. [%d modules]', count($modules))
        );
    }
}
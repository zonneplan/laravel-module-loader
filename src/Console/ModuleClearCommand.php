<?php

namespace Zonneplan\ModuleLoader\Console;

use Illuminate\Console\Command;
use Zonneplan\ModuleLoader\Support\ModuleManifest;

class ModuleClearCommand extends Command
{
    protected $signature = 'module:clear';

    protected $description = 'Remove the module cache file';

    public function handle(ModuleManifest $manifest): void
    {
        $manifest->clear();

        $this->components->info('Module cache cleared successfully.');
    }
}
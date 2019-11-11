<?php

namespace Zonneplan\ModuleLoader\Facades;

use Illuminate\Support\Facades\Facade;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;

class ModuleFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ModuleRepositoryContract::class;
    }
}

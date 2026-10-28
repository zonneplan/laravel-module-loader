<?php

namespace Zonneplan\ModuleLoader\Test\Fixtures\Inventory;

use Zonneplan\ModuleLoader\Module;

class InventoryServiceProvider extends Module
{
    public function getModuleNamespace(): string
    {
        return 'inventory';
    }
}

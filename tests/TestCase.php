<?php

namespace Zonneplan\ModuleLoader\Test;

use Orchestra\Testbench\TestCase as Orchestra;
use Zonneplan\ModuleLoader\Module;
use Zonneplan\ModuleLoader\ModulesServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ModulesServiceProvider::class,
        ];
    }
    protected function getPackageAliases($app)
    {
        return [
            'Module' => Module::class,
        ];
    }
}

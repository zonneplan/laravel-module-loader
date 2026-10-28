<?php

namespace Zonneplan\ModuleLoader\Test\Support;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use LogicException;
use Zonneplan\ModuleLoader\Test\Fixtures\Inventory\InventoryServiceProvider;
use Zonneplan\ModuleLoader\Test\TestCase;

class ModuleRouteLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(InventoryServiceProvider::class);
    }

    public function test_it_autoloads_the_module_route_files_without_a_group()
    {
        $this->assertSame(['inventory/stock'], $this->uris());
    }

    public function test_it_throws_when_an_autoloaded_route_file_is_loaded_in_a_prefixed_group()
    {
        $this->expectException(LogicException::class);

        Route::prefix('api')->group(fn () => Route::module('inventory', 'api'));
    }

    public function test_it_throws_when_all_modules_are_loaded_in_a_prefixed_group()
    {
        $this->expectException(LogicException::class);

        Route::prefix('api')->middleware('auth')->group(fn () => Route::modules('api'));
    }

    public function test_it_throws_when_an_autoloaded_route_file_is_loaded_in_a_domain_group()
    {
        $this->expectException(LogicException::class);

        Route::domain('api.example.com')->group(fn () => Route::module('inventory', 'api'));
    }

    public function test_it_replaces_the_autoloaded_routes_in_a_middleware_only_group()
    {
        Route::middleware('auth')->group(fn () => Route::modules('api'));

        $this->assertSame(['inventory/stock'], $this->uris());
        $this->assertSame(['auth'], Route::getRoutes()->getRoutes()[0]->middleware());
    }

    public function test_it_loads_the_module_root_routes_file_in_a_prefixed_group()
    {
        Route::prefix('partner')->group(fn () => Route::module('inventory'));

        $this->assertSame(['inventory/stock', 'partner/inventory/export'], $this->uris());
    }

    /**
     * @return array<int, string>
     */
    private function uris(): array
    {
        return array_map(fn (RoutingRoute $route) => $route->uri(), Route::getRoutes()->getRoutes());
    }
}

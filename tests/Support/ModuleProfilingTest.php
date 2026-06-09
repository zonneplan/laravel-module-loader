<?php

namespace Zonneplan\ModuleLoader\Test\Support;

use Illuminate\Support\Facades\Event;
use Zonneplan\ModuleLoader\Events\ModuleProfiled;
use Zonneplan\ModuleLoader\Module;
use Zonneplan\ModuleLoader\Test\TestCase;

class ModuleProfilingTest extends TestCase
{
    public function test_it_does_not_profile_modules_by_default()
    {
        $records = [];

        config([
            'module-loader.profiling.enabled' => false,
            'module-loader.profiling.driver' => 'callback',
            'module-loader.profiling.reporter' => function (array $measurement) use (&$records): void {
                $records[] = $measurement;
            },
        ]);

        $module = new ProfilingTestModule($this->app);

        $module->register();
        $module->boot();

        $this->assertEmpty($records);
    }

    public function test_it_profiles_module_register_and_boot_when_enabled()
    {
        $records = [];

        config([
            'module-loader.profiling.enabled' => true,
            'module-loader.profiling.driver' => 'callback',
            'module-loader.profiling.include_steps' => false,
            'module-loader.profiling.reporter' => function (array $measurement) use (&$records): void {
                $records[] = $measurement;
            },
        ]);

        $module = new ProfilingTestModule($this->app);

        $module->register();
        $module->boot();

        $this->assertEquals(['register', 'boot'], array_column($records, 'operation'));
        $this->assertSame(['profiling-test', 'profiling-test'], array_column($records, 'module'));
        $this->assertSame([ProfilingTestModule::class, ProfilingTestModule::class], array_column($records, 'provider'));
        $this->assertArrayHasKey('duration_ms', $records[0]);
        $this->assertArrayHasKey('duration_ns', $records[0]);
        $this->assertArrayHasKey('started_at_unix_nano', $records[0]);
        $this->assertArrayHasKey('ended_at_unix_nano', $records[0]);
    }

    public function test_it_profiles_module_boot_steps_when_enabled()
    {
        $records = [];

        config([
            'module-loader.profiling.enabled' => true,
            'module-loader.profiling.driver' => 'callback',
            'module-loader.profiling.include_steps' => true,
            'module-loader.profiling.reporter' => function (array $measurement) use (&$records): void {
                $records[] = $measurement;
            },
        ]);

        $module = new ProfilingTestModule($this->app);

        $module->boot();

        $operations = array_column($records, 'operation');

        $this->assertContains('boot', $operations);
        $this->assertContains('boot.loadConfigs', $operations);
        $this->assertContains('boot.loadTranslations', $operations);
        $this->assertContains('boot.registerRoutes', $operations);
    }

    public function test_it_can_dispatch_profile_measurements_as_events()
    {
        Event::fake([
            ModuleProfiled::class,
        ]);

        config([
            'module-loader.profiling.enabled' => true,
            'module-loader.profiling.driver' => 'event',
            'module-loader.profiling.include_steps' => false,
        ]);

        $module = new ProfilingTestModule($this->app);

        $module->register();

        Event::assertDispatched(ModuleProfiled::class, function (ModuleProfiled $event): bool {
            return $event->measurement['module'] === 'profiling-test'
                && $event->measurement['provider'] === ProfilingTestModule::class
                && $event->measurement['operation'] === 'register'
                && array_key_exists('duration_ms', $event->measurement)
                && array_key_exists('duration_ns', $event->measurement)
                && array_key_exists('started_at_unix_nano', $event->measurement)
                && array_key_exists('ended_at_unix_nano', $event->measurement);
        });
    }
}

class ProfilingTestModule extends Module
{
    public function getModuleNamespace(): string
    {
        return 'profiling-test';
    }
}

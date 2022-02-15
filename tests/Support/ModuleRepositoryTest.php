<?php

namespace Zonneplan\ModuleLoader\Test\Support;

use Zonneplan\ModuleLoader\Support\Contracts\ModuleRepositoryContract;
use Zonneplan\ModuleLoader\Support\Exceptions\ModuleNotFoundException;
use Zonneplan\ModuleLoader\Support\ModuleRepository;
use Zonneplan\ModuleLoader\Test\TestCase;

class ModuleRepositoryTest extends TestCase
{
    protected ModuleRepository $moduleRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->moduleRepository = app(ModuleRepositoryContract::class);
    }

    public function test_it_should_register_a_new_module()
    {
        $moduleName = 'test-module';
        $modulePath = 'app/Domains/Test';

        $this->moduleRepository->register($moduleName, $modulePath);

        $this->assertTrue($this->moduleRepository->isLoaded($moduleName));
        $this->assertEquals($this->moduleRepository->get($moduleName), $modulePath);
    }

    public function test_it_should_know_when_a_module_is_not_loaded()
    {
        $this->assertFalse($this->moduleRepository->isLoaded('test-module'));

        $this->expectException(ModuleNotFoundException::class);
        $this->moduleRepository->get('test-module');
    }

    public function test_it_returns_all_registered_modules()
    {
        $moduleArray = [
            'test-module' => 'app/Domains/Test',
            'fake-module' => 'app/Domains/Fake',
        ];

        foreach ($moduleArray as $moduleName => $modulePath) {
            $this->moduleRepository->register($moduleName, $modulePath);
        }

        $this->assertEquals($this->moduleRepository->getAll(), $moduleArray);
    }
}

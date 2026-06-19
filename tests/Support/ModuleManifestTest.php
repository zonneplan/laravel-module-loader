<?php

namespace Zonneplan\ModuleLoader\Test\Support;

use Zonneplan\ModuleLoader\Support\ModuleManifest;
use Zonneplan\ModuleLoader\Support\ModuleRepository;
use Zonneplan\ModuleLoader\Test\TestCase;

class ModuleManifestTest extends TestCase
{
    protected ModuleManifest $manifest;

    protected string $modulesRoot;

    public function setUp(): void
    {
        parent::setUp();

        $this->manifest = app(ModuleManifest::class);
        $this->manifest->clear();

        $this->modulesRoot = sys_get_temp_dir() . '/module-loader-test-' . uniqid();
    }

    public function tearDown(): void
    {
        $this->manifest->clear();
        $this->deleteDirectory($this->modulesRoot);

        parent::tearDown();
    }

    public function test_it_is_not_cached_when_no_cache_file_exists()
    {
        $this->assertFalse($this->manifest->isCached());
    }

    public function test_it_returns_null_for_a_module_when_not_cached()
    {
        $this->assertNull($this->manifest->get('any-module'));
    }

    public function test_it_builds_a_manifest_by_scanning_module_paths()
    {
        $modulePath = $this->makeModule('full', [
            'Database/Migrations',
            'Resources/views',
            'Resources/lang',
            'Database/Factories',
            'Config',
            'Routes',
        ], [
            'Config/full.php' => '<?php return [];',
            'Routes/web.php' => '<?php',
        ]);

        $repository = new ModuleRepository();
        $repository->register('full', $modulePath);

        $manifest = $this->manifest->build($repository);

        $this->assertArrayHasKey('full', $manifest);
        $this->assertSame([
            'migrations' => "{$modulePath}/Database/Migrations",
            'views' => "{$modulePath}/Resources/views",
            'translations' => "{$modulePath}/Resources/lang",
            'factories' => "{$modulePath}/Database/Factories",
            'configs' => ["{$modulePath}/Config/full.php"],
            'routes' => ["{$modulePath}/Routes/web.php"],
        ], $manifest['full']);
    }

    public function test_it_sets_missing_directories_to_null_and_empty_file_lists()
    {
        $modulePath = $this->makeModule('empty', []);

        $repository = new ModuleRepository();
        $repository->register('empty', $modulePath);

        $manifest = $this->manifest->build($repository);

        $this->assertSame([
            'migrations' => null,
            'views' => null,
            'translations' => null,
            'factories' => null,
            'configs' => [],
            'routes' => [],
        ], $manifest['empty']);
    }

    public function test_it_writes_a_cache_file_that_can_be_read_back()
    {
        $modulePath = $this->makeModule('writable', ['Resources/views']);

        $repository = new ModuleRepository();
        $repository->register('writable', $modulePath);

        $built = $this->manifest->build($repository);

        $this->assertFalse($this->manifest->isCached());

        $this->manifest->write($built);

        $this->assertTrue($this->manifest->isCached());
        $this->assertFileExists($this->manifest->getCachePath());

        // Resolve a fresh instance so the in-memory manifest is read from disk.
        $fresh = new ModuleManifest($this->app);

        $this->assertSame($built['writable'], $fresh->get('writable'));
    }

    public function test_it_returns_null_for_unknown_modules_when_cached()
    {
        $modulePath = $this->makeModule('known', []);

        $repository = new ModuleRepository();
        $repository->register('known', $modulePath);

        $this->manifest->write($this->manifest->build($repository));

        $fresh = new ModuleManifest($this->app);

        $this->assertNotNull($fresh->get('known'));
        $this->assertNull($fresh->get('does-not-exist'));
    }

    public function test_it_clears_the_cache_file()
    {
        $repository = new ModuleRepository();
        $repository->register('clearable', $this->makeModule('clearable', []));

        $this->manifest->write($this->manifest->build($repository));
        $this->assertTrue($this->manifest->isCached());

        $this->manifest->clear();

        $this->assertFalse($this->manifest->isCached());
        $this->assertFileDoesNotExist($this->manifest->getCachePath());
        $this->assertNull($this->manifest->get('clearable'));
    }

    public function test_cache_path_points_to_the_bootstrap_cache_directory()
    {
        $this->assertSame(
            $this->app->bootstrapPath('cache/modules.php'),
            $this->manifest->getCachePath()
        );
    }

    /**
     * Create a module fixture with the given sub-directories and files.
     *
     * @param array<int, string> $directories
     * @param array<string, string> $files
     */
    private function makeModule(string $name, array $directories, array $files = []): string
    {
        $modulePath = "{$this->modulesRoot}/{$name}";

        mkdir($modulePath, 0777, true);

        foreach ($directories as $directory) {
            mkdir("{$modulePath}/{$directory}", 0777, true);
        }

        foreach ($files as $relativePath => $contents) {
            file_put_contents("{$modulePath}/{$relativePath}", $contents);
        }

        return $modulePath;
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
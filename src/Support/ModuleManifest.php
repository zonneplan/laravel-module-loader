<?php

namespace Zonneplan\ModuleLoader\Support;

use Illuminate\Contracts\Foundation\Application;

class ModuleManifest
{
    protected ?array $manifest = null;

    public function __construct(
        protected Application $app,
    ) {
    }

    public function isCached(): bool
    {
        return file_exists($this->getCachePath());
    }

    public function get(string $moduleNamespace): ?array
    {
        if ($this->manifest === null) {
            if (!$this->isCached()) {
                return null;
            }

            $this->manifest = require $this->getCachePath();
        }

        return $this->manifest[$moduleNamespace] ?? null;
    }

    public function build(ModuleRepository $repository): array
    {
        $manifest = [];

        foreach ($repository->getAll() as $namespace => $modulePath) {
            $manifest[$namespace] = $this->scanModule($modulePath);
        }

        return $manifest;
    }

    public function write(array $manifest): void
    {
        $content = '<?php return ' . var_export($manifest, true) . ';' . PHP_EOL;

        file_put_contents($this->getCachePath(), $content, LOCK_EX);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->getCachePath(), true);
        }
    }

    public function clear(): void
    {
        if ($this->isCached()) {
            unlink($this->getCachePath());

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($this->getCachePath(), true);
            }
        }

        $this->manifest = null;
    }

    public function getCachePath(): string
    {
        return $this->app->bootstrapPath('cache/modules.php');
    }

    protected function scanModule(string $modulePath): array
    {
        $migrationsPath = "{$modulePath}/Database/Migrations";
        $viewsPath = "{$modulePath}/Resources/views";
        $translationsPath = "{$modulePath}/Resources/lang";
        $factoriesPath = "{$modulePath}/Database/Factories";

        $configFiles = [];
        $configPath = "{$modulePath}/Config";
        if (is_dir($configPath)) {
            $configFiles = glob("{$configPath}/*.php") ?: [];
        }

        $routeFiles = [];
        $routesPath = "{$modulePath}/Routes";
        if (is_dir($routesPath)) {
            $routeFiles = glob("{$routesPath}/*.php") ?: [];
        }

        return [
            'migrations' => is_dir($migrationsPath) ? $migrationsPath : null,
            'views' => is_dir($viewsPath) ? $viewsPath : null,
            'translations' => is_dir($translationsPath) ? $translationsPath : null,
            'factories' => is_dir($factoriesPath) ? $factoriesPath : null,
            'configs' => $configFiles,
            'routes' => $routeFiles,
        ];
    }
}
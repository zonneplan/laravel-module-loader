<?php

namespace Zonneplan\ModuleLoader\Support\Contracts;

interface ModuleRepositoryContract
{
    public function register(string $module, string $path): self;

    public function getAll(): array;

    public function isLoaded(string $module): bool;

    public function get(string $module): string;
}

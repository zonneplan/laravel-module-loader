<?php

namespace Zonneplan\ModuleLoader\Events;

class ModuleProfiled
{
    public function __construct(
        public array $measurement
    ) {
    }
}

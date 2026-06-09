<?php

return [
    'profiling' => [
        'enabled' => env('MODULE_LOADER_PROFILING_ENABLED', false),
        'driver' => env('MODULE_LOADER_PROFILING_DRIVER', 'log'),
        'include_steps' => env('MODULE_LOADER_PROFILING_INCLUDE_STEPS', false),
        'log_channel' => env('MODULE_LOADER_PROFILING_LOG_CHANNEL'),
        'reporter' => null,
    ],
];

<?php

namespace Zonneplan\ModuleLoader\Support\Profiling;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;
use Zonneplan\ModuleLoader\Events\ModuleProfiled;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleContract;

class ModuleProfiler
{
    private ?bool $shouldSample = null;

    public function record(ModuleContract $module, string $operation, callable $callback): mixed
    {
        if (! $this->shouldProfile()) {
            return $callback();
        }

        $startedAt = hrtime(true);
        $exception = null;

        try {
            return $callback();
        } catch (Throwable $throwable) {
            $exception = $throwable;

            throw $throwable;
        } finally {
            $this->report($module, $operation, hrtime(true) - $startedAt, $exception);
        }
    }

    public function shouldProfileSteps(): bool
    {
        return $this->shouldProfile() && (bool) config('module-loader.profiling.include_steps', false);
    }

    private function shouldProfile(): bool
    {
        return $this->isEnabled() && $this->shouldSample();
    }

    private function isEnabled(): bool
    {
        return (bool) config('module-loader.profiling.enabled', false);
    }

    private function driver(): string
    {
        return (string) config('module-loader.profiling.driver', 'log');
    }

    private function shouldSample(): bool
    {
        if ($this->shouldSample !== null) {
            return $this->shouldSample;
        }

        $sampleRate = max(0.0, min(1.0, (float) config('module-loader.profiling.sample_rate', 1.0)));

        if ($sampleRate >= 1.0) {
            return $this->shouldSample = true;
        }

        if ($sampleRate <= 0.0) {
            return $this->shouldSample = false;
        }

        return $this->shouldSample = random_int(1, 1_000_000) <= (int) round($sampleRate * 1_000_000);
    }

    private function report(ModuleContract $module, string $operation, int $durationNanoseconds, ?Throwable $exception): void
    {
        $measurement = $this->measurement($module, $operation, $durationNanoseconds, $exception);

        if ($this->driver() === 'callback') {
            $this->reportUsingCallback($measurement);

            return;
        }

        if ($this->driver() === 'event') {
            Event::dispatch(new ModuleProfiled($measurement));

            return;
        }

        if ($this->driver() !== 'log') {
            return;
        }

        $channel = config('module-loader.profiling.log_channel');

        if ($channel) {
            Log::channel($channel)->debug('Module loader profiling', $measurement);

            return;
        }

        Log::debug('Module loader profiling', $measurement);
    }

    private function reportUsingCallback(array $measurement): void
    {
        $reporter = config('module-loader.profiling.reporter');

        if (is_string($reporter)) {
            app($reporter)->handle($measurement);

            return;
        }

        if (is_callable($reporter)) {
            $reporter($measurement);
        }
    }

    private function measurement(ModuleContract $module, string $operation, int $durationNanoseconds, ?Throwable $exception): array
    {
        $measurement = [
            'module' => $module->getModuleNamespace(),
            'provider' => $module::class,
            'operation' => $operation,
            'duration_ms' => round($durationNanoseconds / 1_000_000, 3),
            'duration_ns' => $durationNanoseconds,
        ];

        if ($exception) {
            $measurement['exception'] = $exception::class;
            $measurement['exception_message'] = $exception->getMessage();
        }

        return $measurement;
    }
}

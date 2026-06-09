<?php

namespace Zonneplan\ModuleLoader\Support\Profiling;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;
use Zonneplan\ModuleLoader\Events\ModuleProfiled;
use Zonneplan\ModuleLoader\Support\Contracts\ModuleContract;

class ModuleProfiler
{
    public function record(ModuleContract $module, string $operation, callable $callback): mixed
    {
        if (! $this->isEnabled()) {
            return $callback();
        }

        $startedAt = hrtime(true);
        $startedAtUnixNanoseconds = $this->unixTimestampNanoseconds();
        $exception = null;

        try {
            return $callback();
        } catch (Throwable $throwable) {
            $exception = $throwable;

            throw $throwable;
        } finally {
            $endedAtUnixNanoseconds = $this->unixTimestampNanoseconds();

            $this->report(
                $module,
                $operation,
                hrtime(true) - $startedAt,
                $startedAtUnixNanoseconds,
                $endedAtUnixNanoseconds,
                $exception
            );
        }
    }

    public function shouldProfileSteps(): bool
    {
        return $this->isEnabled() && (bool) config('module-loader.profiling.include_steps', false);
    }

    private function isEnabled(): bool
    {
        return (bool) config('module-loader.profiling.enabled', false);
    }

    private function driver(): string
    {
        return (string) config('module-loader.profiling.driver', 'log');
    }

    private function report(
        ModuleContract $module,
        string $operation,
        int $durationNanoseconds,
        int $startedAtUnixNanoseconds,
        int $endedAtUnixNanoseconds,
        ?Throwable $exception
    ): void {
        $measurement = $this->measurement(
            $module,
            $operation,
            $durationNanoseconds,
            $startedAtUnixNanoseconds,
            $endedAtUnixNanoseconds,
            $exception
        );

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

    private function measurement(
        ModuleContract $module,
        string $operation,
        int $durationNanoseconds,
        int $startedAtUnixNanoseconds,
        int $endedAtUnixNanoseconds,
        ?Throwable $exception
    ): array {
        $measurement = [
            'module' => $module->getModuleNamespace(),
            'provider' => $module::class,
            'operation' => $operation,
            'duration_ms' => round($durationNanoseconds / 1_000_000, 3),
            'duration_ns' => $durationNanoseconds,
            'started_at_unix_nano' => $startedAtUnixNanoseconds,
            'ended_at_unix_nano' => $endedAtUnixNanoseconds,
        ];

        if ($exception) {
            $measurement['exception'] = $exception::class;
            $measurement['exception_message'] = $exception->getMessage();
        }

        return $measurement;
    }

    private function unixTimestampNanoseconds(): int
    {
        return (int) (microtime(true) * 1_000_000_000);
    }
}

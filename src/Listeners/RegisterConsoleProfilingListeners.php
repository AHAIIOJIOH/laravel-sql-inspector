<?php

namespace Ahaiiojioh\LaravelSqlInspector\Listeners;

use Ahaiiojioh\LaravelSqlInspector\Profiling\ProfilerManager;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Events\Dispatcher;

final class RegisterConsoleProfilingListeners
{
    public function __construct(
        private Dispatcher $events,
        private ProfilerManager $profiler,
    ) {
    }

    public function register(): void
    {
        $this->events->listen(CommandStarting::class, function (CommandStarting $event): void {
            $this->profiler->startCliSession([
                'command' => $event->command ?: 'artisan',
            ]);
        });

        $this->events->listen(CommandFinished::class, function (): void {
            $this->profiler->finishCurrentSession();
        });
    }
}

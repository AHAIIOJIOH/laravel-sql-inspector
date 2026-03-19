<?php

namespace Ahaiiojioh\LaravelSqlInspector\Profiling;

use Ahaiiojioh\LaravelSqlInspector\Contracts\QueryAnalyzer;
use Ahaiiojioh\LaravelSqlInspector\Contracts\QueryNormalizer;
use Ahaiiojioh\LaravelSqlInspector\Contracts\SnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Data\QueryRecord;
use Ahaiiojioh\LaravelSqlInspector\Data\SessionContext;
use Ahaiiojioh\LaravelSqlInspector\Http\SqlInspectorMiddleware;
use Ahaiiojioh\LaravelSqlInspector\Support\GeneratesRequestIds;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class ProfilerManager
{
    use GeneratesRequestIds;

    private ?ActiveProfilerSession $activeSession = null;

    private bool $listenerRegistered = false;

    public function __construct(
        private QueryNormalizer $normalizer,
        private QueryAnalyzer $analyzer,
        private SnapshotStore $store,
        private array $config,
    ) {
    }

    public function registerQueryListener(): void
    {
        if ($this->listenerRegistered) {
            return;
        }

        DB::listen(function (QueryExecuted $event): void {
            if (!$this->enabled() || $this->activeSession === null) {
                return;
            }

            $this->activeSession->addQuery(new QueryRecord(
                $event->sql,
                $event->bindings,
                (float) $event->time,
                $event->connectionName,
                $this->normalizer->normalize($event->sql, $event->bindings),
            ));
        });

        $this->listenerRegistered = true;
    }

    public function registerHttpMiddleware(Router $router): void
    {
        $router->aliasMiddleware('sql-inspector', SqlInspectorMiddleware::class);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function startHttpSession(array $attributes): ?string
    {
        if (!$this->enabled() || !(bool) Arr::get($this->config, 'capture_http', true)) {
            return null;
        }

        return $this->startSession('http', $attributes['route'] ?? $attributes['path'] ?? 'http', $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function startCliSession(array $attributes): ?string
    {
        if (!$this->enabled() || !(bool) Arr::get($this->config, 'capture_cli', true)) {
            return null;
        }

        return $this->startSession('cli', $attributes['command'] ?? 'artisan', $attributes);
    }

    public function finishCurrentSession(): void
    {
        if ($this->activeSession === null) {
            return;
        }

        $session = $this->activeSession;
        $session->finish();
        $analysis = $this->analyzer->analyze($session->context(), $session->queries());
        $this->store->store($session->toSnapshot($analysis));
        $this->activeSession = null;
    }

    private function enabled(): bool
    {
        return (bool) Arr::get($this->config, 'enabled', true);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function startSession(string $type, ?string $name, array $attributes): string
    {
        if ($this->activeSession !== null) {
            $this->finishCurrentSession();
        }

        $context = new SessionContext(
            $this->generateProfileId(),
            $type,
            $name,
            $attributes,
            CarbonImmutable::now(),
        );

        $this->activeSession = new ActiveProfilerSession($context);

        return $context->id;
    }
}

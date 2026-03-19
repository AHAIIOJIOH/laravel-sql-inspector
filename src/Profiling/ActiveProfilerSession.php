<?php

namespace Ahaiiojioh\LaravelSqlInspector\Profiling;

use Ahaiiojioh\LaravelSqlInspector\Contracts\ProfilerSession;
use Ahaiiojioh\LaravelSqlInspector\Data\ProfileSnapshot;
use Ahaiiojioh\LaravelSqlInspector\Data\QueryRecord;
use Ahaiiojioh\LaravelSqlInspector\Data\SessionContext;
use Carbon\CarbonImmutable;

final class ActiveProfilerSession implements ProfilerSession
{
    /**
     * @var array<int, QueryRecord>
     */
    private array $queries = [];

    private bool $finished = false;

    public function __construct(
        private SessionContext $context,
    ) {
    }

    public function id(): string
    {
        return $this->context->id;
    }

    public function addQuery(QueryRecord $query): void
    {
        if ($this->finished) {
            return;
        }

        $this->queries[] = $query;
    }

    public function finish(): void
    {
        if ($this->finished) {
            return;
        }

        $this->finished = true;
        $this->context = $this->context->withEndedAt(CarbonImmutable::now());
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * @return array<int, QueryRecord>
     */
    public function queries(): array
    {
        return $this->queries;
    }

    public function context(): SessionContext
    {
        return $this->context;
    }

    public function toSnapshot(array $analysis): ProfileSnapshot
    {
        $queryPayload = array_map(
            static fn (QueryRecord $query): array => $query->toArray(),
            $this->queries,
        );

        return new ProfileSnapshot(
            $this->context,
            $analysis['summary'] ?? [],
            $queryPayload,
            $analysis['repeated_groups'] ?? [],
            $analysis['slow_queries'] ?? [],
            $analysis['flags'] ?? [],
            $analysis['warnings'] ?? [],
            $analysis['notes'] ?? [],
        );
    }
}

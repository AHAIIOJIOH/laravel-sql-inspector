<?php

namespace Ahaiiojioh\LaravelSqlInspector\Data;

final class RepeatedQueryGroup
{
    /**
     * @param array<int, QueryRecord> $queries
     */
    public function __construct(
        public readonly string $normalizedSql,
        public readonly array $queries,
    ) {
    }

    public function count(): int
    {
        return count($this->queries);
    }

    public function totalTimeMs(): float
    {
        return array_reduce(
            $this->queries,
            static fn (float $carry, QueryRecord $query): float => $carry + $query->timeMs,
            0.0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'normalized_sql' => $this->normalizedSql,
            'count' => $this->count(),
            'total_time_ms' => round($this->totalTimeMs(), 3),
            'sample_sql' => $this->queries[0]->sql ?? $this->normalizedSql,
            'connection_names' => array_values(array_unique(array_map(
                static fn (QueryRecord $query): string => $query->connectionName,
                $this->queries,
            ))),
        ];
    }
}

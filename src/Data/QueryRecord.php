<?php

namespace Ahaiiojioh\LaravelSqlInspector\Data;

final class QueryRecord
{
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings,
        public readonly float $timeMs,
        public readonly string $connectionName,
        public readonly string $normalizedSql,
        public readonly bool $failed = false,
    ) {
    }

    public function isSelect(): bool
    {
        return str_starts_with(ltrim(strtolower($this->sql)), 'select');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'time_ms' => $this->timeMs,
            'connection_name' => $this->connectionName,
            'normalized_sql' => $this->normalizedSql,
            'failed' => $this->failed,
        ];
    }
}

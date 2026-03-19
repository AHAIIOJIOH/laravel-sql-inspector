<?php

namespace Ahaiiojioh\LaravelSqlInspector\Profiling;

use Illuminate\Database\DatabaseManager;
use Throwable;

class DatabaseExplainRunner
{
    public function __construct(
        private DatabaseManager $database,
    ) {
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, notes: array<int, string>}
     */
    public function explain(string $connectionName, string $sql, array $bindings = []): array
    {
        try {
            $rows = $this->database->connection($connectionName)->select('EXPLAIN ' . $sql, $bindings);

            return [
                'rows' => array_map(
                    static fn (object|array $row): array => (array) $row,
                    $rows,
                ),
                'notes' => [],
            ];
        } catch (Throwable $exception) {
            return [
                'rows' => [],
                'notes' => ['EXPLAIN failed: ' . $exception->getMessage()],
            ];
        }
    }
}

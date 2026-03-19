<?php

namespace Ahaiiojioh\LaravelSqlInspector\Profiling;

use Ahaiiojioh\LaravelSqlInspector\Contracts\QueryAnalyzer;
use Ahaiiojioh\LaravelSqlInspector\Contracts\QueryNormalizer;
use Ahaiiojioh\LaravelSqlInspector\Data\QueryRecord;
use Ahaiiojioh\LaravelSqlInspector\Data\RepeatedQueryGroup;
use Ahaiiojioh\LaravelSqlInspector\Data\SessionContext;
use Illuminate\Support\Arr;

final class DefaultQueryAnalyzer implements QueryAnalyzer
{
    public function __construct(
        private QueryNormalizer $normalizer,
        private DatabaseExplainRunner $explainRunner,
        private array $config,
    ) {
    }

    /**
     * @return array|mixed[]
     */
    public function analyze(SessionContext $context, array $queries): array
    {
        $flags = [];
        $warnings = [];
        $notes = [];

        $totalTime = array_reduce(
            $queries,
            static fn (float $carry, QueryRecord $query): float => $carry + $query->timeMs,
            0.0,
        );

        $groups = $this->buildRepeatedGroups($queries);
        $repeatedWarningThreshold = (int) Arr::get($this->config, 'repeated_query_warning_threshold', 5);
        $nPlusOneThreshold = (int) Arr::get($this->config, 'n_plus_one_repeat_threshold', 3);
        $slowThreshold = (float) Arr::get($this->config, 'slow_query_threshold_ms', 100);

        $repeatedPayload = [];

        foreach ($groups as $group) {
            $repeatedPayload[] = $group->toArray();

            if ($group->count() >= $repeatedWarningThreshold) {
                $flags[] = 'too_many_repeated_similar_queries';
                $warnings[] = [
                    'type' => 'repeated_queries',
                    'message' => sprintf(
                        'Repeated query pattern detected %d times.',
                        $group->count(),
                    ),
                    'normalized_sql' => $group->normalizedSql,
                ];
            }

            if ($group->count() >= $nPlusOneThreshold && str_starts_with($group->normalizedSql, 'select')) {
                $warnings[] = [
                    'type' => 'n_plus_one',
                    'message' => sprintf(
                        'Potential N+1 pattern detected for normalized SELECT repeated %d times.',
                        $group->count(),
                    ),
                    'normalized_sql' => $group->normalizedSql,
                ];
            }
        }

        $slowQueries = [];

        foreach ($queries as $index => $query) {
            if ($query->timeMs < $slowThreshold) {
                continue;
            }

            $slowEntry = [
                'index' => $index,
                'sql' => $query->sql,
                'normalized_sql' => $query->normalizedSql,
                'time_ms' => $query->timeMs,
                'connection_name' => $query->connectionName,
                'explain' => [],
                'flags' => [],
            ];

            if ($this->shouldExplain($query)) {
                $explain = $this->explainRunner->explain($query->connectionName, $query->sql, $query->bindings);
                $slowEntry['explain'] = $explain['rows'];
                $notes = array_merge($notes, $explain['notes']);

                foreach ($explain['rows'] as $row) {
                    $rowFlags = $this->flagsFromExplainRow($row);
                    $flags = array_merge($flags, $rowFlags);
                    $slowEntry['flags'] = array_values(array_unique(array_merge($slowEntry['flags'], $rowFlags)));
                }
            }

            $slowQueries[] = $slowEntry;
        }

        return [
            'summary' => [
                'query_count' => count($queries),
                'total_query_time_ms' => round($totalTime, 3),
                'slow_query_count' => count($slowQueries),
                'repeated_group_count' => count($repeatedPayload),
            ],
            'repeated_groups' => $repeatedPayload,
            'slow_queries' => $slowQueries,
            'flags' => array_values(array_unique($flags)),
            'warnings' => $warnings,
            'notes' => array_values(array_unique($notes)),
        ];
    }

    /**
     * @param array<int, QueryRecord> $queries
     * @return array<int, RepeatedQueryGroup>
     */
    private function buildRepeatedGroups(array $queries): array
    {
        $bucketed = [];

        foreach ($queries as $query) {
            $normalized = $query->normalizedSql ?: $this->normalizer->normalize($query->sql, $query->bindings);
            $bucketed[$normalized][] = $query;
        }

        $groups = [];

        foreach ($bucketed as $normalized => $items) {
            if (count($items) < 2) {
                continue;
            }

            $groups[] = new RepeatedQueryGroup($normalized, $items);
        }

        usort(
            $groups,
            static fn (RepeatedQueryGroup $left, RepeatedQueryGroup $right): int => $right->count() <=> $left->count(),
        );

        return $groups;
    }

    private function shouldExplain(QueryRecord $query): bool
    {
        if ($query->failed || !$query->isSelect()) {
            return false;
        }

        if (!(bool) Arr::get($this->config, 'explain.only_slow_select', true)) {
            return true;
        }

        if (!(bool) Arr::get($this->config, 'explain.mysql_only', true)) {
            return true;
        }

        return $this->isMySqlConnection($query->connectionName);
    }

    private function isMySqlConnection(string $connectionName): bool
    {
        $driver = Arr::get($this->config, "database.connections.{$connectionName}.driver");

        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    private function flagsFromExplainRow(array $row): array
    {
        $flags = [];
        $scanType = strtolower((string) ($row['type'] ?? ''));
        $possibleKeys = $row['possible_keys'] ?? null;
        $extra = strtolower((string) ($row['Extra'] ?? $row['extra'] ?? ''));

        if (in_array($scanType, ['all', 'index'], true)) {
            $flags[] = 'full_scan_detected';
        }

        if ($possibleKeys === null || $possibleKeys === '') {
            $flags[] = 'no_index_used';
        }

        if (str_contains($extra, 'filesort')) {
            $flags[] = 'filesort_detected';
        }

        return $flags;
    }
}

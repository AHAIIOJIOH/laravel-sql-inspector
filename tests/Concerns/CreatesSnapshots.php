<?php

namespace Ahaiiojioh\LaravelSqlInspector\Tests\Concerns;

use Ahaiiojioh\LaravelSqlInspector\Data\ProfileSnapshot;
use Ahaiiojioh\LaravelSqlInspector\Data\SessionContext;
use Carbon\CarbonImmutable;

trait CreatesSnapshots
{
    protected function makeSnapshot(array $overrides = []): ProfileSnapshot
    {
        $context = new SessionContext(
            $overrides['id'] ?? 'session-1',
            $overrides['type'] ?? 'cli',
            $overrides['name'] ?? 'demo:command',
            $overrides['attributes'] ?? ['command' => 'demo:command'],
            CarbonImmutable::parse('2026-03-12T10:00:00+09:00'),
            CarbonImmutable::parse('2026-03-12T10:00:02+09:00'),
        );

        return new ProfileSnapshot(
            $context,
            $overrides['summary'] ?? [
                'query_count' => 3,
                'total_query_time_ms' => 45.5,
                'slow_query_count' => 1,
                'repeated_group_count' => 1,
            ],
            $overrides['queries'] ?? [
                [
                    'sql' => 'select * from users where id = ?',
                    'bindings' => [1],
                    'time_ms' => 45.5,
                    'connection_name' => 'testing',
                    'normalized_sql' => 'select * from users where id = ?',
                    'failed' => false,
                ],
            ],
            $overrides['repeated_groups'] ?? [
                [
                    'normalized_sql' => 'select * from users where id = ?',
                    'count' => 3,
                    'total_time_ms' => 45.5,
                    'sample_sql' => 'select * from users where id = ?',
                    'connection_names' => ['testing'],
                ],
            ],
            $overrides['slow_queries'] ?? [
                [
                    'index' => 0,
                    'sql' => 'select * from users where id = ?',
                    'normalized_sql' => 'select * from users where id = ?',
                    'time_ms' => 45.5,
                    'connection_name' => 'testing',
                    'explain' => [],
                    'flags' => ['full_scan_detected'],
                ],
            ],
            $overrides['flags'] ?? ['full_scan_detected'],
            $overrides['warnings'] ?? [
                [
                    'type' => 'n_plus_one',
                    'message' => 'Potential N+1 pattern detected for normalized SELECT repeated 3 times.',
                    'normalized_sql' => 'select * from users where id = ?',
                ],
            ],
            $overrides['notes'] ?? [],
        );
    }
}

<?php

namespace Ahaiiojioh\LaravelSqlInspector\Tests\Unit;

use Ahaiiojioh\LaravelSqlInspector\Data\QueryRecord;
use Ahaiiojioh\LaravelSqlInspector\Data\SessionContext;
use Ahaiiojioh\LaravelSqlInspector\Profiling\DatabaseExplainRunner;
use Ahaiiojioh\LaravelSqlInspector\Profiling\DefaultQueryAnalyzer;
use Ahaiiojioh\LaravelSqlInspector\Profiling\SqlNormalizer;
use Ahaiiojioh\LaravelSqlInspector\Tests\TestCase;
use Carbon\CarbonImmutable;

final class DefaultQueryAnalyzerTest extends TestCase
{
    public function test_it_flags_slow_queries_and_mysql_explain_results(): void
    {
        $runner = new class extends DatabaseExplainRunner
        {
            public function __construct()
            {
            }

            public function explain(string $connectionName, string $sql, array $bindings = []): array
            {
                return [
                    'rows' => [[
                        'type' => 'ALL',
                        'possible_keys' => null,
                        'Extra' => 'Using filesort',
                    ]],
                    'notes' => [],
                ];
            }
        };

        $analyzer = new DefaultQueryAnalyzer(
            new SqlNormalizer(),
            $runner,
            [
                'slow_query_threshold_ms' => 100,
                'n_plus_one_repeat_threshold' => 2,
                'repeated_query_warning_threshold' => 2,
                'explain' => ['mysql_only' => true, 'only_slow_select' => true],
                'database' => [
                    'connections' => [
                        'mysql-test' => ['driver' => 'mysql'],
                    ],
                ],
            ],
        );

        $result = $analyzer->analyze(
            new SessionContext('1', 'cli', 'demo', [], CarbonImmutable::now(), CarbonImmutable::now()),
            [
                new QueryRecord('select * from users where id = ?', [1], 150, 'mysql-test', 'select * from users where id = ?'),
                new QueryRecord('select * from users where id = ?', [2], 120, 'mysql-test', 'select * from users where id = ?'),
            ],
        );

        $this->assertContains('full_scan_detected', $result['flags']);
        $this->assertContains('no_index_used', $result['flags']);
        $this->assertContains('filesort_detected', $result['flags']);
        $this->assertContains('too_many_repeated_similar_queries', $result['flags']);
        $this->assertCount(2, $result['slow_queries']);
        $this->assertNotEmpty($result['slow_queries'][0]['explain']);
        $warningTypes = array_column($result['warnings'], 'type');

        $this->assertContains('n_plus_one', $warningTypes);
    }

    public function test_it_skips_explain_for_non_mysql_connections(): void
    {
        $runner = new class extends DatabaseExplainRunner
        {
            public int $calls = 0;

            public function __construct()
            {
            }

            public function explain(string $connectionName, string $sql, array $bindings = []): array
            {
                $this->calls++;

                return ['rows' => [], 'notes' => []];
            }
        };

        $analyzer = new DefaultQueryAnalyzer(
            new SqlNormalizer(),
            $runner,
            [
                'slow_query_threshold_ms' => 100,
                'n_plus_one_repeat_threshold' => 2,
                'repeated_query_warning_threshold' => 2,
                'explain' => ['mysql_only' => true, 'only_slow_select' => true],
                'database' => [
                    'connections' => [
                        'sqlite-test' => ['driver' => 'sqlite'],
                    ],
                ],
            ],
        );

        $analyzer->analyze(
            new SessionContext('1', 'cli', 'demo', [], CarbonImmutable::now(), CarbonImmutable::now()),
            [
                new QueryRecord('select * from users', [], 150, 'sqlite-test', 'select * from users'),
            ],
        );

        $this->assertSame(0, $runner->calls);
    }
}

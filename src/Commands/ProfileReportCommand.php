<?php

namespace Ahaiiojioh\LaravelSqlInspector\Commands;

use Ahaiiojioh\LaravelSqlInspector\Contracts\SnapshotStore;
use Illuminate\Console\Command;

final class ProfileReportCommand extends Command
{
    protected $signature = 'profile:report {--limit=10 : Number of snapshots to inspect}';

    protected $description = 'Show SQL inspector runtime snapshots.';

    public function __construct(
        private SnapshotStore $store,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->store->canRead()) {
            $this->warn((string) $this->store->limitationMessage());

            return self::SUCCESS;
        }

        $snapshots = $this->store->latest((int) $this->option('limit'));

        if ($snapshots === []) {
            $this->line('No snapshots found.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Loaded %d snapshot(s).', count($snapshots)));

        foreach ($snapshots as $index => $snapshot) {
            $session = $snapshot['session'];
            $summary = $snapshot['summary'];

            $this->newLine();
            $this->line(sprintf(
                '[%d] %s %s (%s)',
                $index + 1,
                strtoupper((string) $session['type']),
                (string) ($session['name'] ?? 'unnamed'),
                (string) ($session['ended_at'] ?? $session['started_at'] ?? 'n/a'),
            ));

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Queries', (string) ($summary['query_count'] ?? 0)],
                    ['Total query time (ms)', (string) ($summary['total_query_time_ms'] ?? 0)],
                    ['Slow queries', (string) ($summary['slow_query_count'] ?? 0)],
                    ['Repeated groups', (string) ($summary['repeated_group_count'] ?? 0)],
                ],
            );

            $this->renderSlowQueries($snapshot['slow_queries'] ?? []);
            $this->renderRepeatedGroups($snapshot['repeated_groups'] ?? []);
            $this->renderFlags($snapshot['flags'] ?? [], $snapshot['warnings'] ?? []);
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $slowQueries
     */
    private function renderSlowQueries(array $slowQueries): void
    {
        $this->line('Top slow queries:');

        if ($slowQueries === []) {
            $this->line('  none');

            return;
        }

        $rows = array_map(static fn (array $query): array => [
            (string) ($query['time_ms'] ?? 0),
            (string) ($query['connection_name'] ?? 'default'),
            (string) ($query['sql'] ?? ''),
            implode(', ', $query['flags'] ?? []),
        ], array_slice($slowQueries, 0, 5));

        $this->table(['ms', 'connection', 'sql', 'flags'], $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     */
    private function renderRepeatedGroups(array $groups): void
    {
        $this->line('Repeated groups:');

        if ($groups === []) {
            $this->line('  none');

            return;
        }

        $rows = array_map(static fn (array $group): array => [
            (string) ($group['count'] ?? 0),
            (string) ($group['total_time_ms'] ?? 0),
            (string) ($group['sample_sql'] ?? $group['normalized_sql'] ?? ''),
        ], array_slice($groups, 0, 5));

        $this->table(['count', 'total ms', 'sample sql'], $rows);
    }

    /**
     * @param array<int, string> $flags
     * @param array<int, array<string, mixed>> $warnings
     */
    private function renderFlags(array $flags, array $warnings): void
    {
        $this->line('Warnings and flags:');

        if ($flags === [] && $warnings === []) {
            $this->line('  none');

            return;
        }

        foreach ($flags as $flag) {
            $this->line('  [flag] ' . $flag);
        }

        foreach ($warnings as $warning) {
            $this->line('  [warning] ' . ($warning['message'] ?? ''));
        }
    }
}

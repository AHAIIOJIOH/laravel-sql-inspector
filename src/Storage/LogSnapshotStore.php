<?php

namespace Ahaiiojioh\LaravelSqlInspector\Storage;

use Ahaiiojioh\LaravelSqlInspector\Contracts\SnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Data\ProfileSnapshot;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

final class LogSnapshotStore implements SnapshotStore
{
    public function __construct(
        private LogManager $logs,
        private ?string $channel = null,
    ) {
    }

    public function store(ProfileSnapshot $snapshot): void
    {
        $logger = $this->logger();
        $payload = $snapshot->toArray();

        $logger->warning('sql-inspector snapshot', [
            'session' => $payload['session'],
            'summary' => $payload['summary'],
            'flags' => $payload['flags'],
            'warnings' => $payload['warnings'],
            'slow_queries' => array_slice($payload['slow_queries'], 0, 5),
        ]);
    }

    public function canRead(): bool
    {
        return false;
    }

    public function limitationMessage(): ?string
    {
        return 'The log driver is write-oriented. Use json or db storage for historical profile:report output.';
    }

    public function latest(int $limit = 10): array
    {
        return [];
    }

    private function logger(): LoggerInterface
    {
        return $this->channel ? $this->logs->channel($this->channel) : $this->logs->driver();
    }
}

<?php

namespace Ahaiiojioh\LaravelSqlInspector\Storage;

use Ahaiiojioh\LaravelSqlInspector\Contracts\SnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Data\ProfileSnapshot;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;

final class DatabaseSnapshotStore implements SnapshotStore
{
    public function __construct(
        private DatabaseManager $database,
        private string $connectionName,
        private string $table,
    ) {
    }

    public function store(ProfileSnapshot $snapshot): void
    {
        $connection = $this->connection();
        $payload = $snapshot->toArray();

        $connection->table($this->table)->insert([
            'session_id' => $snapshot->context->id,
            'type' => $snapshot->context->type,
            'name' => $snapshot->context->name,
            'query_count' => $payload['summary']['query_count'] ?? count($payload['queries']),
            'total_query_time_ms' => $payload['summary']['total_query_time_ms'] ?? 0,
            'flags' => json_encode($payload['flags'], JSON_THROW_ON_ERROR),
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'recorded_at' => $snapshot->context->endedAt?->toDateTimeString() ?? Carbon::now()->toDateTimeString(),
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function canRead(): bool
    {
        return true;
    }

    public function limitationMessage(): ?string
    {
        return null;
    }

    public function latest(int $limit = 10): array
    {
        return $this->connection()
            ->table($this->table)
            ->orderByDesc('recorded_at')
            ->limit($limit)
            ->get()
            ->map(static fn (object $row): array => json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR))
            ->all();
    }

    private function connection(): Connection
    {
        return $this->connectionName !== ''
            ? $this->database->connection($this->connectionName)
            : $this->database->connection();
    }
}

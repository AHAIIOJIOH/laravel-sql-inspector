<?php

namespace Ahaiiojioh\LaravelSqlInspector\Contracts;

interface SnapshotReader
{
    public function canRead(): bool;

    public function limitationMessage(): ?string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 10): array;
}

<?php

namespace Ahaiiojioh\LaravelSqlInspector\Contracts;

use Ahaiiojioh\LaravelSqlInspector\Data\QueryRecord;
use Ahaiiojioh\LaravelSqlInspector\Data\ProfileSnapshot;

interface ProfilerSession
{
    public function id(): string;

    public function addQuery(QueryRecord $query): void;

    public function finish(): void;

    public function isFinished(): bool;

    public function toSnapshot(array $analysis): ProfileSnapshot;
}

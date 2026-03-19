<?php

namespace Ahaiiojioh\LaravelSqlInspector\Contracts;

use Ahaiiojioh\LaravelSqlInspector\Data\ProfileSnapshot;

interface SnapshotStore extends SnapshotReader
{
    public function store(ProfileSnapshot $snapshot): void;
}

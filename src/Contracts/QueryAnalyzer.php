<?php

namespace Ahaiiojioh\LaravelSqlInspector\Contracts;

use Ahaiiojioh\LaravelSqlInspector\Data\QueryRecord;
use Ahaiiojioh\LaravelSqlInspector\Data\SessionContext;

interface QueryAnalyzer
{
    /**
     * @param array<int, QueryRecord> $queries
     *
     * @return array<string, mixed>
     */
    public function analyze(SessionContext $context, array $queries): array;
}

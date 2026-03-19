<?php

namespace Ahaiiojioh\LaravelSqlInspector\Contracts;

interface QueryNormalizer
{
    public function normalize(string $sql, array $bindings = []): string;
}

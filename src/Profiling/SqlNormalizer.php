<?php

namespace Ahaiiojioh\LaravelSqlInspector\Profiling;

use Ahaiiojioh\LaravelSqlInspector\Contracts\QueryNormalizer;

final class SqlNormalizer implements QueryNormalizer
{
    public function normalize(string $sql, array $bindings = []): string
    {
        $normalized = trim(strtolower($sql));
        $normalized = preg_replace("/'[^']*'/", '?', $normalized) ?? $normalized;
        $normalized = preg_replace('/\b\d+\b/', '?', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bnull\b/', '?', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bin\s*\((?:\s*\?\s*,?)+\)/', 'in (?*)', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bin\s*\((?:\s*[^)]+)\)/', 'in (?*)', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}

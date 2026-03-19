<?php

namespace Ahaiiojioh\LaravelSqlInspector\Data;

final class ProfileSnapshot
{
    /**
     * @param array<string, mixed> $summary
     * @param array<int, array<string, mixed>> $queries
     * @param array<int, array<string, mixed>> $repeatedGroups
     * @param array<int, array<string, mixed>> $slowQueries
     * @param array<int, string> $flags
     * @param array<int, array<string, mixed>> $warnings
     * @param array<int, string> $notes
     */
    public function __construct(
        public readonly SessionContext $context,
        public readonly array $summary,
        public readonly array $queries,
        public readonly array $repeatedGroups,
        public readonly array $slowQueries,
        public readonly array $flags,
        public readonly array $warnings,
        public readonly array $notes = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session' => $this->context->toArray(),
            'summary' => $this->summary,
            'queries' => $this->queries,
            'repeated_groups' => $this->repeatedGroups,
            'slow_queries' => $this->slowQueries,
            'flags' => array_values(array_unique($this->flags)),
            'warnings' => $this->warnings,
            'notes' => $this->notes,
        ];
    }
}

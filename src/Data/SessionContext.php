<?php

namespace Ahaiiojioh\LaravelSqlInspector\Data;

use Carbon\CarbonImmutable;

final class SessionContext
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly ?string $name,
        public readonly array $attributes,
        public readonly CarbonImmutable $startedAt,
        public readonly ?CarbonImmutable $endedAt = null,
    ) {
    }

    public function withEndedAt(CarbonImmutable $endedAt): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->name,
            $this->attributes,
            $this->startedAt,
            $endedAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'started_at' => $this->startedAt->toIso8601String(),
            'ended_at' => $this->endedAt?->toIso8601String(),
        ];
    }
}

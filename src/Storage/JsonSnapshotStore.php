<?php

namespace Ahaiiojioh\LaravelSqlInspector\Storage;

use Ahaiiojioh\LaravelSqlInspector\Contracts\SnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Data\ProfileSnapshot;
use Illuminate\Support\Facades\File;

final class JsonSnapshotStore implements SnapshotStore
{
    public function __construct(
        private string $path,
    ) {
    }

    public function store(ProfileSnapshot $snapshot): void
    {
        File::ensureDirectoryExists($this->path);

        File::put(
            $this->snapshotPath($snapshot->context->id),
            json_encode($snapshot->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
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
        if (!File::exists($this->path)) {
            return [];
        }

        $files = collect(File::files($this->path))
            ->sortByDesc(static fn (\SplFileInfo $file): int => $file->getMTime())
            ->take($limit);

        return $files->map(function (\SplFileInfo $file): array {
            $contents = File::get($file->getPathname());

            return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        })->values()->all();
    }

    private function snapshotPath(string $sessionId): string
    {
        return rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sessionId . '.json';
    }
}

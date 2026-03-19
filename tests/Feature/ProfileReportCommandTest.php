<?php

namespace Ahaiiojioh\LaravelSqlInspector\Tests\Feature;

use Ahaiiojioh\LaravelSqlInspector\Storage\JsonSnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Tests\Concerns\CreatesSnapshots;
use Ahaiiojioh\LaravelSqlInspector\Tests\TestCase;

final class ProfileReportCommandTest extends TestCase
{
    use CreatesSnapshots;

    public function test_artisan_report_displays_summary_data(): void
    {
        $path = $this->app['config']->get('sql-inspector.storage.json.path');
        @mkdir($path, 0777, true);
        array_map('unlink', glob($path . '/*.json') ?: []);

        (new JsonSnapshotStore($path))->store($this->makeSnapshot());

        $this->artisan('profile:report')
            ->expectsOutputToContain('Loaded 1 snapshot')
            ->expectsOutputToContain('Top slow queries:')
            ->expectsOutputToContain('Repeated groups:')
            ->expectsOutputToContain('Warnings and flags:')
            ->assertSuccessful();
    }
}

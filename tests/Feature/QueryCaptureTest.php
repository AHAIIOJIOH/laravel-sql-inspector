<?php

namespace Ahaiiojioh\LaravelSqlInspector\Tests\Feature;

use Ahaiiojioh\LaravelSqlInspector\Profiling\ProfilerManager;
use Ahaiiojioh\LaravelSqlInspector\Tests\TestCase;
use Illuminate\Support\Facades\DB;

final class QueryCaptureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        @mkdir($this->app['config']->get('sql-inspector.storage.json.path'), 0777, true);
        array_map('unlink', glob($this->app['config']->get('sql-inspector.storage.json.path') . '/*.json') ?: []);
    }

    public function test_it_captures_queries_and_groups_repeated_statements(): void
    {
        $manager = $this->app->make(ProfilerManager::class);

        $manager->startCliSession(['command' => 'demo:run']);
        DB::select('select ? as value', [1]);
        DB::select('select ? as value', [2]);
        $manager->finishCurrentSession();

        $snapshot = $this->latestSnapshot();

        $this->assertSame('cli', $snapshot['session']['type']);
        $this->assertSame('demo:run', $snapshot['session']['name']);
        $this->assertCount(2, $snapshot['queries']);
        $this->assertSame('testing', $snapshot['queries'][0]['connection_name']);
        $this->assertNotEmpty($snapshot['repeated_groups']);
        $this->assertSame(2, $snapshot['repeated_groups'][0]['count']);
    }

    private function latestSnapshot(): array
    {
        $files = glob($this->app['config']->get('sql-inspector.storage.json.path') . '/*.json') ?: [];
        rsort($files);

        return json_decode((string) file_get_contents($files[0]), true, 512, JSON_THROW_ON_ERROR);
    }
}

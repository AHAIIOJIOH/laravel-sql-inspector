<?php

namespace Ahaiiojioh\LaravelSqlInspector\Tests\Feature;

use Ahaiiojioh\LaravelSqlInspector\Storage\DatabaseSnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Storage\JsonSnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Storage\LogSnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Tests\Concerns\CreatesSnapshots;
use Ahaiiojioh\LaravelSqlInspector\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

final class StorageDriversTest extends TestCase
{
    use CreatesSnapshots;

    protected function setUp(): void
    {
        parent::setUp();

        @mkdir($this->app['config']->get('sql-inspector.storage.json.path'), 0777, true);
        array_map('unlink', glob($this->app['config']->get('sql-inspector.storage.json.path') . '/*.json') ?: []);
    }

    public function test_json_store_writes_and_reads_snapshots(): void
    {
        $store = new JsonSnapshotStore($this->app['config']->get('sql-inspector.storage.json.path'));
        $store->store($this->makeSnapshot());

        $snapshots = $store->latest();

        $this->assertCount(1, $snapshots);
        $this->assertSame('session-1', $snapshots[0]['session']['id']);
    }

    public function test_database_store_writes_and_reads_snapshots(): void
    {
        Schema::create('sql_inspector_snapshots', function ($table): void {
            $table->id();
            $table->string('session_id');
            $table->string('type', 16);
            $table->string('name')->nullable();
            $table->unsignedInteger('query_count')->default(0);
            $table->decimal('total_query_time_ms', 12, 3)->default(0);
            $table->json('flags')->nullable();
            $table->json('payload');
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });

        $store = new DatabaseSnapshotStore($this->app['db'], '', 'sql_inspector_snapshots');
        $store->store($this->makeSnapshot());

        $snapshots = $store->latest();

        $this->assertCount(1, $snapshots);
        $this->assertSame('demo:command', $snapshots[0]['session']['name']);
    }

    public function test_log_store_accepts_snapshot_writes(): void
    {
        $store = new LogSnapshotStore($this->app['log']);
        $store->store($this->makeSnapshot());

        $this->assertFalse($store->canRead());
        $this->assertNotNull($store->limitationMessage());
    }
}

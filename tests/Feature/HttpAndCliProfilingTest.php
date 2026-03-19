<?php

namespace Ahaiiojioh\LaravelSqlInspector\Tests\Feature;

use Ahaiiojioh\LaravelSqlInspector\Tests\TestCase;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class HttpAndCliProfilingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $path = $this->app['config']->get('sql-inspector.storage.json.path');
        @mkdir($path, 0777, true);
        array_map('unlink', glob($path . '/*.json') ?: []);

        Route::middleware('sql-inspector')->get('/sql-inspector-demo', function () {
            DB::select('select ? as value', [1]);

            return response()->json(['ok' => true]);
        })->name('sql-inspector.demo');

    }

    public function test_http_middleware_records_request_metadata(): void
    {
        $this->get('/sql-inspector-demo')->assertOk();

        $snapshot = $this->latestSnapshot();

        $this->assertSame('http', $snapshot['session']['type']);
        $this->assertSame('GET', $snapshot['session']['attributes']['method']);
        $this->assertSame('sql-inspector-demo', $snapshot['session']['attributes']['path']);
        $this->assertSame('sql-inspector.demo', $snapshot['session']['attributes']['route']);
    }

    public function test_cli_listener_records_command_name(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $this->app['events']->dispatch(new CommandStarting('sql-inspector:demo', $input, $output));
        DB::select('select ? as value', [1]);
        $this->app['events']->dispatch(new CommandFinished('sql-inspector:demo', $input, $output, 0));

        $snapshot = $this->latestSnapshot();

        $this->assertSame('cli', $snapshot['session']['type']);
        $this->assertSame('sql-inspector:demo', $snapshot['session']['attributes']['command']);
    }

    private function latestSnapshot(): array
    {
        $files = glob($this->app['config']->get('sql-inspector.storage.json.path') . '/*.json') ?: [];
        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return json_decode((string) file_get_contents($files[0]), true, 512, JSON_THROW_ON_ERROR);
    }
}

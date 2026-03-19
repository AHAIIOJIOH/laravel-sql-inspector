<?php

namespace Ahaiiojioh\LaravelSqlInspector\Tests;

use Ahaiiojioh\LaravelSqlInspector\SqlInspectorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SqlInspectorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('sql-inspector.storage.default', 'json');
        $app['config']->set('sql-inspector.storage.json.path', sys_get_temp_dir() . '/laravel-sql-inspector-tests');
        $app['config']->set('logging.default', 'stack');
    }
}

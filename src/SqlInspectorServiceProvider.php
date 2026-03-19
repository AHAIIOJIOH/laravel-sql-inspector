<?php

namespace Ahaiiojioh\LaravelSqlInspector;

use Ahaiiojioh\LaravelSqlInspector\Commands\ProfileReportCommand;
use Ahaiiojioh\LaravelSqlInspector\Contracts\QueryAnalyzer;
use Ahaiiojioh\LaravelSqlInspector\Contracts\QueryNormalizer;
use Ahaiiojioh\LaravelSqlInspector\Contracts\SnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Listeners\RegisterConsoleProfilingListeners;
use Ahaiiojioh\LaravelSqlInspector\Profiling\DatabaseExplainRunner;
use Ahaiiojioh\LaravelSqlInspector\Profiling\DefaultQueryAnalyzer;
use Ahaiiojioh\LaravelSqlInspector\Profiling\ProfilerManager;
use Ahaiiojioh\LaravelSqlInspector\Profiling\SqlNormalizer;
use Ahaiiojioh\LaravelSqlInspector\Storage\DatabaseSnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Storage\JsonSnapshotStore;
use Ahaiiojioh\LaravelSqlInspector\Storage\LogSnapshotStore;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class SqlInspectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sql-inspector.php', 'sql-inspector');

        $this->app->singleton(QueryNormalizer::class, SqlNormalizer::class);
        $this->app->singleton(DatabaseExplainRunner::class);

        $this->app->singleton(QueryAnalyzer::class, function (Application $app): DefaultQueryAnalyzer {
            return new DefaultQueryAnalyzer(
                $app->make(QueryNormalizer::class),
                $app->make(DatabaseExplainRunner::class),
                [
                    ...$app['config']->get('sql-inspector', []),
                    'database' => $app['config']->get('database', []),
                ],
            );
        });

        $this->app->singleton(SnapshotStore::class, function (Application $app): SnapshotStore {
            return match ($app['config']->get('sql-inspector.storage.default', 'json')) {
                'db' => new DatabaseSnapshotStore(
                    $app['db'],
                    (string) $app['config']->get('sql-inspector.storage.db.connection'),
                    (string) $app['config']->get('sql-inspector.storage.db.table', 'sql_inspector_snapshots'),
                ),
                'log' => new LogSnapshotStore(
                    $app['log'],
                    $app['config']->get('sql-inspector.storage.log.channel'),
                ),
                default => new JsonSnapshotStore(
                    (string) $app['config']->get('sql-inspector.storage.json.path'),
                ),
            };
        });

        $this->app->singleton(ProfilerManager::class, function (Application $app): ProfilerManager {
            return new ProfilerManager(
                $app->make(QueryNormalizer::class),
                $app->make(QueryAnalyzer::class),
                $app->make(SnapshotStore::class),
                $app['config']->get('sql-inspector', []),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sql-inspector.php' => config_path('sql-inspector.php'),
        ], 'sql-inspector-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/create_sql_inspector_snapshots_table.php.stub' => database_path('migrations/' . date('Y_m_d_His') . '_create_sql_inspector_snapshots_table.php'),
        ], 'sql-inspector-migrations');

        $this->commands([
            ProfileReportCommand::class,
        ]);

        if ($this->app->bound('router')) {
            $this->app->make(ProfilerManager::class)->registerHttpMiddleware($this->app->make(Router::class));
        }

        $this->app->make(RegisterConsoleProfilingListeners::class)->register();
        $this->app->make(ProfilerManager::class)->registerQueryListener();
    }
}

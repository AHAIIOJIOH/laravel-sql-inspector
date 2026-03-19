# laravel-sql-inspector

`laravel-sql-inspector` is a Laravel package for catching SQL problems while your app is actually running.

It focuses on the kind of issues that quietly hurt production performance:

- repeated similar queries
- heuristic N+1 patterns
- slow query snapshots
- MySQL `EXPLAIN` for slow `SELECT` statements
- lightweight reporting through Artisan

This package is intentionally small. No UI. No Telescope dependency. No vendor lock-in to a hosted dashboard. Just runtime diagnostics you can keep close to the application.

## Why This Package Exists

Laravel gives you enough power to build fast, but SQL regressions still slip in through normal feature work.

A page can stay functionally correct while becoming much slower because:

- one eager load was removed
- a loop now triggers repeated lookups
- a harmless-looking filter causes a table scan
- a new endpoint introduces a few slow queries that nobody notices locally

`laravel-sql-inspector` exists to make those problems visible in a practical, package-friendly way.

It is designed for:

- local debugging
- CI-friendly smoke diagnostics
- package-based profiling in apps that do not want a full monitoring UI
- teams that want snapshots and CLI reports, not another dashboard

## Features

- HTTP and CLI profiling lifecycle
- query capture through `DB::listen()`
- normalized SQL grouping for repeated-query analysis
- heuristic N+1 warnings for repeated normalized `SELECT` statements
- slow query detection with configurable threshold
- MySQL/MariaDB `EXPLAIN` on slow `SELECT` queries
- runtime flags:
  - `full_scan_detected`
  - `no_index_used`
  - `filesort_detected`
  - `too_many_repeated_similar_queries`
- storage drivers:
  - `json`
  - `db`
  - `log`
- `php artisan profile:report`

## Requirements

- PHP `8.2+`
- Laravel `11.x`

## Installation

```bash
composer require ahaiiojioh/laravel-sql-inspector
```

Publish the config:

```bash
php artisan vendor:publish --tag=sql-inspector-config
```

If you want database-backed snapshots, publish the migration too:

```bash
php artisan vendor:publish --tag=sql-inspector-migrations
php artisan migrate
```

## Quick Start

The default storage driver is `json`, which makes the package easy to try locally.

1. Install the package.
2. Publish the config.
3. Add the middleware alias to routes you want to profile, or attach it globally in your app middleware stack.
4. Run HTTP requests or Artisan commands.
5. Inspect snapshots with:

```bash
php artisan profile:report
```

Example route:

```php
Route::middleware('sql-inspector')->get('/orders', OrderController::class);
```

## Configuration

```php
return [
    'enabled' => true,
    'capture_http' => true,
    'capture_cli' => true,
    'slow_query_threshold_ms' => 100,
    'n_plus_one_repeat_threshold' => 3,
    'repeated_query_warning_threshold' => 5,
    'storage' => [
        'default' => 'json',
        'json' => [
            'path' => storage_path('app/sql-inspector'),
        ],
        'log' => [
            'channel' => null,
        ],
        'db' => [
            'connection' => null,
            'table' => 'sql_inspector_snapshots',
        ],
    ],
    'explain' => [
        'mysql_only' => true,
        'only_slow_select' => true,
    ],
];
```

## Storage Drivers

### `json`

Best default for local work and demos.

- one snapshot file per session
- easy to inspect manually
- easy to use with `profile:report`

### `db`

Best when you want retained history inside the application database.

- snapshots stored as a single JSON payload
- easy to query latest runs
- still simple enough for an MVP package

### `log`

Useful when you only want streaming diagnostics in your normal Laravel logs.

- lightweight write path
- good for temporary debugging
- not intended as a rich historical reporting backend

`profile:report` will explicitly tell you when the current driver is log-only.

## Example Report

```text
Loaded 2 snapshot(s).

[1] HTTP orders.index (2026-03-12T10:30:05+09:00)
+-----------------------+-------+
| Metric                | Value |
+-----------------------+-------+
| Queries               | 18    |
| Total query time (ms) | 212.4 |
| Slow queries          | 2     |
| Repeated groups       | 3     |
+-----------------------+-------+

Top slow queries:
+-------+------------+--------------------------------------------------+---------------------------+
| ms    | connection | sql                                              | flags                     |
+-------+------------+--------------------------------------------------+---------------------------+
| 148.2 | mysql      | select * from orders where status = ? order ...  | filesort_detected         |
| 118.1 | mysql      | select * from users where id = ?                 | full_scan_detected        |
+-------+------------+--------------------------------------------------+---------------------------+

Repeated groups:
+-------+----------+-------------------------------------------+
| count | total ms | sample sql                                |
+-------+----------+-------------------------------------------+
| 9     | 71.5     | select * from users where id = ?          |
| 4     | 33.7     | select * from order_items where order ... |
+-------+----------+-------------------------------------------+

Warnings and flags:
  [flag] too_many_repeated_similar_queries
  [flag] filesort_detected
  [warning] Potential N+1 pattern detected for normalized SELECT repeated 9 times.
```

## How Analysis Works

### Repeated query grouping

Queries are normalized before grouping so that similar statements collapse into the same pattern.

For example:

- `select * from users where id = 1`
- `select * from users where id = 2`

Both map to the same normalized form and can be reported as a repeated group.

### N+1 heuristic

This package does not pretend it can prove every N+1 issue with perfect accuracy.

Instead, it raises a warning when a normalized `SELECT` pattern is repeated above the configured threshold. That keeps the signal useful without over-claiming certainty.

### Slow query `EXPLAIN`

For MySQL-family connections, slow `SELECT` queries can be analyzed with `EXPLAIN`.

Current MVP flags include:

- `full_scan_detected`
- `no_index_used`
- `filesort_detected`

## Public API Surface

Minimal by design:

- service provider: `SqlInspectorServiceProvider`
- HTTP middleware alias: `sql-inspector`
- Artisan command: `profile:report`
- publishable config
- publishable migration for DB storage

No facade is included.

## Testing

The test suite covers the core MVP behaviors:

- query capture
- normalized grouping
- slow query threshold behavior
- MySQL explain analysis
- storage drivers
- Artisan reporting
- HTTP and CLI profiling metadata

Run locally:

```bash
composer test
```

## Limitations

This is an intentional MVP. Current non-goals:

- no UI
- no Telescope integration
- no PostgreSQL-specific `EXPLAIN` logic
- no distributed tracing
- no attempt to classify every repeated query as a definite bug

## License

MIT

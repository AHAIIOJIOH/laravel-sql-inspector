<?php

return [
    'enabled' => env('SQL_INSPECTOR_ENABLED', true),
    'capture_http' => env('SQL_INSPECTOR_CAPTURE_HTTP', true),
    'capture_cli' => env('SQL_INSPECTOR_CAPTURE_CLI', true),
    'slow_query_threshold_ms' => (float) env('SQL_INSPECTOR_SLOW_QUERY_THRESHOLD_MS', 100),
    'n_plus_one_repeat_threshold' => (int) env('SQL_INSPECTOR_N_PLUS_ONE_REPEAT_THRESHOLD', 3),
    'repeated_query_warning_threshold' => (int) env('SQL_INSPECTOR_REPEATED_QUERY_WARNING_THRESHOLD', 5),
    'storage' => [
        'default' => env('SQL_INSPECTOR_STORAGE', 'json'),
        'json' => [
            'path' => env('SQL_INSPECTOR_JSON_PATH', storage_path('app/sql-inspector')),
        ],
        'log' => [
            'channel' => env('SQL_INSPECTOR_LOG_CHANNEL'),
        ],
        'db' => [
            'connection' => env('SQL_INSPECTOR_DB_CONNECTION'),
            'table' => env('SQL_INSPECTOR_DB_TABLE', 'sql_inspector_snapshots'),
        ],
    ],
    'explain' => [
        'mysql_only' => true,
        'only_slow_select' => true,
    ],
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project Base Path
    |--------------------------------------------------------------------------
    | Used for CLI-based operations like queue workers, custom scripts, etc.
    | Falls back to Laravel's base_path() if not set in ENV.
    */
    'project_path' => env('PROJECT_PATH', base_path()),


    /*
    |--------------------------------------------------------------------------
    | PHP Binary Path
    |--------------------------------------------------------------------------
    | Explicit PHP binary (useful if multiple PHP versions exist).
    | Example: /usr/bin/php8.2
    */
    'php_binary' => env('PHP_BINARY_PATH', PHP_BINARY),


    /*
    |--------------------------------------------------------------------------
    | Queue Worker Settings
    |--------------------------------------------------------------------------
    | Centralized queue configuration for dynamic worker spawning.
    */
    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'database'),
        'name'       => env('QUEUE_NAME', 'default'),
        'tries'      => env('QUEUE_TRIES', 3),
        'timeout'    => env('QUEUE_TIMEOUT', 300),
        'sleep'      => env('QUEUE_SLEEP', 1),
    ],


    /*
    |--------------------------------------------------------------------------
    | Log Paths
    |--------------------------------------------------------------------------
    | Custom logs used by system processes (queue, cron, etc.)
    */
    'logs' => [
        'queue_worker' => env(
            'QUEUE_WORKER_LOG',
            storage_path('logs/queue-worker.log')
        ),
    ],


    /*
    |--------------------------------------------------------------------------
    | Process Control
    |--------------------------------------------------------------------------
    | Toggle for enabling/disabling background process spawning.
    | Useful for local/dev environments.
    */
    'process' => [
        'enable_queue_worker' => env('ENABLE_QUEUE_WORKER', true),
    ],

];

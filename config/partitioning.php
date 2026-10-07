<?php

return [
    'retention' => [
        'live_days' => 30,
        'archive_days' => 365,
    ],
    'archive_suffix' => '_archive',

    'tables' => [
        'visiting_arrangements' => 'created_at',
        'visitor_parkings' => 'created_at',
        'visitor_logs' => 'created_at',
    ],

    'processing' => [
        'chunk_size' => 500,
        'max_attempts' => 3,
        'sleep_between_chunks' => 0.5,
        'transaction_timeout' => 300,
        'verify_integrity' => false,
        'chunking_strategy' => 'auto',
        'bulk_threshold' => 100000,
        'date_chunk_hours' => 24,
        'date_bucket_days' => 30,
        'monthly_archive' => true,
        'keepalive_seconds' => 20,
        'retry_backoff_ms' => 500,
    ],

    'foreign_keys' => [
        'strategy' => 'dependency_order',
        'dependency_order' => [
            'visiting_arrangements',
            'visitor_parkings',
            'visitor_logs',
        ],
    ],

    'partitions' => [
        'partition_column' => 'created_date',
        'future_partitions' => 2,
        'manage' => true,
    ],

    'logging' => [
        'log_errors_only' => true,
        'log_progress' => false,
        'log_statistics' => false,
        'log_debug' => false,
    ],

    'safety' => [
        'max_records_per_run' => 100000,
        'require_confirmation' => false,
        'slack_notifications' => true,
        'generate_summary' => false,
        'silent' => true,
    ],
];

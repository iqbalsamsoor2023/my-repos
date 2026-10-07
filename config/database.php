<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // Dedicated connection for Laravel Pulse so its high-frequency writes
        // stay out of the main app database and its backups.
        'pulse' => [
            'driver' => 'mysql',
            'host' => env('PULSE_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('PULSE_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('PULSE_DB_DATABASE', 'mymooban_pulse'),
            'username' => env('PULSE_DB_USERNAME', env('DB_USERNAME', 'forge')),
            'password' => env('PULSE_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        // Secondary database connection
        'sgoc' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_SGOC', '127.0.0.1'),
            'port' => env('DB_PORT_SGOC', '3306'),
            'database' => env('DB_DATABASE_SGOC', 'forge'),
            'username' => env('DB_USERNAME_SGOC', 'forge'),
            'password' => env('DB_PASSWORD_SGOC'.''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],

        'sgoc1' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_SGOC', '127.0.0.1'),
            'port' => env('DB_PORT_SGOC', '3306'),
            'database' => env('DB_DATABASE_SGOC', 'mysgoc_web'),
            'username' => env('DB_USERNAME_SGOC', 'root'),
            'password' => env('DB_PASSWORD_SGOC'.''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],

        // MMBCNERP database connection
        'mmbcnerp' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_MMBCNERP', '127.0.0.1'),
            'port' => env('DB_PORT_MMBCNERP', '3306'),
            'database' => env('DB_DATABASE_MMBCNERP', 'forge'),
            'username' => env('DB_USERNAME_MMBCNERP', 'forge'),
            'password' => env('DB_PASSWORD_MMBCNERP'.''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],

        'mmb1' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_MMB1_HOST', '127.0.0.1'),
            'port' => env('DB_MMB1_PORT', '3306'),
            'database' => env('DB_MMB1_DATABASE', 'mymooban_web'),
            'username' => env('DB_MMB1_USERNAME', 'root'),
            'password' => env('DB_MMB1_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // FACILITY-MANAGEMENT database connection
        'fm' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_FM', '127.0.0.1'),
            'port' => env('DB_PORT_FM', '3306'),
            'database' => env('DB_DATABASE_FM', 'forge'),
            'username' => env('DB_USERNAME_FM', 'forge'),
            'password' => env('DB_PASSWORD_FM'.''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

        // Separate connection for Pulse's Redis ingest stream so it never
        // collides with the default connection's keys.
        'pulse' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_PULSE_DB', '3'),
        ],

        'redis' => [
            'client' => env('REDIS_CLIENT', 'phpredis'),
            'default' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_DB', 0),
            ],
        ],
    ],

];

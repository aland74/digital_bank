<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | The default connection is set dynamically by the SetBranchDatabase
    | middleware based on the authenticated user's branch. For guests and
    | system operations, it defaults to HQ.
    |
    */

    'default' => env('DB_CONNECTION') === 'sqlite' ? 'sqlite_hq' : (env('DB_CONNECTION') === 'mysql' ? 'mysql_hq' : env('DB_CONNECTION', 'mysql_hq')),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | NexusBank Distributed Database Architecture:
    |
    |   mysql_hq             → Headquarters (master copy of ALL data)
    |   mysql_erbil           → Erbil branch
    |   mysql_sulaimaniyah    → Sulaimaniyah branch
    |   mysql_duhok           → Duhok branch
    |
    | Each branch database contains only its own users' data.
    | HQ contains a superset of all branch data.
    |
    */

    'connections' => [

        // ── HQ Database (Master) ────────────────────────────────
        'mysql_hq' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HQ_HOST', '127.0.0.1'),
            'port' => env('DB_HQ_PORT', '3306'),
            'database' => env('DB_HQ_DATABASE', 'nexus_bank_hq'),
            'username' => env('DB_HQ_USERNAME', 'root'),
            'password' => env('DB_HQ_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET @@session.auto_increment_increment=10, @@session.auto_increment_offset=1",
            ]) : [],
        ],

        // ── Erbil Branch Database ───────────────────────────────
        'mysql_erbil' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_ERBIL_HOST', '127.0.0.1'),
            'port' => env('DB_ERBIL_PORT', '3306'),
            'database' => env('DB_ERBIL_DATABASE', 'nexus_bank_erbil'),
            'username' => env('DB_ERBIL_USERNAME', 'root'),
            'password' => env('DB_ERBIL_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET @@session.auto_increment_increment=10, @@session.auto_increment_offset=2",
            ]) : [],
        ],

        // ── Sulaimaniyah Branch Database ────────────────────────
        'mysql_sulaimaniyah' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_SULAIMANIYAH_HOST', '127.0.0.1'),
            'port' => env('DB_SULAIMANIYAH_PORT', '3306'),
            'database' => env('DB_SULAIMANIYAH_DATABASE', 'nexus_bank_sulaimaniyah'),
            'username' => env('DB_SULAIMANIYAH_USERNAME', 'root'),
            'password' => env('DB_SULAIMANIYAH_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET @@session.auto_increment_increment=10, @@session.auto_increment_offset=3",
            ]) : [],
        ],

        // ── Duhok Branch Database ───────────────────────────────
        'mysql_duhok' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_DUHOK_HOST', '127.0.0.1'),
            'port' => env('DB_DUHOK_PORT', '3306'),
            'database' => env('DB_DUHOK_DATABASE', 'nexus_bank_duhok'),
            'username' => env('DB_DUHOK_USERNAME', 'root'),
            'password' => env('DB_DUHOK_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET @@session.auto_increment_increment=10, @@session.auto_increment_offset=4",
            ]) : [],
        ],

        // ── SQLite (kept for reference/testing) ─────────────────
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        // ── SQLite Distributed Connections ─────────────────────
        'sqlite_hq' => [
            'driver' => 'sqlite',
            'database' => database_path('nexus_bank_hq.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'sqlite_erbil' => [
            'driver' => 'sqlite',
            'database' => database_path('nexus_bank_erbil.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'sqlite_sulaimaniyah' => [
            'driver' => 'sqlite',
            'database' => database_path('nexus_bank_sulaimaniyah.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'sqlite_duhok' => [
            'driver' => 'sqlite',
            'database' => database_path('nexus_bank_duhok.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];

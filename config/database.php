<?php

use Illuminate\Support\Str;

/*
 * PDO SSL-CA option key. PHP 8.4+ deprecates PDO::MYSQL_ATTR_SSL_CA in
 * favour of Pdo\Mysql::ATTR_SSL_CA; resolve whichever exists so the
 * config stays warning-free on PHP 8.2 - 8.5.
 */
$mysqlSslCaAttr = defined('Pdo\Mysql::ATTR_SSL_CA')
    ? constant('Pdo\Mysql::ATTR_SSL_CA')
    : PDO::MYSQL_ATTR_SSL_CA;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Navdhya uses a hybrid tenancy model (doc 02). The `central` connection
    | holds identity, authorization, token-platform and platform-registry
    | tables and is ALSO the Laravel default connection. Tenant business data
    | lives on `tenant_shared` (row-level tenants) or a dynamically
    | registered `tenant_ded_<tenantId>` connection (promoted tenants).
    |
    */

    'default' => env('DB_CONNECTION', 'central'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Connection naming is normative (doc 02 §2.3):
    |   central          -> central tables; the default Laravel connection
    |   tenant_shared    -> shared tenant data database (all `shared` tenants)
    |   tenant_ded_<id>  -> registered at runtime by the connection resolver
    |
    | Locally these default to SQLite files (doc 14 §14 permits database
    | drivers in dev); production points both at MySQL 8 via env overrides.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'central' => [
            'driver' => env('CENTRAL_DB_DRIVER', 'sqlite'),
            'url' => env('CENTRAL_DB_URL'),
            'database' => env('CENTRAL_DB_DATABASE', database_path('central.sqlite')),
            'host' => env('CENTRAL_DB_HOST', '127.0.0.1'),
            'port' => env('CENTRAL_DB_PORT', '3306'),
            'username' => env('CENTRAL_DB_USERNAME', 'root'),
            'password' => env('CENTRAL_DB_PASSWORD', ''),
            'unix_socket' => env('CENTRAL_DB_SOCKET', ''),
            'charset' => env('CENTRAL_DB_CHARSET', 'utf8mb4'),
            'collation' => env('CENTRAL_DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('CENTRAL_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                $mysqlSslCaAttr => env('CENTRAL_MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'tenant_shared' => [
            'driver' => env('TENANT_DB_DRIVER', 'sqlite'),
            'url' => env('TENANT_DB_URL'),
            'database' => env('TENANT_DB_DATABASE', database_path('tenant_shared.sqlite')),
            'host' => env('TENANT_DB_HOST', '127.0.0.1'),
            'port' => env('TENANT_DB_PORT', '3306'),
            'username' => env('TENANT_DB_USERNAME', 'root'),
            'password' => env('TENANT_DB_PASSWORD', ''),
            'unix_socket' => env('TENANT_DB_SOCKET', ''),
            'charset' => env('TENANT_DB_CHARSET', 'utf8mb4'),
            'collation' => env('TENANT_DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'foreign_key_constraints' => env('TENANT_DB_FOREIGN_KEYS', true),
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                $mysqlSslCaAttr => env('TENANT_MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                $mysqlSslCaAttr => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                $mysqlSslCaAttr => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
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
            'sslmode' => 'prefer',
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
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
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
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];

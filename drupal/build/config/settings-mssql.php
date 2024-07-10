<?php
$vars = array('DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_PORT', 'DB_TABLE_PREFIX');


foreach ($vars as $var) {
    if (!isset($_ENV[$var]) && getenv($var)) {
        $_ENV[$var] = getenv($var);
        continue;
    }
    // log error
    if (!isset($_ENV[$var])) {
        echo "Error: Environment variable $var not set";
    }
}

$username = mb_convert_encoding($_ENV['DB_USERNAME'], "UTF-8");
$password = mb_convert_encoding($_ENV['DB_PASSWORD'], "UTF-8");
$host = mb_convert_encoding($_ENV['DB_HOST'], "UTF-8");
$database = mb_convert_encoding($_ENV['DB_DATABASE'], "UTF-8");
$port = mb_convert_encoding($_ENV['DB_PORT'], "UTF-8");
$prefix = mb_convert_encoding($_ENV['DB_TABLE_PREFIX'], "UTF-8");

$databases['default']['default'] = array (
  'database' => $database,
  'username' => $username,
  'password' => $password,
  'prefix' => $prefix,
  'host' => $host,
  'port' => $port,
  'schema' => 'dbo',
  'cache_schema' => 0,
  'autoload' => 'modules/contrib/sqlsrv/src/Driver/Database/sqlsrv/',
  'encrypt' => '1',
  'trust_server_certificate' => '1',
  'multi_subnet_failover' => 0,
  'driver' => 'sqlsrv',
  'namespace' => 'Drupal\\sqlsrv\\Driver\\Database\\sqlsrv',
);
$settings['config_sync_directory'] = 'sites/default/files/config_WxI8vmxYs9PqmeenmJT9W4FOaGlM4B-6Ytl0htct0-INmEDl-Q0YE9KAPzJNiJHd1m09Tsxi7A/sync';

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 *
 * This variable will be set to a random value by the installer. All one-time
 * login links will be invalidated if the value is changed. Note that if your
 * site is deployed on a cluster of web servers, you must ensure that this
 * variable has the same value on each server.
 *
 * For enhanced security, you may set this variable to the contents of a file
 * outside your document root, and vary the value across environments (like
 * production and development); you should also ensure that this file is not
 * stored with backups of your database.
 *
 * Example:
 * @code
 *   $settings['hash_salt'] = file_get_contents('/home/example/salt.txt');
 * @endcode
 */
$settings['hash_salt'] = 'O_mBsJWj88kbvJ0m5V3W6GnNYDg8rIaPPgBRJjT-UDMB4ubbe5x1ZX4ETOaRnk7c3z8NVVO3ew';

$config['system.site']['uuid'] = '3153272e-4a62-4b66-95bf-e4cb117bf38b';

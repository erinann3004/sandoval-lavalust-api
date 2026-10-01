<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * ------------------------------------------------------------------
 * LavaLust - Database Configuration
 * ------------------------------------------------------------------
 */

$database['main'] = array(
    'driver'    => 'mysql',
    'hostname'  => getenv('DB_HOST') ?: 'localhost',
    'port'      => getenv('DB_PORT') ?: '3306',
    'username'  => getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root'),
    'password'  => getenv('DB_PASSWORD') ?: '',
    'database'  => getenv('DB_NAME') ?: 'productdb',
    'charset'   => 'utf8mb4',
    'ssl_ca'    => getenv('DB_SSL_CA') ?: (
        defined('ROOT_DIR') && file_exists(ROOT_DIR . 'certs/ca.pem')
            ? ROOT_DIR . 'certs/ca.pem'
            : ''
    ),
    'dbprefix'  => '',
    'path'      => ''
);

?>
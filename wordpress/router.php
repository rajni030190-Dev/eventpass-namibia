<?php
/**
 * EventPass Namibia - Built-in PHP Server Router for WordPress
 */
$root = __DIR__;
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = $root . $path;

if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // Serve static file directly
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
require_once $root . '/index.php';

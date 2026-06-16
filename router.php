<?php
/**
 * PHP built-in server router.
 * Serves existing files directly and falls back to WordPress index.php.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = __DIR__ . $uri;

// Serve static files directly if they exist.
if ($uri !== '/' && file_exists($path) && is_file($path)) {
    return false;
}

// Otherwise let WordPress handle the request.
require __DIR__ . '/index.php';

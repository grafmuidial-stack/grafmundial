<?php
// Simple router for PHP built-in server to serve index.html at '/'
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$docroot = __DIR__;

// Serve index.html on root
if ($uri === '/' || $uri === '' || $uri === null) {
    $index = $docroot . DIRECTORY_SEPARATOR . 'index.html';
    if (is_file($index)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($index);
        return true;
    }
}

// Resolve requested file safely within docroot (case-insensitive prefix check on Windows)
$rootReal = strtolower(realpath($docroot));
$path = realpath($docroot . $uri);
$pathNorm = $path ? strtolower($path) : null;

// If the path is a directory, serve its index.html
if ($path && is_dir($path)) {
    $dirIndex = $path . DIRECTORY_SEPARATOR . 'index.html';
    if (is_file($dirIndex)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($dirIndex);
        return true;
    }
}

if ($path && is_file($path) && $pathNorm !== null && strpos($pathNorm, $rootReal) === 0) {
    // Let the built-in server serve static files
    return false;
}

// Fallback 404 in JSON (helpful for debugging)
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['detail' => 'Not Found', 'path' => $uri]);
<?php
// Router do PHP para servir o frontend e direcionar /admin para o backend
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$docroot = dirname(__DIR__) . '/frontend';

// Rotas de admin
if (preg_match('#^/admin($|/)#', $uri)) {
    require __DIR__ . '/admin/index.php';
    return;
}

// Se o arquivo solicitado existe no frontend, deixa o servidor embutido servir diretamente
$path = $docroot . $uri;
if ($uri !== '/' && file_exists($path) && is_file($path)) {
    return false; // o PHP embutido serve o arquivo estático
}

// Fallback para index.html do frontend
require $docroot . '/index.html';
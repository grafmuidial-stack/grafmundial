<?php
// Router para despachar /admin para o backend
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/admin($|/)#', $uri)) {
    require __DIR__ . '/../backend/admin/index.php';
    exit;
}

// Qualquer outra rota não deve chegar aqui; Apache serve estáticos ou index.html via .htaccess
http_response_code(404);
echo 'Not Found';
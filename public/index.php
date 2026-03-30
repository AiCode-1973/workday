<?php
/**
 * Ponto de entrada da aplicação
 */

define('ROOT', dirname(__DIR__));

// Autoloader simples
spl_autoload_register(function (string $class): void {
    $dirs = [
        ROOT . '/app/Controllers/',
        ROOT . '/app/Models/',
        ROOT . '/app/Middleware/',
        ROOT . '/app/Services/',
        ROOT . '/app/Helpers/',
        ROOT . '/config/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once ROOT . '/config/config.php';
require_once ROOT . '/config/database.php';
require_once ROOT . '/app/Router.php';
require_once ROOT . '/app/bootstrap.php';

// Detecta método HTTP real (para PUT/DELETE via JS fetch)
$method = strtoupper($_SERVER['REQUEST_METHOD']);
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Remove prefixo da URL base
$base = parse_url(APP_URL, PHP_URL_PATH) ?? '';
if ($base && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . ltrim($uri, '/');

// Serve arquivos estáticos diretamente
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/', $uri)) {
    return false;
}

$router = require ROOT . '/routes/web.php';
$router->dispatch($uri, $method);

<?php
/**
 * Configuración local de ejemplo.
 *
 * Copiar como config/local.php solo si necesitás personalizar algo sin Docker.
 * Para Docker no hace falta tocar este archivo: docker-compose.yml inyecta las
 * variables ESCUELA_DB_* automáticamente.
 */

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (
    $host === 'localhost' ||
    strpos($host, 'localhost:') === 0 ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($host, '.local') !== false
);

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_PORT', 3306);
    define('DB_NAME', 'escuela_san_martin');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    define('SITE_URL', 'http://localhost:8081');
    define('ADMIN_URL', SITE_URL . '/admin');
    define('ENVIRONMENT', 'development');
} else {
    define('DB_HOST', getenv('ESCUELA_DB_HOST') ?: 'localhost');
    define('DB_PORT', (int) (getenv('ESCUELA_DB_PORT') ?: 3306));
    define('DB_NAME', getenv('ESCUELA_DB_NAME') ?: 'escuela_san_martin');
    define('DB_USER', getenv('ESCUELA_DB_USER') ?: '');
    define('DB_PASS', getenv('ESCUELA_DB_PASS') ?: '');
    define('DB_CHARSET', getenv('ESCUELA_DB_CHARSET') ?: 'utf8mb4');
    define('SITE_URL', getenv('ESCUELA_SITE_URL') ?: 'https://tudominio.com');
    define('ADMIN_URL', SITE_URL . '/admin');
    define('ENVIRONMENT', 'production');
}

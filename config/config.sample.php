<?php
/**
 * Archivo de ejemplo de configuración.
 * Copiar a `config.php` y completar con credenciales locales.
 * No subir `config.php` al repositorio.
 */

// Base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'escuela_san_martin');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_CHARSET', 'utf8mb4');

// URL base (ajustar según tu entorno local)
define('SITE_URL', 'http://localhost/proyecto-web-escuela');
define('ADMIN_URL', SITE_URL . '/admin');

// Rutas de uploads
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Valores de ejemplo para desarrollo
define('ENVIRONMENT', 'development');
define('DEBUG', true);

// Colores institucionales (puedes mantener)
define('COLOR_VIOLETA', '#6A1B9A');
define('COLOR_GRIS', '#616161');
define('COLOR_BLANCO', '#FFFFFF');

// NOTA: Copia este archivo a config.php y edítalo con tus valores locales.


<?php
// db.php — conexión única MySQL para local (Docker) y producción.

$envFile = __DIR__ . '/.env';
$fileEnv = [];
if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || (isset($line[0]) && $line[0] === '#') || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '') {
            continue;
        }

        $hasQuotes = strlen($value) >= 2
            && (
                ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                ($value[0] === "'" && $value[strlen($value) - 1] === "'")
            );
        if ($hasQuotes) {
            $value = substr($value, 1, -1);
        }

        $fileEnv[$key] = $value;
    }
}

$env = static function (string $key, ?string $default = null) use ($fileEnv): ?string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }
    if (isset($fileEnv[$key]) && $fileEnv[$key] !== '') {
        return $fileEnv[$key];
    }
    return $default;
};

$appEnv = strtolower((string) $env('APP_ENV', 'production'));
$isDevelopment = in_array($appEnv, ['local', 'dev', 'development', 'docker'], true);

if ($isDevelopment) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

$timezone = (string) $env('APP_TIMEZONE', 'America/Argentina/Buenos_Aires');
date_default_timezone_set($timezone);

$host = (string) $env('DB_HOST', 'mysql');
$port = (string) $env('DB_PORT', '3306');
$name = (string) $env('DB_NAME', 'kiosco');
$user = (string) $env('DB_USER', 'kiosco');
$pass = (string) $env('DB_PASS', 'kiosco');
$dbTimeZone = (string) $env('DB_TIMEZONE', '-03:00');

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $name
    );

    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $db->exec('SET NAMES utf8mb4');
    $db->prepare('SET time_zone = ?')->execute([$dbTimeZone]);

    require_once __DIR__ . '/lib/finanzas.php';
    app_bootstrap($db);
} catch (Throwable $e) {
    $message = $isDevelopment
        ? 'No pude conectar a la base de datos: ' . $e->getMessage()
        : 'No pude conectar a la base de datos. Verifica configuración.';
    die($message);
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $currentHost;
if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}

if (!function_exists('forzar_relogin_por_sesion')) {
    function forzar_relogin_por_sesion(string $mensaje): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
            }
            session_destroy();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['login_notice'] = $mensaje;
        header('Location: login.php');
        exit;
    }
}

$scriptActual = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptsSinControlSesion = ['login.php', 'logout.php'];

if (
    PHP_SAPI !== 'cli'
    && session_status() === PHP_SESSION_ACTIVE
    && isset($_SESSION['usuario_id'])
    && !in_array($scriptActual, $scriptsSinControlSesion, true)
) {
    $usuarioIdSesion = (int) ($_SESSION['usuario_id'] ?? 0);
    $tokenSesion = (string) ($_SESSION['usuario_token'] ?? '');

    if ($usuarioIdSesion <= 0 || $tokenSesion === '') {
        forzar_relogin_por_sesion('Tu sesión venció. Iniciá sesión nuevamente.');
    }

    $stmtSesion = $db->prepare("SELECT session_token, activo FROM usuarios WHERE id = ? LIMIT 1");
    $stmtSesion->execute([$usuarioIdSesion]);
    $usuarioSesion = $stmtSesion->fetch(PDO::FETCH_ASSOC);
    $tokenBd = (string) ($usuarioSesion['session_token'] ?? '');
    $usuarioActivo = (int) ($usuarioSesion['activo'] ?? 0);

    if (!$usuarioSesion || $usuarioActivo !== 1 || $tokenBd === '' || !hash_equals($tokenBd, $tokenSesion)) {
        forzar_relogin_por_sesion('Esta cuenta inició sesión en otro dispositivo. Volvé a ingresar.');
    }
}

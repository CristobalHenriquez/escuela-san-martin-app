<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

const SECRETARIA_SESSION_KEY = 'secretaria_authenticated';

// The password is stored as a hash. Production can override it with an environment variable.
const SECRETARIA_DEFAULT_PASSWORD_HASH = '$2y$12$USFgpRvz17bmGVmKnOVcHu46R725IRhbnqjGfdvmchUHBVT40dKy2';

function secretaria_password_hash(): string
{
    $configured = getenv('SECRETARIA_PASSWORD_HASH');
    return ($configured !== false && $configured !== '')
        ? $configured
        : SECRETARIA_DEFAULT_PASSWORD_HASH;
}

function secretaria_is_authenticated(): bool
{
    return !empty($_SESSION[SECRETARIA_SESSION_KEY]);
}

function secretaria_require_auth(): void
{
    if (!secretaria_is_authenticated()) {
        header('Location: index.php');
        exit;
    }
}

function secretaria_csrf_token(): string
{
    if (empty($_SESSION['secretaria_csrf'])) {
        $_SESSION['secretaria_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['secretaria_csrf'];
}

function secretaria_verify_csrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['secretaria_csrf'])
        && hash_equals($_SESSION['secretaria_csrf'], $token);
}

function secretaria_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function secretaria_old(string $key, string $default = ''): string
{
    return secretaria_h((string) ($_POST[$key] ?? $default));
}

function secretaria_date_display(string $date): string
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date ? $parsed->format('d/m/Y') : '';
}

function secretaria_pdf_autoload(): string
{
    $autoload = __DIR__ . '/../Kiosco/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('No se encontro la libreria PDF. Ejecute composer install dentro de Kiosco.');
    }
    return $autoload;
}

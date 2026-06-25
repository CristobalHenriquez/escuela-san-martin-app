<?php
/**
 * Script CLI / localhost para resetear la contraseña de un usuario
 * Uso CLI: php scripts/reset-password.php email nueva_contraseña
 * Uso web (solo localhost): http://localhost/Proyecto/scripts/reset-password.php?email=...&password=...
 * IMPORTANTE: Ejecutar sólo en entorno local y eliminar/mover el script después.
 */

// Seguridad: permitir solo CLI o peticiones desde localhost
if (php_sapi_name() !== 'cli') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'])) {
        http_response_code(403);
        echo "Acceso denegado. Uso local solo.\n";
        exit;
    }
}

require_once __DIR__ . '/../config/database.php';

$email = '';
$newPass = '';

if (php_sapi_name() === 'cli') {
    $email = $argv[1] ?? '';
    $newPass = $argv[2] ?? '';
} else {
    $email = $_GET['email'] ?? '';
    $newPass = $_GET['password'] ?? '';
}

if (!$email || !$newPass) {
    echo "Uso: php scripts/reset-password.php email nueva_contraseña\n";
    echo "Ejemplo: php scripts/reset-password.php admin@eeso225.edu.ar AdminEESO225!\n";
    exit(1);
}

// Crear hash
$hash = password_hash($newPass, PASSWORD_DEFAULT);

// Actualizar en la base de datos
$mysqli = obtenerConexion();
$stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE email = ?");
if (!$stmt) {
    echo "Error preparando la consulta: " . $mysqli->error . "\n";
    exit(2);
}
$stmt->bind_param('ss', $hash, $email);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Contraseña actualizada correctamente para {$email}\n";
        echo "Nuevo hash: " . substr($hash,0,60) . (strlen($hash)>60?"...":"") . "\n";
        exit(0);
    } else {
        echo "No se encontró usuario con email {$email} o la contraseña ya es igual.\n";
        exit(3);
    }
} else {
    echo "Error al ejecutar la actualización: " . $stmt->error . "\n";
    exit(4);
}

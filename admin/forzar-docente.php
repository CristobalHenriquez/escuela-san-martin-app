<?php
/**
 * Forzar creación/actualización del usuario docente desde el panel admin.
 * Úsalo una sola vez y luego elimínalo.
 */

require_once __DIR__ . '/../config/database.php';

$usuario_email = DEFAULT_DOCENTE_EMAIL;
$usuario_nombre = 'Docente';
$usuario_password = DEFAULT_DOCENTE_PASSWORD;

try {
    $pdo = conectarDB();
    echo "<pre>";
    echo "Conectado a la base de datos correctamente.\n\n";

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$usuario_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Usuario docente encontrado. ID = {$user['id']}\n";

        if (password_verify($usuario_password, $user['password'])) {
            echo "✔ La contraseña ya está configurada correctamente.\n";
        } else {
            echo "⚠ La contraseña guardada no coincide. Actualizando contraseña...\n";
            $password_hash = password_hash($usuario_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET nombreyapellido = ?, password = ? WHERE id = ?");
            $update->execute([$usuario_nombre, $password_hash, $user['id']]);
            echo "✅ Contraseña actualizada correctamente.\n";
        }
    } else {
        echo "Usuario docente no existe. Creando usuario...\n";
        $password_hash = password_hash($usuario_password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (nombreyapellido, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $insert->execute([$usuario_nombre, $usuario_email, $password_hash]);
        echo "✅ Usuario docente creado correctamente. ID = " . $pdo->lastInsertId() . "\n";
    }

    echo "\nDatos para login:\n";
    echo "Email: {$usuario_email}\n";
    echo "Contraseña: {$usuario_password}\n";
    echo "\nAbrí el admin en: /admin/login.php\n";
    echo "</pre>";
} catch (PDOException $e) {
    echo "<pre>❌ Error de conexión o de base de datos: " . $e->getMessage() . "</pre>";
    exit(1);
}

<?php
/**
 * Script para forzar la creación/actualización del usuario docente
 * Ejecuta este archivo desde el navegador una sola vez.
 */

require_once __DIR__ . '/../config/database.php';

$usuario_email = 'docente@eeso225.edu.ar';
$usuario_nombre = 'Docente';
$usuario_password = 'SanMartin2026';

try {
    $pdo = conectarDB();
    
    echo "<pre>";
    echo "Verificando usuario docente...\n\n";

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$usuario_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Usuario encontrado en la base de datos. ID = {$user['id']}\n";

        if (password_verify($usuario_password, $user['password'])) {
            echo "✔ La contraseña actual ya coincide con 'SanMartin2026'.\n";
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

    echo "\nAccedé con:\n";
    echo "Email: {$usuario_email}\n";
    echo "Contraseña: {$usuario_password}\n";
    echo "\nLuego abrí el login en tu navegador: /admin/login.php\n";
    echo "</pre>";
} catch (PDOException $e) {
    echo "<pre>❌ Error de conexión o de base de datos: " . $e->getMessage() . "</pre>";
    exit(1);
}

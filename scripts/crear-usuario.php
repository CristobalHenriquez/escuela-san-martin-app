<?php
/**
 * Script para crear un usuario administrador
 * Ejecuta este archivo desde el navegador o terminal una sola vez
 */

require_once __DIR__ . '/../config/database.php';

echo "=== CREAR USUARIOS PREDETERMINADOS ===\n\n";

// Datos de los nuevos usuarios predeterminados
$usuarios = [
    [
        'nombre' => 'Administrador',
        'email' => DEFAULT_ADMIN_EMAIL,
        'password' => DEFAULT_ADMIN_PASSWORD,
        'descripcion' => 'Administrador',
    ],
    [
        'nombre' => 'Docente',
        'email' => DEFAULT_DOCENTE_EMAIL,
        'password' => DEFAULT_DOCENTE_PASSWORD,
        'descripcion' => 'Docente',
    ],
];

try {
    $pdo = conectarDB();
    
    $usuariosCreados = [];
    $usuariosExistentes = [];

    foreach ($usuarios as $usuario) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$usuario['email']]);
    
        $password_hash = password_hash($usuario['password'], PASSWORD_DEFAULT);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare(
                "UPDATE users SET nombreyapellido = ?, password = ? WHERE id = ?"
            );
            $stmt->execute([$usuario['nombre'], $password_hash, $row['id']]);
            $usuariosExistentes[] = $usuario;
            continue;
        }
        
        // Insertar nuevo usuario
        $stmt = $pdo->prepare(
            "INSERT INTO users (nombreyapellido, email, password, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$usuario['nombre'], $usuario['email'], $password_hash]);
        $usuariosCreados[] = $usuario;
    }
    
    if (!empty($usuariosCreados)) {
        echo "✅ Usuarios creados exitosamente:\n\n";
        foreach ($usuariosCreados as $usuario) {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "Rol:    {$usuario['descripcion']}\n";
            echo "Nombre: {$usuario['nombre']}\n";
            echo "Email:  {$usuario['email']}\n";
            echo "Pass:   {$usuario['password']}\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        }
        echo "🔐 IMPORTANTE: Cambia las contraseñas después del primer login\n\n";
    }

    if (!empty($usuariosExistentes)) {
        echo "⚠️ Algunos usuarios ya existían y no se recrearon:\n\n";
        foreach ($usuariosExistentes as $usuario) {
            echo "- {$usuario['descripcion']} ({$usuario['email']})\n";
        }
        echo "\n";
    }

    echo "Ahora puedes acceder a:\n";
    echo "http://localhost:8888/Proyecto/admin/login.php\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error al crear usuarios: " . $e->getMessage() . "\n";
    exit(1);
}

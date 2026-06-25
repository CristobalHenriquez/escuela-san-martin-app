<?php
/**
 * Script para crear un usuario administrador
 * Ejecuta este archivo desde el navegador o terminal una sola vez
 */

require_once __DIR__ . '/../config/database.php';

echo "=== CREAR USUARIO ADMINISTRADOR ===\n\n";

// Datos del nuevo usuario (modifica estos valores)
$nombre = "Administrador";
$email = "admin@eeso225.edu.ar";
$password = "admin123";  // CAMBIAR después del primer login

try {
    $pdo = conectarDB();
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo "❌ El usuario con email '{$email}' ya existe.\n\n";
        echo "Si olvidaste la contraseña, usa el script reset-password.php\n";
        exit;
    }
    
    // Crear hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt = $pdo->prepare("
        INSERT INTO users (nombreyapellido, email, password, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$nombre, $email, $password_hash]);
    
    echo "✅ Usuario creado exitosamente!\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Nombre: {$nombre}\n";
    echo "Email:  {$email}\n";
    echo "Pass:   {$password}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "🔐 IMPORTANTE: Cambia la contraseña después del primer login\n\n";
    echo "Ahora puedes acceder a:\n";
    echo "http://localhost:8888/Proyecto/admin/login.php\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error al crear usuario: " . $e->getMessage() . "\n";
    exit(1);
}

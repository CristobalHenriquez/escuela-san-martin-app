<?php
/**
 * Script de verificación de tabla users - EESO 225 San Martín
 * Verifica y crea la estructura necesaria para usuarios administradores
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/conexion.php';

function verificarYCrearTablaUsers() {
    try {
        $pdo = conectarDB_PDO();
        
        if (!$pdo) {
            return "Error: No se pudo conectar a la base de datos";
        }
        
        // Verificar si la tabla users existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $tabla_existe = $stmt->rowCount() > 0;
        
        if (!$tabla_existe) {
            // Crear tabla users
            $sql = "CREATE TABLE `users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `nombreyapellido` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL UNIQUE,
                `password` varchar(255) NOT NULL,
                `activo` tinyint(1) DEFAULT 1,
                `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
                `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $pdo->exec($sql);
            $mensaje = "✅ Tabla 'users' creada exitosamente.\n";
        } else {
            $mensaje = "✅ La tabla 'users' ya existe.\n";
        }
        
        // Verificar si existe al menos un usuario administrador
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE activo = 1");
        $total_usuarios = $stmt->fetch()['total'];
        
        if ($total_usuarios == 0) {
            // Crear usuario administrador por defecto
            $email_admin = 'admin@eeso225.edu.ar';
            $password_admin = 'admin123'; // Cambiar en producción
            $nombre_admin = 'Administrador EESO 225';
            $password_hash = password_hash($password_admin, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (nombreyapellido, email, password, activo, fecha_creacion) VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$nombre_admin, $email_admin, $password_hash]);
            
            $mensaje .= "✅ Usuario administrador por defecto creado:\n";
            $mensaje .= "   Email: $email_admin\n";
            $mensaje .= "   Contraseña: $password_admin\n";
            $mensaje .= "   ⚠️  IMPORTANTE: Cambiar esta contraseña en producción\n";
        } else {
            $mensaje .= "✅ Ya existen $total_usuarios usuarios en el sistema.\n";
        }
        
        // Mostrar estructura de la tabla
        $stmt = $pdo->query("DESCRIBE users");
        $columnas = $stmt->fetchAll();
        
        $mensaje .= "\n📋 Estructura de la tabla 'users':\n";
        foreach ($columnas as $columna) {
            $mensaje .= "   - {$columna['Field']}: {$columna['Type']}\n";
        }
        
        return $mensaje;
        
    } catch (Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
}

// Solo ejecutar si se llama directamente
if (basename($_SERVER['PHP_SELF']) === 'verificar-users.php') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "🔍 VERIFICACIÓN DE TABLA USERS - EESO 225\n";
    echo "==========================================\n\n";
    echo verificarYCrearTablaUsers();
    echo "\n\n✨ Verificación completada.\n";
}
?>
<?php
/**
 * Script de actualización de tabla users - EESO 225 San Martín
 * Agrega la columna 'activo' si no existe
 */

// Configuración local para scripts
$db_config = [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'escuela_san_martin',
    'username' => 'root',
    'password' => 'root'
];

function conectarDB_Script($config) {
    try {
        // Usar socket de MAMP para conexión local
        $dsn = "mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname={$config['database']};charset=utf8mb4";
        
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, $config['username'], $config['password'], $opciones);
    } catch (PDOException $e) {
        throw new Exception("Error de conexión: " . $e->getMessage());
    }
}

function actualizarTablaUsers($config) {
    try {
        $pdo = conectarDB_Script($config);
        
        // Verificar estructura actual
        $stmt = $pdo->query("DESCRIBE users");
        $columnas = $stmt->fetchAll();
        
        $tiene_activo = false;
        $tiene_fecha_creacion = false;
        $tiene_fecha_actualizacion = false;
        
        foreach ($columnas as $columna) {
            if ($columna['Field'] === 'activo') $tiene_activo = true;
            if ($columna['Field'] === 'fecha_creacion') $tiene_fecha_creacion = true;
            if ($columna['Field'] === 'fecha_actualizacion') $tiene_fecha_actualizacion = true;
        }
        
        $mensaje = "🔄 ACTUALIZANDO TABLA USERS...\n\n";
        
        // Agregar columna 'activo' si no existe
        if (!$tiene_activo) {
            $pdo->exec("ALTER TABLE users ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER password");
            $mensaje .= "✅ Columna 'activo' agregada.\n";
        } else {
            $mensaje .= "✅ Columna 'activo' ya existe.\n";
        }
        
        // Agregar columna 'fecha_creacion' si no existe
        if (!$tiene_fecha_creacion) {
            $pdo->exec("ALTER TABLE users ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER activo");
            $mensaje .= "✅ Columna 'fecha_creacion' agregada.\n";
        } else {
            $mensaje .= "✅ Columna 'fecha_creacion' ya existe (como created_at).\n";
        }
        
        // Agregar columna 'fecha_actualizacion' si no existe
        if (!$tiene_fecha_actualizacion) {
            $pdo->exec("ALTER TABLE users ADD COLUMN fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER fecha_creacion");
            $mensaje .= "✅ Columna 'fecha_actualizacion' agregada.\n";
        } else {
            $mensaje .= "✅ Columna 'fecha_actualizacion' ya existe.\n";
        }
        
        // Actualizar usuarios existentes para que estén activos
        if (!$tiene_activo) {
            $stmt = $pdo->exec("UPDATE users SET activo = 1 WHERE activo IS NULL");
            $mensaje .= "✅ $stmt usuarios existentes marcados como activos.\n";
        }
        
        // Mostrar estructura final
        $stmt = $pdo->query("DESCRIBE users");
        $columnas = $stmt->fetchAll();
        
        $mensaje .= "\n📋 Estructura final de la tabla 'users':\n";
        foreach ($columnas as $columna) {
            $mensaje .= "   - {$columna['Field']}: {$columna['Type']}\n";
        }
        
        // Mostrar usuarios existentes
        $stmt = $pdo->query("SELECT id, nombreyapellido, email, activo FROM users");
        $usuarios = $stmt->fetchAll();
        
        $mensaje .= "\n👥 Usuarios existentes:\n";
        foreach ($usuarios as $usuario) {
            $estado = $usuario['activo'] ? 'Activo' : 'Inactivo';
            $mensaje .= "   - {$usuario['nombreyapellido']} ({$usuario['email']}) - $estado\n";
        }
        
        return $mensaje;
        
    } catch (Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
}

// Solo ejecutar si se llama directamente
if (basename($_SERVER['PHP_SELF']) === 'actualizar-users.php') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "🔧 ACTUALIZACIÓN DE TABLA USERS - EESO 225\n";
    echo "==========================================\n\n";
    echo actualizarTablaUsers($db_config);
    echo "\n\n✨ Actualización completada.\n";
}
?>
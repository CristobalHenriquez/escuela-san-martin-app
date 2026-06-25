<?php
// migrar_a_mysql.php
// Script para migrar de SQLite a MySQL

// Conexión a SQLite (origen)
try {
    $sqlite = new PDO('sqlite:' . __DIR__ . '/data.db');
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error conectando a SQLite: " . $e->getMessage());
}

// Conexión a MySQL (destino)
try {
    $host = 'localhost';
    $dbname = 'tu_nombre_de_base_de_datos';
    $username = 'tu_usuario';
    $password = 'tu_password';
    
    $mysql = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Error conectando a MySQL: " . $e->getMessage());
}

// Crear tablas en MySQL
$tablas = [
    "CREATE TABLE usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        apellido VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        telefono VARCHAR(20),
        grupo_id INT NULL,
        es_admin TINYINT(1) NOT NULL DEFAULT 0,
        super_admin TINYINT(1) NOT NULL DEFAULT 0,
        session_token VARCHAR(128) NULL,
        session_token_created_at DATETIME NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref VARCHAR(50),
        nombre VARCHAR(200) NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        costo DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE turnos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha_apertura DATETIME NOT NULL,
        fecha_cierre DATETIME NULL,
        saldo_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
        saldo_cierre DECIMAL(10,2) NULL,
        grupo_id INT NULL
    )",

    "CREATE TABLE semanas_operativas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        grupo_id INT NOT NULL,
        estado ENUM('planificada','activa','cerrada') NOT NULL DEFAULT 'planificada',
        observaciones VARCHAR(255) NULL,
        created_by INT NULL,
        closed_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_semanas_rango (fecha_inicio, fecha_fin),
        INDEX idx_semanas_estado (estado),
        INDEX idx_semanas_grupo (grupo_id),
        FOREIGN KEY (grupo_id) REFERENCES grupos(id),
        FOREIGN KEY (created_by) REFERENCES usuarios(id),
        FOREIGN KEY (closed_by) REFERENCES usuarios(id)
    )",

    "CREATE TABLE grupos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE cuentas_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        tipo ENUM('transferencia') NOT NULL DEFAULT 'transferencia',
        detalle VARCHAR(255) NULL,
        orden INT NOT NULL DEFAULT 0,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE transacciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        turno_id INT,
        fecha DATETIME NOT NULL,
        tipo VARCHAR(20) NOT NULL,
        producto_id INT,
        descripcion TEXT,
        venta_uid VARCHAR(64) NULL,
        cantidad INT NOT NULL,
        metodo_pago VARCHAR(20) NULL,
        cuenta_id INT NULL,
        importe DECIMAL(10,2) NOT NULL,
        usuario_id INT,
        FOREIGN KEY (turno_id) REFERENCES turnos(id),
        FOREIGN KEY (producto_id) REFERENCES productos(id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (cuenta_id) REFERENCES cuentas_pago(id)
    )",
    
    "CREATE TABLE capital_liquido (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NOT NULL,
        tipo ENUM('ingreso', 'egreso') NOT NULL,
        efectivo DECIMAL(10,2) NOT NULL DEFAULT 0,
        transferencia DECIMAL(10,2) NOT NULL DEFAULT 0,
        descripcion TEXT,
        usuario_id INT,
        cuenta_id INT NULL,
        comprobante_path VARCHAR(255) NULL,
        comprobante_tipo ENUM('webp', 'pdf') NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (cuenta_id) REFERENCES cuentas_pago(id)
    )",
    
    "CREATE TABLE compras_mercaderia (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        metodo_pago VARCHAR(20) NOT NULL,
        descripcion TEXT,
        usuario_id INT,
        cuenta_id INT NULL,
        comprobante_path VARCHAR(255) NULL,
        comprobante_tipo ENUM('webp', 'pdf') NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (cuenta_id) REFERENCES cuentas_pago(id)
    )",
    
    "CREATE TABLE balance_diario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE UNIQUE NOT NULL,
        capital_inicial_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0,
        capital_inicial_transferencia DECIMAL(10,2) NOT NULL DEFAULT 0,
        ventas_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0,
        ventas_transferencia DECIMAL(10,2) NOT NULL DEFAULT 0,
        compras_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0,
        compras_transferencia DECIMAL(10,2) NOT NULL DEFAULT 0,
        capital_final_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0,
        capital_final_transferencia DECIMAL(10,2) NOT NULL DEFAULT 0,
        capital_productos DECIMAL(10,2) NOT NULL DEFAULT 0
    )"
];

echo "<h2>Creando tablas en MySQL...</h2>";

foreach ($tablas as $sql) {
    try {
        $mysql->exec($sql);
        echo "✓ Tabla creada correctamente<br>";
    } catch (Exception $e) {
        echo "⚠ Error creando tabla: " . $e->getMessage() . "<br>";
    }
}

// Migrar datos
echo "<h2>Migrando datos...</h2>";

// Crear grupos y cuentas por defecto
try {
    foreach (['Grupo 1', 'Grupo 2', 'Grupo 3', 'Grupo 4'] as $grupoNombre) {
        $stmt = $mysql->prepare("INSERT IGNORE INTO grupos (nombre, activo) VALUES (?, 1)");
        $stmt->execute([$grupoNombre]);
    }

    $cuentas = [
        ['MP Grupo 1', 'Cuenta Mercado Pago Grupo 1', 1],
        ['MP Grupo 2', 'Cuenta Mercado Pago Grupo 2', 2],
        ['MP Grupo 3', 'Cuenta Mercado Pago Grupo 3', 3],
        ['MP Grupo 4', 'Cuenta Mercado Pago Grupo 4', 4],
    ];
    foreach ($cuentas as $cuenta) {
        $stmt = $mysql->prepare("INSERT IGNORE INTO cuentas_pago (nombre, tipo, detalle, orden, activo) VALUES (?, 'transferencia', ?, ?, 1)");
        $stmt->execute($cuenta);
    }
    echo "✓ Grupos y cuentas de transferencia creados<br>";
} catch (Exception $e) {
    echo "⚠ Error creando grupos/cuentas: " . $e->getMessage() . "<br>";
}

// Usuarios
try {
    $usuarios = $sqlite->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($usuarios as $usuario) {
        $stmt = $mysql->prepare("INSERT INTO usuarios (nombre, apellido, email, password, telefono, grupo_id, es_admin, super_admin, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $usuario['nombre'],
            $usuario['apellido'],
            $usuario['email'],
            $usuario['password'],
            $usuario['telefono'] ?? '',
            isset($usuario['grupo_id']) && $usuario['grupo_id'] !== '' ? (int) $usuario['grupo_id'] : null,
            (int)($usuario['es_admin'] ?? 0),
            (int)($usuario['super_admin'] ?? 0),
            (int)($usuario['activo'] ?? 1),
            $usuario['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
    echo "✓ " . count($usuarios) . " usuarios migrados<br>";
} catch (Exception $e) {
    echo "⚠ Error migrando usuarios: " . $e->getMessage() . "<br>";
}

// Productos
try {
    $productos = $sqlite->query("SELECT * FROM productos")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productos as $producto) {
        $stmt = $mysql->prepare("INSERT INTO productos (ref, nombre, precio, costo, stock, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$producto['ref'], $producto['nombre'], $producto['precio'], $producto['costo'], $producto['stock'], $producto['created_at']]);
    }
    echo "✓ " . count($productos) . " productos migrados<br>";
} catch (Exception $e) {
    echo "⚠ Error migrando productos: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Migración completada!</h2>";
echo "<p>Migración completada. Usa db.php para conexión única MySQL.</p>";
?> 

<?php
// instalar_hostinger.php
// Instalador MySQL unificado (usa la misma configuración de db.php para local y producción).

require 'db.php';

function ejecutarArchivoSql(PDO $db, string $ruta): void
{
    if (!is_file($ruta)) {
        throw new RuntimeException("No encontré el archivo SQL: $ruta");
    }

    $contenido = file_get_contents($ruta);
    if ($contenido === false) {
        throw new RuntimeException("No pude leer el archivo SQL: $ruta");
    }

    $sentencias = preg_split('/;\s*(\r?\n|$)/', $contenido);
    foreach ($sentencias as $sql) {
        $sql = trim($sql);
        if ($sql === '' || strpos($sql, '--') === 0) {
            continue;
        }
        $db->exec($sql);
    }
}

try {
    echo "<h2>Instalando POS San Martín 5C en MySQL</h2>";

    $db->beginTransaction();
    ejecutarArchivoSql($db, __DIR__ . '/docker/mysql/init/01_schema.sql');
    ejecutarArchivoSql($db, __DIR__ . '/docker/mysql/init/02_seed.sql');
    $db->commit();

    echo "<h3>Instalacion completada</h3>";
    echo "<p>Credenciales iniciales:</p>";
    echo "<ul>";
    echo "<li>Email: <b>admin@escuela.com</b></li>";
    echo "<li>Contrasena: <b>admin123</b></li>";
    echo "</ul>";
    echo "<p>Recomendado: cambia la contrasena del admin despues del primer login.</p>";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<h2>Error durante la instalacion</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
}

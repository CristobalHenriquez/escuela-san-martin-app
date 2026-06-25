<?php
// limpiar_datos.php
// Script para dejar la base de datos en cero (solo ventas, reportes y caja), manteniendo productos y usuarios

require 'db.php';

try {
    // Desactivar claves foráneas temporalmente
    $db->exec('SET FOREIGN_KEY_CHECKS = 0;');

    // Vaciar tablas principales de movimientos y reportes
    $db->exec('DELETE FROM transacciones;');
    $db->exec('DELETE FROM turnos;');
    $db->exec('DELETE FROM capital_liquido;');
    $db->exec('DELETE FROM compras_mercaderia;');
    $db->exec('DELETE FROM balance_diario;');

    // Resetear autoincrement (opcional)
    $db->exec('ALTER TABLE transacciones AUTO_INCREMENT = 1;');
    $db->exec('ALTER TABLE turnos AUTO_INCREMENT = 1;');
    $db->exec('ALTER TABLE capital_liquido AUTO_INCREMENT = 1;');
    $db->exec('ALTER TABLE compras_mercaderia AUTO_INCREMENT = 1;');
    $db->exec('ALTER TABLE balance_diario AUTO_INCREMENT = 1;');

    // Reactivar claves foráneas
    $db->exec('SET FOREIGN_KEY_CHECKS = 1;');

    echo "<h2>✔ Base de datos limpiada correctamente.<br>La estructura y los productos/usuarios se mantienen.<br>Puedes cerrar esta ventana.</h2>";
} catch (Exception $e) {
    echo "<h2>Error al limpiar la base de datos: ".$e->getMessage()."</h2>";
} 
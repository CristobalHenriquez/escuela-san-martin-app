<?php
// Zona horaria Argentina
// ¡IMPORTANTE! Esto asegura que la fecha sea la correcta para el balance diario
// y evita problemas si el servidor está en UTC u otra zona.
date_default_timezone_set('America/Argentina/Buenos_Aires');

require 'db.php';

try {
    $fechaParam = isset($_GET['fecha']) ? trim((string) $_GET['fecha']) : '';
    $hoy = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaParam) ? $fechaParam : date('Y-m-d');
    
    // Verificar si ya existe un balance para hoy
    $stmt = $db->prepare("SELECT id FROM balance_diario WHERE fecha = ?");
    $stmt->execute([$hoy]);
    $balance_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $resumen = calcular_resumen_balance_dia($db, $hoy);
    
    if ($balance_existente) {
        // Actualizar balance existente
        $stmt = $db->prepare("UPDATE balance_diario SET 
            capital_inicial_efectivo=?, capital_inicial_transferencia=?,
            ventas_efectivo=?, ventas_transferencia=?, compras_efectivo=?, compras_transferencia=?,
            capital_final_efectivo=?, capital_final_transferencia=?, capital_productos=?
            WHERE fecha = ?");
        $stmt->execute([
            $resumen['capital_inicial_efectivo'],
            $resumen['capital_inicial_transferencia'],
            $resumen['ventas_efectivo'],
            $resumen['ventas_transferencia'],
            $resumen['compras_efectivo'],
            $resumen['compras_transferencia'],
            $resumen['capital_final_efectivo'],
            $resumen['capital_final_transferencia'],
            $resumen['capital_productos'],
            $hoy
        ]);
        $msg = 'Balance del ' . date('d/m/Y', strtotime($hoy)) . ' actualizado correctamente';
    } else {
        // Insertar el balance
        $stmt = $db->prepare("INSERT INTO balance_diario (
            fecha, capital_inicial_efectivo, capital_inicial_transferencia,
            ventas_efectivo, ventas_transferencia, compras_efectivo, compras_transferencia,
            capital_final_efectivo, capital_final_transferencia, capital_productos
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $hoy,
            $resumen['capital_inicial_efectivo'],
            $resumen['capital_inicial_transferencia'],
            $resumen['ventas_efectivo'],
            $resumen['ventas_transferencia'],
            $resumen['compras_efectivo'],
            $resumen['compras_transferencia'],
            $resumen['capital_final_efectivo'],
            $resumen['capital_final_transferencia'],
            $resumen['capital_productos']
        ]);
        $msg = 'Balance del ' . date('d/m/Y', strtotime($hoy)) . ' generado correctamente';
    }
    
    header('Location: capital.php?msg=' . urlencode($msg) . '&type=success');
    exit;
    
} catch (Exception $e) {
    header('Location: capital.php?msg=' . urlencode($e->getMessage()) . '&type=danger');
    exit;
}
?> 

<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

function redir(string $msg, string $type = 'success'): void
{
    header('Location: capital.php?msg=' . urlencode($msg) . '&type=' . $type);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redir('Acceso invalido', 'danger');
}

$accion = $_POST['accion'] ?? '';
$monto = isset($_POST['monto']) ? (float) $_POST['monto'] : 0;
$metodo = strtolower(trim($_POST['metodo_pago'] ?? ''));
$descripcion = trim($_POST['descripcion'] ?? '');
$usuario_id = (int) $_SESSION['usuario_id'];
$cuentaId = isset($_POST['cuenta_id']) ? (int) $_POST['cuenta_id'] : 0;
$fecha = date('Y-m-d');
$ahora = date('Y-m-d H:i:s');

if ($monto <= 0 || !in_array($metodo, ['efectivo', 'transferencia'], true) || $descripcion === '') {
    redir('Datos invalidos', 'danger');
}

if ($metodo === 'transferencia') {
    if ($cuentaId <= 0 || !is_cuenta_transferencia_valida($db, $cuentaId)) {
        redir('Selecciona una cuenta de transferencia valida.', 'danger');
    }
} else {
    $cuentaId = 0;
}

if (in_array($accion, ['egreso', 'compra'], true)) {
    if ($metodo === 'efectivo') {
        $disponible = get_capital_efectivo_disponible($db);
        if ($monto > $disponible) {
            redir('No hay efectivo suficiente para registrar esta salida.', 'danger');
        }
    } elseif ($metodo === 'transferencia') {
        $saldoCuenta = get_saldo_transferencia_por_cuenta($db, $cuentaId);
        if ($monto > $saldoCuenta) {
            redir('No hay saldo suficiente en la cuenta de transferencia seleccionada.', 'danger');
        }
    }
}

$comprobante = null;
$comprobanteErr = null;
if (in_array($accion, ['egreso', 'compra'], true)) {
    $comprobante = guardar_comprobante($_FILES['comprobante'] ?? [], $comprobanteErr);
    if ($comprobante === null) {
        redir($comprobanteErr ?? 'Debes adjuntar un comprobante valido para esta operacion.', 'danger');
    }
} elseif (($accion === 'ingreso') && isset($_FILES['comprobante'])) {
    $comprobante = guardar_comprobante($_FILES['comprobante'], $comprobanteErr);
    if ($comprobanteErr !== null) {
        redir($comprobanteErr, 'danger');
    }
}

try {
    $db->beginTransaction();

    if ($accion === 'ingreso' || $accion === 'egreso') {
        $efectivo = $metodo === 'efectivo' ? $monto : 0;
        $transferencia = $metodo === 'transferencia' ? $monto : 0;
        $stmt = $db->prepare("INSERT INTO capital_liquido
            (fecha, tipo, efectivo, transferencia, descripcion, usuario_id, cuenta_id, comprobante_path, comprobante_tipo, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $fecha,
            $accion,
            $efectivo,
            $transferencia,
            $descripcion,
            $usuario_id,
            $cuentaId > 0 ? $cuentaId : null,
            $comprobante['path'] ?? null,
            $comprobante['tipo'] ?? null,
            $ahora
        ]);

        $db->commit();
        redir(ucfirst($accion) . ' registrado correctamente');
    }

    if ($accion === 'compra') {
        $stmtCompra = $db->prepare("INSERT INTO compras_mercaderia
            (fecha, monto, metodo_pago, descripcion, usuario_id, cuenta_id, comprobante_path, comprobante_tipo, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtCompra->execute([
            $fecha,
            $monto,
            $metodo,
            $descripcion,
            $usuario_id,
            $cuentaId > 0 ? $cuentaId : null,
            $comprobante['path'] ?? null,
            $comprobante['tipo'] ?? null,
            $ahora
        ]);

        $efectivo = $metodo === 'efectivo' ? $monto : 0;
        $transferencia = $metodo === 'transferencia' ? $monto : 0;

        $stmtEgreso = $db->prepare("INSERT INTO capital_liquido
            (fecha, tipo, efectivo, transferencia, descripcion, usuario_id, cuenta_id, comprobante_path, comprobante_tipo, created_at)
            VALUES (?, 'egreso', ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtEgreso->execute([
            $fecha,
            $efectivo,
            $transferencia,
            'Compra: ' . $descripcion,
            $usuario_id,
            $cuentaId > 0 ? $cuentaId : null,
            $comprobante['path'] ?? null,
            $comprobante['tipo'] ?? null,
            $ahora
        ]);

        $db->commit();
        header('Location: capital.php?msg=' . urlencode('Compra registrada. Recuerda actualizar el inventario en Stock.') . '&type=success&goto=stock');
        exit;
    }

    $db->rollBack();
    redir('Accion no reconocida', 'danger');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    redir('Error: ' . $e->getMessage(), 'danger');
}

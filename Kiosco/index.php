<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

if (!isset($_SESSION['usuario_super_admin']) && isset($_SESSION['usuario_id'])) {
  $stmtRol = $db->prepare("SELECT es_admin, super_admin FROM usuarios WHERE id = ? LIMIT 1");
  $stmtRol->execute([(int) $_SESSION['usuario_id']]);
  $rol = $stmtRol->fetch(PDO::FETCH_ASSOC) ?: [];
  $_SESSION['usuario_admin'] = (int) ($rol['es_admin'] ?? 0);
  $_SESSION['usuario_super_admin'] = (int) ($rol['super_admin'] ?? 0);
}

$mensaje = '';
$error_stock = false;
$capitalEfectivoDisponible = get_capital_efectivo_disponible($db);
$gruposActivos = get_grupos_activos($db);
$cuentasTransferencia = get_cuentas_transferencia($db);
$isAdmin = !empty($_SESSION['usuario_admin']) && (int) $_SESSION['usuario_admin'] === 1;
$isSuperAdmin = !empty($_SESSION['usuario_super_admin']) && (int) $_SESSION['usuario_super_admin'] === 1;
$usuarioGrupoId = isset($_SESSION['usuario_grupo_id']) ? (int) $_SESSION['usuario_grupo_id'] : 0;
$usuarioGrupoNombre = isset($_SESSION['usuario_grupo_nombre']) ? (string) $_SESSION['usuario_grupo_nombre'] : '';
$usuarioGrupoActivo = false;
$usuarioNombre = trim((string) ($_SESSION['usuario_nombre'] ?? ''));
$usuarioApellido = trim((string) ($_SESSION['usuario_apellido'] ?? ''));
$usuarioEmail = trim((string) ($_SESSION['usuario_email'] ?? ''));
$usuarioDisplay = trim($usuarioNombre . ' ' . $usuarioApellido);
if ($usuarioDisplay === '') {
  $usuarioDisplay = $usuarioEmail !== '' ? $usuarioEmail : 'Usuario';
}
$cuentasTransferenciaMap = [];
foreach ($cuentasTransferencia as $cuenta) {
  $cuentasTransferenciaMap[(int) $cuenta['id']] = $cuenta['nombre'];
}
foreach ($gruposActivos as $grupo) {
  if ($usuarioGrupoId > 0 && (int) $grupo['id'] === $usuarioGrupoId) {
    $usuarioGrupoActivo = true;
    if ($usuarioGrupoNombre === '') {
      $usuarioGrupoNombre = (string) $grupo['nombre'];
    }
    break;
  }
}
$fechaHoy = date('Y-m-d');
$semanaOperativaActual = get_semana_operativa_actual($db, $fechaHoy);
$semanaActualGrupoId = $semanaOperativaActual ? (int) ($semanaOperativaActual['grupo_id'] ?? 0) : 0;
$semanaActualGrupoNombre = $semanaOperativaActual ? (string) ($semanaOperativaActual['grupo_nombre'] ?? '') : '';
$semanaActualEstado = $semanaOperativaActual ? (string) ($semanaOperativaActual['estado'] ?? '') : '';
$semanaActualRango = $semanaOperativaActual
  ? date('d/m', strtotime($semanaOperativaActual['fecha_inicio'])) . ' al ' . date('d/m/Y', strtotime($semanaOperativaActual['fecha_fin']))
  : '';
$grupoBloqueadoPorSemana = !$isSuperAdmin && $semanaActualGrupoId > 0;
$grupoBloqueadoPorUsuario = !$isSuperAdmin && $usuarioGrupoId > 0 && $usuarioGrupoActivo;
$puedeAbrirTurno = $isSuperAdmin || $semanaActualGrupoId > 0;

if (isset($_SESSION['flash_msg'])) {
  $mensaje = (string) $_SESSION['flash_msg'];
  $error_stock = (bool) ($_SESSION['flash_error'] ?? false);
  unset($_SESSION['flash_msg'], $_SESSION['flash_error']);
}

// 0) Detectar turno abierto
$turno = $db->query("SELECT t.*, g.nombre AS grupo_nombre
FROM turnos t
LEFT JOIN grupos g ON g.id = t.grupo_id
WHERE t.fecha_cierre IS NULL
ORDER BY t.id DESC
LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// 1) Abrir turno
if (isset($_POST['abrir_turno'])) {
  $saldo0 = isset($_POST['saldo_inicial']) ? (float) $_POST['saldo_inicial'] : 0;
  $grupoId = isset($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : 0;

  if (!$isSuperAdmin) {
    if ($semanaActualGrupoId <= 0) {
      $_SESSION['flash_msg'] = 'No hay una semana operativa activa para hoy. Pide al Super Admin asignar el grupo de la semana.';
      $_SESSION['flash_error'] = true;
      header("Location: index.php");
      exit;
    }
    if ($usuarioGrupoId > 0 && $usuarioGrupoId !== $semanaActualGrupoId) {
      $_SESSION['flash_msg'] = 'Tu usuario pertenece a ' . ($usuarioGrupoNombre ?: 'otro grupo') . ' y esta semana está asignada a ' . $semanaActualGrupoNombre . '.';
      $_SESSION['flash_error'] = true;
      header("Location: index.php");
      exit;
    }
    $grupoId = $semanaActualGrupoId;
  } elseif ($grupoId <= 0 && $semanaActualGrupoId > 0) {
    $grupoId = $semanaActualGrupoId;
  }

  if ($usuarioGrupoId > 0 && !$usuarioGrupoActivo) {
    $_SESSION['flash_msg'] = 'Tu usuario tiene un grupo asignado, pero ese grupo no esta activo. Pide a un administrador revisarlo.';
    $_SESSION['flash_error'] = true;
    header("Location: index.php");
    exit;
  }

  if ($saldo0 < 0) {
    $_SESSION['flash_msg'] = 'El saldo inicial no puede ser negativo.';
    $_SESSION['flash_error'] = true;
    header("Location: index.php");
    exit;
  }

  $grupoValido = false;
  foreach ($gruposActivos as $grupo) {
    if ((int) $grupo['id'] === $grupoId) {
      $grupoValido = true;
      break;
    }
  }
  if (!$grupoValido) {
    $_SESSION['flash_msg'] = 'Debes seleccionar un grupo activo para iniciar turno.';
    $_SESSION['flash_error'] = true;
    header("Location: index.php");
    exit;
  }

  if ($saldo0 > $capitalEfectivoDisponible) {
    $_SESSION['flash_msg'] = 'No hay efectivo suficiente en capital para iniciar con ese saldo.';
    $_SESSION['flash_error'] = true;
    header("Location: index.php");
    exit;
  }

  try {
    $db->beginTransaction();
    $stmtTurno = $db->prepare("INSERT INTO turnos (fecha_apertura, saldo_inicial, grupo_id) VALUES (NOW(), ?, ?)");
    $stmtTurno->execute([$saldo0, $grupoId]);
    $turnoId = (int) $db->lastInsertId();

    if ($saldo0 > 0) {
      $grupoNombre = '';
      foreach ($gruposActivos as $grupo) {
        if ((int) $grupo['id'] === $grupoId) {
          $grupoNombre = $grupo['nombre'];
          break;
        }
      }

      $stmtCapital = $db->prepare("INSERT INTO capital_liquido (fecha, tipo, efectivo, transferencia, descripcion, usuario_id, created_at) VALUES (?, 'egreso', ?, 0, ?, ?, ?)");
      $stmtCapital->execute([
        date('Y-m-d'),
        $saldo0,
        'Apertura de turno #' . $turnoId . ($grupoNombre !== '' ? ' - ' . $grupoNombre : ''),
        $_SESSION['usuario_id'],
        date('Y-m-d H:i:s')
      ]);
    }

    $db->commit();
  } catch (Throwable $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }
    $_SESSION['flash_msg'] = 'No se pudo abrir el turno: ' . $e->getMessage();
    $_SESSION['flash_error'] = true;
    header("Location: index.php");
    exit;
  }

  header("Location: index.php"); exit;
}

// 2) Cerrar turno
if (isset($_POST['cerrar_turno']) && $turno && isset($turno['id'])) {
  $saldoCierre = floatval($_POST['saldo_cierre'] ?: 0);
  try {
    $db->beginTransaction();
    $db->prepare("UPDATE turnos SET fecha_cierre = NOW(), saldo_cierre = ? WHERE id = ?")->execute([$saldoCierre, $turno['id']]);

    // --- Traspaso automático a capital_liquido ---
    $fechaHoy = date('Y-m-d');
    $ahora = date('Y-m-d H:i:s');
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $grupoTexto = $turno['grupo_nombre'] ?? '';

    // 1) Al cerrar turno, vuelve a capital el efectivo contado en caja.
    if ($saldoCierre > 0) {
      $db->prepare("INSERT INTO capital_liquido (fecha, tipo, efectivo, transferencia, descripcion, usuario_id, created_at) VALUES (?, 'ingreso', ?, 0, ?, ?, ?)")
        ->execute([
          $fechaHoy,
          $saldoCierre,
          'Cierre de turno #' . $turno['id'] . ($grupoTexto !== '' ? ' - ' . $grupoTexto : ''),
          $usuario_id,
          $ahora
        ]);
    }

    // 2) Ventas por transferencia: sumar a capital por cuenta (4 cuentas MP).
    $ventasTransfer = $db->prepare("SELECT cuenta_id, SUM(importe) AS total
    FROM transacciones
    WHERE turno_id = ? AND tipo = 'Venta' AND metodo_pago = 'Transferencia'
    GROUP BY cuenta_id");
    $ventasTransfer->execute([$turno['id']]);
    foreach ($ventasTransfer->fetchAll(PDO::FETCH_ASSOC) as $vt) {
      $cuentaId = isset($vt['cuenta_id']) ? (int) $vt['cuenta_id'] : null;
      $cuentaNombre = $cuentaId && isset($cuentasTransferenciaMap[$cuentaId]) ? $cuentasTransferenciaMap[$cuentaId] : 'Sin cuenta';
      $db->prepare("INSERT INTO capital_liquido (fecha, tipo, efectivo, transferencia, descripcion, usuario_id, cuenta_id, created_at) VALUES (?, 'ingreso', 0, ?, ?, ?, ?, ?)")
        ->execute([
          $fechaHoy,
          (float) $vt['total'],
          'Ventas transferencia turno #' . $turno['id'] . ' - ' . $cuentaNombre,
          $usuario_id,
          $cuentaId ?: null,
          $ahora
        ]);
    }

    // 3) Compras del turno (si existieran transacciones tipo Compra).
    $comprasTurno = $db->prepare("SELECT metodo_pago, cuenta_id, SUM(importe) AS total
    FROM transacciones
    WHERE turno_id = ? AND tipo = 'Compra'
    GROUP BY metodo_pago, cuenta_id");
    $comprasTurno->execute([$turno['id']]);
    foreach ($comprasTurno->fetchAll(PDO::FETCH_ASSOC) as $c) {
      $efectivo = strtolower((string) $c['metodo_pago']) === 'efectivo' ? (float) $c['total'] : 0;
      $transferencia = strtolower((string) $c['metodo_pago']) === 'transferencia' ? (float) $c['total'] : 0;
      if ($efectivo > 0 || $transferencia > 0) {
        $cuentaId = isset($c['cuenta_id']) ? (int) $c['cuenta_id'] : null;
        $db->prepare("INSERT INTO capital_liquido (fecha, tipo, efectivo, transferencia, descripcion, usuario_id, cuenta_id, created_at) VALUES (?, 'egreso', ?, ?, ?, ?, ?, ?)")
          ->execute([
            $fechaHoy,
            $efectivo,
            $transferencia,
            'Compra asociada al turno #' . $turno['id'],
            $usuario_id,
            $cuentaId ?: null,
            $ahora
          ]);
      }
    }

    $db->commit();
  } catch (Throwable $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }
    $_SESSION['flash_msg'] = 'No se pudo cerrar el turno: ' . $e->getMessage();
    $_SESSION['flash_error'] = true;
    header("Location: index.php");
    exit;
  }

  // --- Generar balance diario automáticamente si no quedan turnos abiertos ---
  $turnos_abiertos = $db->query("SELECT COUNT(*) FROM turnos WHERE fecha_cierre IS NULL")->fetchColumn();
  if ($turnos_abiertos == 0) {
    // Llamar a generar_balance.php internamente
    include 'generar_balance.php';
    exit; // Terminar ejecución tras generar el balance
  }

  header("Location: turno_cerrado.php"); exit;
}

// 3) Registrar transacción (solo si hay turno abierto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar']) && $turno && isset($turno['id'])) {
  $productos_comprados = $_POST['productos'] ?? [];
  $tipo = ($_POST['tipo'] ?? 'Venta') === 'Venta' ? 'Venta' : 'Venta';
  $metodo = $_POST['metodo_pago'] ?? '';
  $cuentaTransferenciaId = isset($_POST['cuenta_transferencia_id']) ? (int) $_POST['cuenta_transferencia_id'] : 0;
  $cuentaTransferencia = null;
  $total_venta = 0;

  if (!in_array($metodo, ['Efectivo', 'Transferencia'], true)) {
    $mensaje = 'Metodo de pago invalido.';
    $error_stock = true;
  } else {
    if ($metodo === 'Transferencia') {
      if ($cuentaTransferenciaId <= 0 || !is_cuenta_transferencia_valida($db, $cuentaTransferenciaId)) {
        $mensaje = 'Debes seleccionar una cuenta de transferencia valida.';
        $error_stock = true;
      } else {
        $cuentaTransferencia = $cuentaTransferenciaId;
      }
    }

    if ($error_stock) {
      // Nada: se muestra el mensaje validado arriba.
    } else {
    $items = [];
    foreach ($productos_comprados as $producto_id => $cantidad) {
      $id = (int) $producto_id;
      $qty = (int) $cantidad;
      if ($id > 0 && $qty > 0) {
        $items[$id] = ($items[$id] ?? 0) + $qty;
      }
    }

    if (empty($items)) {
      $mensaje = 'Debe seleccionar al menos un producto para registrar la venta.';
      $error_stock = true;
    } else {
      try {
        $db->beginTransaction();

        $selProducto = $db->prepare("SELECT precio, stock FROM productos WHERE id = ? FOR UPDATE");
        $updStock = $db->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $insTrans = $db->prepare("INSERT INTO transacciones (turno_id, fecha, tipo, producto_id, cantidad, metodo_pago, cuenta_id, importe, usuario_id, venta_uid) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $fechaOperacion = date('Y-m-d H:i:s');
        try {
          $ventaUid = 'VEN-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
        } catch (Throwable $e) {
          $ventaUid = 'VEN-' . date('YmdHis') . '-' . str_replace('.', '', (string) microtime(true));
        }

        foreach ($items as $producto_id => $cantidad) {
          $selProducto->execute([$producto_id]);
          $producto = $selProducto->fetch(PDO::FETCH_ASSOC);

          if (!$producto) {
            throw new RuntimeException('Producto no encontrado.');
          }

          $stock_actual = (int) $producto['stock'];
          if ($cantidad > $stock_actual) {
            throw new RuntimeException('No hay stock suficiente para uno o mas productos seleccionados.');
          }

          $precio = (float) $producto['precio'];
          $importe = $precio * $cantidad;
          $total_venta += $importe;

          if ($tipo === 'Venta') {
            $updStock->execute([$stock_actual - $cantidad, $producto_id]);
          }

          $insTrans->execute([
            $turno['id'],
            $fechaOperacion,
            $tipo,
            $producto_id,
            $cantidad,
            $metodo,
            $cuentaTransferencia,
            $importe,
            $_SESSION['usuario_id'],
            $ventaUid
          ]);
        }

        $db->commit();
        $mensaje = 'Venta registrada por un total de: $' . number_format($total_venta, 2, ',', '.');
      } catch (Throwable $e) {
        if ($db->inTransaction()) {
          $db->rollBack();
        }
        $error_stock = true;
        $mensaje = $e->getMessage();
      }
    }
  }
  }
}

// 3.1) Corregir método de pago de una venta (solo turno abierto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_metodo_venta'])) {
  if (!$turno || !isset($turno['id'])) {
    $_SESSION['flash_msg'] = 'No hay un turno abierto para corregir ventas.';
    $_SESSION['flash_error'] = true;
    header("Location: index.php#ventas-turno");
    exit;
  }

  $ventaUid = trim((string) ($_POST['venta_uid'] ?? ''));
  $nuevoMetodo = trim((string) ($_POST['nuevo_metodo_pago'] ?? ''));
  $nuevaCuentaId = isset($_POST['nueva_cuenta_id']) ? (int) $_POST['nueva_cuenta_id'] : 0;
  $nuevaCuenta = null;

  if ($ventaUid === '') {
    $_SESSION['flash_msg'] = 'Venta inválida para corregir.';
    $_SESSION['flash_error'] = true;
    header("Location: index.php#ventas-turno");
    exit;
  }

  if (!in_array($nuevoMetodo, ['Efectivo', 'Transferencia'], true)) {
    $_SESSION['flash_msg'] = 'Método de pago inválido.';
    $_SESSION['flash_error'] = true;
    header("Location: index.php#ventas-turno");
    exit;
  }

  if ($nuevoMetodo === 'Transferencia') {
    if ($nuevaCuentaId <= 0 || !is_cuenta_transferencia_valida($db, $nuevaCuentaId)) {
      $_SESSION['flash_msg'] = 'Debes seleccionar una cuenta de transferencia válida.';
      $_SESSION['flash_error'] = true;
      header("Location: index.php#ventas-turno");
      exit;
    }
    $nuevaCuenta = $nuevaCuentaId;
  }

  try {
    $whereSql = '';
    $whereParams = [];
    if (strpos($ventaUid, 'LEGACY-') === 0) {
      $legacyId = (int) substr($ventaUid, 7);
      if ($legacyId <= 0) {
        throw new RuntimeException('Identificador de venta inválido.');
      }
      $whereSql = "id = ? AND turno_id = ? AND tipo = 'Venta'";
      $whereParams = [$legacyId, (int) $turno['id']];
    } else {
      $whereSql = "venta_uid = ? AND turno_id = ? AND tipo = 'Venta'";
      $whereParams = [$ventaUid, (int) $turno['id']];
    }

    $stmtVenta = $db->prepare("SELECT
      COUNT(*) AS lineas,
      MIN(metodo_pago) AS metodo_min,
      MAX(metodo_pago) AS metodo_max,
      MIN(COALESCE(cuenta_id, 0)) AS cuenta_min,
      MAX(COALESCE(cuenta_id, 0)) AS cuenta_max
      FROM transacciones
      WHERE $whereSql");
    $stmtVenta->execute($whereParams);
    $ventaActual = $stmtVenta->fetch(PDO::FETCH_ASSOC);

    if (!$ventaActual || (int) ($ventaActual['lineas'] ?? 0) <= 0) {
      throw new RuntimeException('La venta seleccionada no existe en el turno activo.');
    }

    $metodoUniforme = ((string) ($ventaActual['metodo_min'] ?? '')) === ((string) ($ventaActual['metodo_max'] ?? ''));
    $cuentaUniforme = ((int) ($ventaActual['cuenta_min'] ?? 0)) === ((int) ($ventaActual['cuenta_max'] ?? 0));
    $metodoActual = $metodoUniforme ? (string) ($ventaActual['metodo_min'] ?? '') : '';
    $cuentaActual = $cuentaUniforme ? (int) ($ventaActual['cuenta_max'] ?? 0) : 0;
    $cuentaNuevaInt = $nuevaCuenta !== null ? (int) $nuevaCuenta : 0;

    if ($metodoUniforme && $cuentaUniforme && $metodoActual === $nuevoMetodo && $cuentaActual === $cuentaNuevaInt) {
      $_SESSION['flash_msg'] = 'No hubo cambios para guardar en esa venta.';
      $_SESSION['flash_error'] = false;
      header("Location: index.php#ventas-turno");
      exit;
    }

    $stmtUpdate = $db->prepare("UPDATE transacciones
      SET metodo_pago = ?, cuenta_id = ?
      WHERE $whereSql");
    $stmtUpdate->execute(array_merge([$nuevoMetodo, $nuevaCuenta], $whereParams));

    if ($stmtUpdate->rowCount() <= 0) {
      throw new RuntimeException('No se pudo actualizar la venta seleccionada.');
    }

    $_SESSION['flash_msg'] = 'Método de pago actualizado correctamente.';
    $_SESSION['flash_error'] = false;
  } catch (Throwable $e) {
    $_SESSION['flash_msg'] = 'No se pudo corregir la venta: ' . $e->getMessage();
    $_SESSION['flash_error'] = true;
  }

  header("Location: index.php#ventas-turno");
  exit;
}

// Cargar productos con stock disponible para agilizar la venta en turno.
$productos = $db->query("SELECT * FROM productos WHERE stock > 0 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Si hay turno abierto, obtener ventas del turno actual
$ventas = [];
$total_efectivo = 0;
$total_transferencia = 0;
if ($turno && isset($turno['id'])) {
    $ventas = $db->prepare("SELECT
      CASE
        WHEN t.venta_uid IS NULL OR t.venta_uid = '' THEN CONCAT('LEGACY-', t.id)
        ELSE t.venta_uid
      END AS venta_uid_clave,
      MAX(t.fecha) AS fecha,
      SUM(t.cantidad) AS cantidad_total,
      SUM(t.importe) AS importe_total,
      MIN(t.metodo_pago) AS metodo_pago,
      CASE
        WHEN MIN(COALESCE(t.cuenta_id, 0)) = MAX(COALESCE(t.cuenta_id, 0)) THEN MAX(t.cuenta_id)
        ELSE NULL
      END AS cuenta_id,
      MAX(cp.nombre) AS cuenta_nombre,
      COUNT(*) AS lineas,
      GROUP_CONCAT(CONCAT(p.nombre, ' x', t.cantidad) ORDER BY p.nombre SEPARATOR ', ') AS detalle_productos
    FROM transacciones t
    JOIN productos p ON t.producto_id = p.id
    LEFT JOIN cuentas_pago cp ON cp.id = t.cuenta_id
    WHERE t.turno_id = ? AND t.tipo = 'Venta'
    GROUP BY venta_uid_clave
    ORDER BY MAX(t.fecha) DESC, MAX(t.id) DESC");
    $ventas->execute([$turno['id']]);
    $ventas = $ventas->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ventas as $v) {
        if ($v['metodo_pago'] === 'Efectivo') {
            $total_efectivo += (float) ($v['importe_total'] ?? 0);
        } else {
            $total_transferencia += (float) ($v['importe_total'] ?? 0);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS La San Martin 5°C</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Main CSS File -->
    <link href="css/main.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body>
    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-store"></i> Aplicacion de gestion Kiosco</h1>
            <div class="header-user-line">
                <span class="header-user-badge">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars($usuarioDisplay, ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!$isSuperAdmin && $usuarioGrupoId > 0 && $usuarioGrupoActivo && $usuarioGrupoNombre !== ''): ?>
                    <small class="ms-1">(<?= htmlspecialchars($usuarioGrupoNombre, ENT_QUOTES, 'UTF-8') ?>)</small>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($isAdmin): ?>
            <a href="usuarios.php" class="btn btn-outline-primary btn-sm ms-2"><i class="fas fa-users"></i> Usuarios</a>
            <?php endif; ?>
            <?php if ($isSuperAdmin): ?>
            <a href="semanas.php" class="btn btn-outline-light btn-sm ms-2"><i class="fas fa-calendar-week"></i> Semanas</a>
            <?php endif; ?>
            <?php if(isset($_SESSION['usuario_id'])): ?>
            <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2"><i class="fas fa-sign-out-alt"></i> Cerrar
                sesión</a>
            <?php endif; ?>
        </div>
        <?php if ($mensaje): ?>
        <div class="container pt-3">
            <div class="alert alert-<?= $error_stock ? 'danger' : 'success' ?> mb-0" role="alert">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$turno): ?>
        <div class="container mt-4">
            <h2 class="mb-4 text-center"><i class="fas fa-cash-register"></i> Bienvenido a POS La San Martin 5°C</h2>
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-md-6 mb-3">
                    <form method="post" class="text-center">
                        <?php if ($semanaOperativaActual): ?>
                        <div class="alert alert-info text-start">
                            <strong>Semana operativa actual:</strong>
                            <?= htmlspecialchars($semanaActualGrupoNombre, ENT_QUOTES, 'UTF-8') ?>
                            <br>
                            <small><?= htmlspecialchars($semanaActualRango, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($semanaActualEstado, ENT_QUOTES, 'UTF-8') ?>)</small>
                        </div>
                        <?php elseif (!$isSuperAdmin): ?>
                        <div class="alert alert-warning text-start">
                            No hay semana operativa asignada para hoy. Un Super Admin debe configurarla.
                        </div>
                        <?php endif; ?>
                        <div class="mb-3 text-start">
                            <label for="grupo_id" class="form-label">Grupo a cargo</label>
                            <?php if ($grupoBloqueadoPorSemana): ?>
                            <input type="hidden" name="grupo_id" value="<?= (int) $semanaActualGrupoId ?>">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($semanaActualGrupoNombre, ENT_QUOTES, 'UTF-8') ?>" readonly>
                            <small class="form-text text-muted d-block mt-1">
                                Esta semana está asignada a este grupo.
                            </small>
                            <?php elseif ($grupoBloqueadoPorUsuario): ?>
                            <input type="hidden" name="grupo_id" value="<?= (int) $usuarioGrupoId ?>">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($usuarioGrupoNombre, ENT_QUOTES, 'UTF-8') ?>" readonly>
                            <small class="form-text text-muted d-block mt-1">Tu usuario está asociado a este grupo.</small>
                            <?php else: ?>
                            <?php if ($usuarioGrupoId > 0 && !$usuarioGrupoActivo): ?>
                            <div class="alert alert-warning py-2">
                                Tu grupo asignado no está activo. Contacta al administrador.
                            </div>
                            <?php endif; ?>
                            <select name="grupo_id" id="grupo_id" class="form-select" required>
                                <option value="">Seleccionar grupo</option>
                                <?php foreach ($gruposActivos as $grupo): ?>
                                <option value="<?= (int) $grupo['id'] ?>"
                                    <?= $semanaActualGrupoId > 0 && (int) $grupo['id'] === $semanaActualGrupoId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($grupo['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isSuperAdmin && $semanaActualGrupoId > 0): ?>
                            <small class="form-text text-muted d-block mt-1">
                                Sugerido: <?= htmlspecialchars($semanaActualGrupoNombre, ENT_QUOTES, 'UTF-8') ?> (grupo asignado esta semana).
                            </small>
                            <?php endif; ?>
                            <?php if ($isSuperAdmin): ?>
                            <small class="form-text text-muted d-block mt-1">
                                Como super admin puedes elegir cualquier grupo para este turno.
                            </small>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="saldo_inicial" class="form-label">Saldo inicial de caja</label>
                            <input type="number" name="saldo_inicial" step="0.01" class="form-control" required
                                placeholder="$5000 - ideal para efectivo inicio de caja">
                            <small class="form-text text-muted d-block mt-1">
                                Disponible en capital (efectivo):
                                <strong>$<?= number_format($capitalEfectivoDisponible, 2, ',', '.') ?></strong>
                            </small>
                        </div>
                        <button type="submit" name="abrir_turno" class="btn-turno w-100 mb-2" <?= $puedeAbrirTurno ? '' : 'disabled' ?>>
                            <i class="fas fa-play"></i> Iniciar Turno
                        </button>
                        <?php if (!$puedeAbrirTurno): ?>
                        <small class="text-danger d-block">No se puede iniciar turno hasta asignar una semana operativa.</small>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <a href="reportes.php" class="btn btn-info w-100 mb-2" style="font-size:1.2rem;">
                        <i class="fas fa-chart-bar"></i> Reportes de Turnos
                    </a>
                    <a href="stock.php" class="btn btn-warning w-100 mb-2" style="font-size:1.2rem;">
                        <i class="fas fa-warehouse"></i> Gestión de Stock
                    </a>
                    <a href="#" id="btnCapital" class="btn btn-success w-100" style="font-size:1.2rem;">
                        <i class="fas fa-chart-line"></i> Control de Capital
                    </a>
                    <div id="msgCapital" class="text-danger text-center mt-2" style="display:none;font-size:1rem;">
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="container-fluid">
            <div class="status">
                <h4><i class="fas fa-clock"></i> Turno #<?= $turno && isset($turno['id']) ? $turno['id'] : '' ?> Activo
                </h4>
                <p>Abierto:
                    <?= $turno && isset($turno['fecha_apertura']) ? date('d/m/Y H:i', strtotime($turno['fecha_apertura'])) : '' ?>
                    | Saldo inicial:
                    $<?= $turno && isset($turno['saldo_inicial']) ? number_format($turno['saldo_inicial'], 2) : '' ?>
                    <?php if (!empty($turno['grupo_nombre'])): ?>
                    | Grupo: <strong><?= htmlspecialchars($turno['grupo_nombre']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div class="total-section">
                <h3>Total: $<span class="total" id="total">0.00</span></h3>
                <div class="productos-lista" id="productos_seleccionados">
                    <p class="text-center text-muted">No hay productos seleccionados</p>
                </div>
            </div>

            <form method="post" id="ventaForm">
                <input type="hidden" name="registrar" value="1">
                <input type="hidden" name="tipo" value="Venta">

                <!-- aquí metemos los hidden inputs -->
                <div id="hiddenInputs"></div>

                <div class="row">
                    <div class="col-12">
                        <h4><i class="fas fa-shopping-cart"></i> Productos</h4>
                        <div class="cantidad-rapida-wrap mb-2">
                            <label for="cantidad_por_toque" class="cantidad-rapida-label mb-0">Cantidad por toque:</label>
                            <input
                                type="number"
                                id="cantidad_por_toque"
                                class="form-control cantidad-por-toque-input"
                                value="1"
                                min="1"
                                step="1"
                                inputmode="numeric">
                        </div>
                        <small class="text-muted d-block mb-2">Carga la cantidad necesaria y, al tocar un producto, vuelve automáticamente a 1.</small>
                        <?php if (count($productos) === 0): ?>
                        <div class="alert alert-warning py-2">No hay productos con stock disponible para vender.</div>
                        <?php endif; ?>
                        <div class="row">
                            <?php foreach($productos as $p): ?>
                            <?php 
                            $stockClass = '';
                            if ($p['stock'] <= 0) $stockClass = 'stock-bajo';
                            elseif ($p['stock'] <= 3) $stockClass = 'stock-medio';
                            ?>
                            <div class="col-6 col-md-4 col-lg-3 producto-boton">
                                <button type="button" class="btn producto <?= $stockClass ?>" data-id="<?=$p['id']?>"
                                    data-nombre="<?=$p['nombre']?>" data-precio="<?=$p['precio']?>"
                                    data-stock="<?=$p['stock']?>" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <div class="nombre"><?=$p['nombre']?></div>
                                    <div class="precio">$<?= number_format($p['precio'], 0) ?></div>
                                    <div class="stock" id="stock_<?=$p['id']?>">Stock: <?=$p['stock']?></div>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn-corregir" onclick="corregirUltimo()">
                                <i class="fas fa-undo"></i> Corregir Último
                            </button>
                            <button type="button" class="btn-corregir" onclick="limpiarTodo()">
                                <i class="fas fa-trash"></i> Limpiar Todo
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Método de pago</label>
                            <input type="hidden" name="metodo_pago" id="metodo_pago" value="">
                            <div class="metodo-pago-botones" role="group" aria-label="Seleccionar método de pago">
                                <button type="button" class="btn-metodo-pago" data-metodo="Efectivo">
                                    <i class="fas fa-money-bill-wave"></i> Efectivo
                                </button>
                                <button type="button" class="btn-metodo-pago" data-metodo="Transferencia">
                                    <i class="fas fa-mobile-alt"></i> Transferencia
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">Primero selecciona cómo pagó el cliente.</small>
                        </div>
                        <div class="mb-3" id="cuentaTransferenciaWrap" style="display:none;">
                            <label class="form-label">Cuenta de transferencia</label>
                            <input type="hidden" name="cuenta_transferencia_id" id="cuenta_transferencia_id" value="">
                            <div class="cuenta-transferencia-botones" role="group" aria-label="Seleccionar cuenta de transferencia">
                                <?php foreach ($cuentasTransferencia as $cuenta): ?>
                                <button
                                    type="button"
                                    class="btn-cuenta-transferencia"
                                    data-cuenta-id="<?= (int) $cuenta['id'] ?>">
                                    <?= htmlspecialchars($cuenta['nombre']) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted d-block mt-1">Selecciona la cuenta que recibió el pago.</small>
                        </div>
                        <button type="submit" class="btn-finalizar" id="btnFinalizar" disabled>
                            <i class="fas fa-check"></i> Finalizar Venta
                        </button>
                    </div>
                </div>
            </form>
            <!-- Listado de ventas del turno actual -->
            <div class="row mb-4" id="ventas-turno">
                <div class="col-12">
                    <div class="ventas-header">
                        <h4 class="mb-1"><i class="fas fa-list"></i> Ventas del Turno</h4>
                        <p class="ventas-tip mb-0">
                            Antes de cerrar turno, puedes corregir el método de pago de cada venta.
                        </p>
                    </div>
                    <?php if (count($ventas) === 0): ?>
                    <div class="alert alert-info">No hay ventas registradas en este turno.</div>
                    <?php else: ?>
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Detalle de venta</th>
                                        <th class="text-center">Items</th>
                                        <th class="text-end">Total venta</th>
                                        <th>Método</th>
                                        <th>Cuenta</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ventas as $v): ?>
                                    <tr>
                                        <td><?= date('H:i', strtotime($v['fecha'])) ?></td>
                                        <td><?= htmlspecialchars((string) ($v['detalle_productos'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= (int) ($v['cantidad_total'] ?? 0) ?></td>
                                        <td class="text-end">$<?= number_format((float) ($v['importe_total'] ?? 0), 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($v['metodo_pago'] === 'Efectivo'): ?>
                                            <span class="badge bg-success badge-metodo"><i class="fas fa-money-bill-wave me-1"></i>Efectivo</span>
                                            <?php else: ?>
                                            <span class="badge bg-info text-dark badge-metodo"><i class="fas fa-mobile-alt me-1"></i>Transferencia</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($v['cuenta_nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm btn-editar-venta"
                                                data-venta-uid="<?= htmlspecialchars((string) ($v['venta_uid_clave'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-metodo="<?= htmlspecialchars((string) $v['metodo_pago'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-cuenta-id="<?= (int) ($v['cuenta_id'] ?? 0) ?>"
                                                data-hora="<?= date('H:i', strtotime($v['fecha'])) ?>"
                                                data-producto="<?= htmlspecialchars((string) ($v['detalle_productos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-cantidad-total="<?= (int) ($v['cantidad_total'] ?? 0) ?>"
                                                data-importe="<?= number_format((float) ($v['importe_total'] ?? 0), 0, ',', '.') ?>">
                                                <i class="fas fa-pen"></i> Editar cobro
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ventas-mobile-list d-md-none">
                        <?php foreach($ventas as $v): ?>
                        <article class="venta-card">
                            <div class="venta-card-top">
                                <h5 class="venta-title mb-0">Venta cerrada</h5>
                                <strong class="venta-importe">$<?= number_format((float) ($v['importe_total'] ?? 0), 0, ',', '.') ?></strong>
                            </div>
                            <div class="venta-meta">
                                <span><i class="fas fa-clock me-1"></i><?= date('H:i', strtotime($v['fecha'])) ?></span>
                                <span><i class="fas fa-box me-1"></i>Items: <?= (int) ($v['cantidad_total'] ?? 0) ?></span>
                            </div>
                            <div class="venta-detalle">
                                <?= htmlspecialchars((string) ($v['detalle_productos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="venta-badges">
                                <?php if ($v['metodo_pago'] === 'Efectivo'): ?>
                                <span class="badge bg-success badge-metodo"><i class="fas fa-money-bill-wave me-1"></i>Efectivo</span>
                                <?php else: ?>
                                <span class="badge bg-info text-dark badge-metodo"><i class="fas fa-mobile-alt me-1"></i>Transferencia</span>
                                <?php endif; ?>
                                <span class="badge bg-light text-dark badge-cuenta">
                                    <i class="fas fa-wallet me-1"></i><?= htmlspecialchars($v['cuenta_nombre'] ?? 'Sin cuenta', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-2 btn-editar-venta"
                                data-venta-uid="<?= htmlspecialchars((string) ($v['venta_uid_clave'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-metodo="<?= htmlspecialchars((string) $v['metodo_pago'], ENT_QUOTES, 'UTF-8') ?>"
                                data-cuenta-id="<?= (int) ($v['cuenta_id'] ?? 0) ?>"
                                data-hora="<?= date('H:i', strtotime($v['fecha'])) ?>"
                                data-producto="<?= htmlspecialchars((string) ($v['detalle_productos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-cantidad-total="<?= (int) ($v['cantidad_total'] ?? 0) ?>"
                                data-importe="<?= number_format((float) ($v['importe_total'] ?? 0), 0, ',', '.') ?>">
                                <i class="fas fa-pen"></i> Editar cobro
                            </button>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modal fade" id="modalEditarPagoVenta" tabindex="-1" aria-labelledby="modalEditarPagoVentaLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
                    <div class="modal-content">
                        <form method="post" id="formEditarPagoVenta">
                            <input type="hidden" name="actualizar_metodo_venta" value="1">
                            <input type="hidden" name="venta_uid" id="editar_venta_uid" value="">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEditarPagoVentaLabel">
                                    <i class="fas fa-pen-to-square"></i> Corregir cobro de venta
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="venta-resumen-modal mb-3">
                                    <div><strong>Detalle:</strong> <span id="editar_producto_resumen">-</span></div>
                                    <div><strong>Hora:</strong> <span id="editar_hora_resumen">-</span></div>
                                    <div><strong>Items:</strong> <span id="editar_items_resumen">0</span></div>
                                    <div><strong>Importe:</strong> $<span id="editar_importe_resumen">0</span></div>
                                </div>
                                <div class="mb-3">
                                    <label for="editar_metodo_pago" class="form-label">Nuevo método de pago</label>
                                    <select name="nuevo_metodo_pago" id="editar_metodo_pago" class="form-select" required>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia">Transferencia</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="editar_cuenta_wrap" style="display:none;">
                                    <label for="editar_cuenta_id" class="form-label">Cuenta de transferencia</label>
                                    <select name="nueva_cuenta_id" id="editar_cuenta_id" class="form-select">
                                        <option value="">Seleccionar cuenta</option>
                                        <?php foreach ($cuentasTransferencia as $cuenta): ?>
                                        <option value="<?= (int) $cuenta['id'] ?>"><?= htmlspecialchars($cuenta['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Selecciona la cuenta que recibió esta venta.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar corrección
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Resumen de caja antes de cerrar turno -->
            <div class="row mb-4">
                <div class="col-12">
                    <h4><i class="fas fa-calculator"></i> Resumen de Caja</h4>
                    <div class="cierre-pasos mb-3">
                        <div><strong>Paso 1:</strong> Cuenta el efectivo real en caja.</div>
                        <div><strong>Paso 2:</strong> Carga el monto y toca <em>Confirmar monto</em>.</div>
                        <div><strong>Paso 3:</strong> Se habilita <em>Cerrar Turno</em>.</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Saldo inicial</td>
                                    <td class="text-end">$<?= number_format($turno['saldo_inicial'], 0) ?></td>
                                </tr>
                                <tr>
                                    <td>Total ventas en efectivo</td>
                                    <td class="text-end">$<?= number_format($total_efectivo, 0) ?></td>
                                </tr>
                                <tr>
                                    <td>Total ventas en transferencia</td>
                                    <td class="text-end">$<?= number_format($total_transferencia, 0) ?></td>
                                </tr>
                                <tr class="table-info">
                                    <td><strong>Total esperado en caja</strong></td>
                                    <td class="text-end"><strong
                                            id="esperado_en_caja">$<?= number_format($turno['saldo_inicial'] + $total_efectivo, 0) ?></strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label for="saldo_real" class="form-label">Saldo real contado en caja (solo efectivo)</label>
                        <input type="number" step="0.01" class="form-control" id="saldo_real"
                            placeholder="Dinero contado físicamente al cierre (solo efectivo)">
                        <small class="form-text text-muted">Este valor se usará para cerrar el turno y registrar el movimiento de capital.</small>
                    </div>
                    <div id="diferencia_caja" class="alert mt-2" style="display:none;"></div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <h4><i class="fas fa-door-closed"></i> Cerrar Turno</h4>
                    <form method="post" id="formCerrarTurno">
                        <input type="hidden" name="saldo_cierre" id="saldo_cierre_hidden" value="">
                        <div id="estado_confirmacion_cierre" class="alert alert-secondary py-2">
                            Falta confirmar el monto contado para poder cerrar el turno.
                        </div>
                        <button type="button" id="btnConfirmarCierre" class="btn btn-primary w-100 mb-2" disabled>
                            <i class="fas fa-check-circle"></i> Confirmar monto
                        </button>
                        <button type="submit" name="cerrar_turno" id="btnCerrarTurno" class="btn-turno" disabled>
                            <i class="fas fa-stop"></i> Cerrar Turno
                        </button>
                        <small class="form-text text-muted d-block mt-2">
                            Si modificas el monto contado, vuelve a confirmar antes de cerrar.
                        </small>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables
        let productosSeleccionados = {};
        let totalVenta = 0;
        let historialProductos = [];
        const metodoPagoInput = document.getElementById('metodo_pago');
        const botonesMetodoPago = document.querySelectorAll('.btn-metodo-pago');
        const cuentaWrap = document.getElementById('cuentaTransferenciaWrap');
        const cuentaTransferenciaInput = document.getElementById('cuenta_transferencia_id');
        const botonesCuentaTransferencia = document.querySelectorAll('.btn-cuenta-transferencia');
        const btnFinalizar = document.getElementById('btnFinalizar');
        const modalEditarPagoEl = document.getElementById('modalEditarPagoVenta');
        const formEditarPago = document.getElementById('formEditarPagoVenta');
        const editarMetodoSelect = document.getElementById('editar_metodo_pago');
        const editarCuentaWrap = document.getElementById('editar_cuenta_wrap');
        const editarCuentaSelect = document.getElementById('editar_cuenta_id');
        const editarVentaUid = document.getElementById('editar_venta_uid');
        const editarProductoResumen = document.getElementById('editar_producto_resumen');
        const editarHoraResumen = document.getElementById('editar_hora_resumen');
        const editarItemsResumen = document.getElementById('editar_items_resumen');
        const editarImporteResumen = document.getElementById('editar_importe_resumen');
        const cantidadPorToqueInput = document.getElementById('cantidad_por_toque');

        function actualizarMetodoPagoUI() {
            if (!metodoPagoInput || !cuentaWrap || !cuentaTransferenciaInput) {
                return;
            }
            const metodoSeleccionado = metodoPagoInput.value;

            botonesMetodoPago.forEach((btn) => {
                const activo = btn.getAttribute('data-metodo') === metodoSeleccionado;
                btn.classList.toggle('active', activo);
                btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
            });

            if (metodoSeleccionado === 'Transferencia') {
                cuentaWrap.style.display = 'block';
            } else {
                cuentaWrap.style.display = 'none';
                cuentaTransferenciaInput.value = '';
            }

            if (btnFinalizar) {
                const metodoValido = metodoSeleccionado === 'Efectivo' || metodoSeleccionado === 'Transferencia';
                btnFinalizar.disabled = !metodoValido;
            }

            actualizarCuentaTransferenciaUI();
        }

        function actualizarCuentaTransferenciaUI() {
            if (!cuentaTransferenciaInput) {
                return;
            }
            const cuentaActiva = cuentaTransferenciaInput.value;
            botonesCuentaTransferencia.forEach((btn) => {
                const activa = btn.getAttribute('data-cuenta-id') === cuentaActiva;
                btn.classList.toggle('active', activa);
                btn.setAttribute('aria-pressed', activa ? 'true' : 'false');
            });
        }

        function actualizarCuentaEditarPago() {
            if (!editarMetodoSelect || !editarCuentaWrap || !editarCuentaSelect) {
                return;
            }
            if (editarMetodoSelect.value === 'Transferencia') {
                editarCuentaWrap.style.display = 'block';
                editarCuentaSelect.required = true;
            } else {
                editarCuentaWrap.style.display = 'none';
                editarCuentaSelect.required = false;
                editarCuentaSelect.value = '';
            }
        }
        actualizarMetodoPagoUI();
        botonesMetodoPago.forEach((btn) => {
            btn.addEventListener('click', function() {
                if (!metodoPagoInput) {
                    return;
                }
                metodoPagoInput.value = this.getAttribute('data-metodo') || '';
                actualizarMetodoPagoUI();
            });
        });
        botonesCuentaTransferencia.forEach((btn) => {
            btn.addEventListener('click', function() {
                if (!cuentaTransferenciaInput) {
                    return;
                }
                cuentaTransferenciaInput.value = this.getAttribute('data-cuenta-id') || '';
                actualizarCuentaTransferenciaUI();
            });
        });
        if (editarMetodoSelect) {
            editarMetodoSelect.addEventListener('change', actualizarCuentaEditarPago);
        }

        if (modalEditarPagoEl && formEditarPago && editarMetodoSelect && editarCuentaSelect) {
            const modalEditarPago = new bootstrap.Modal(modalEditarPagoEl);
            const botonesEditarVenta = document.querySelectorAll('.btn-editar-venta');

            botonesEditarVenta.forEach((btn) => {
                btn.addEventListener('click', function() {
                    const ventaUid = this.getAttribute('data-venta-uid') || '';
                    const metodo = this.getAttribute('data-metodo') || 'Efectivo';
                    const cuentaId = this.getAttribute('data-cuenta-id') || '';
                    const producto = this.getAttribute('data-producto') || '-';
                    const hora = this.getAttribute('data-hora') || '-';
                    const cantidadTotal = this.getAttribute('data-cantidad-total') || '0';
                    const importe = this.getAttribute('data-importe') || '0';

                    editarVentaUid.value = ventaUid;
                    editarMetodoSelect.value = metodo;
                    editarCuentaSelect.value = cuentaId && cuentaId !== '0' ? cuentaId : '';
                    if (editarProductoResumen) editarProductoResumen.textContent = producto;
                    if (editarHoraResumen) editarHoraResumen.textContent = hora;
                    if (editarItemsResumen) editarItemsResumen.textContent = cantidadTotal;
                    if (editarImporteResumen) editarImporteResumen.textContent = importe;

                    formEditarPago.dataset.confirmed = '0';
                    actualizarCuentaEditarPago();
                    modalEditarPago.show();
                });
            });

            formEditarPago.addEventListener('submit', function(e) {
                if (this.dataset.confirmed === '1') {
                    return;
                }

                e.preventDefault();

                if (editarMetodoSelect.value === 'Transferencia' && !editarCuentaSelect.value) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta cuenta de transferencia',
                        text: 'Selecciona la cuenta de Mercado Pago para guardar la corrección.'
                    });
                    return;
                }

                Swal.fire({
                    icon: 'question',
                    title: '¿Guardar corrección?',
                    text: 'Se actualizará el método de pago de esta venta antes de cerrar turno.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.dataset.confirmed = '1';
                        this.submit();
                    }
                });
            });
        }
        // Stock actual en frontend
        let stockActual = {};
        document.querySelectorAll('.producto').forEach(boton => {
            const id = boton.getAttribute('data-id');
            stockActual[id] = parseInt(boton.getAttribute('data-stock'));
        });

        function obtenerCantidadPorToque() {
            if (!cantidadPorToqueInput) {
                return 1;
            }
            const cantidad = parseInt(cantidadPorToqueInput.value, 10);
            if (!Number.isFinite(cantidad) || cantidad <= 0) {
                cantidadPorToqueInput.value = '1';
                return 1;
            }
            return cantidad;
        }

        function resetearCantidadPorToque() {
            if (cantidadPorToqueInput) {
                cantidadPorToqueInput.value = '1';
            }
        }

        // Función para actualizar el total y el stock visual
        function actualizarTotal() {
            document.getElementById("total").innerText = totalVenta.toFixed(2);
            const lista = document.getElementById("productos_seleccionados");
            if (Object.keys(productosSeleccionados).length === 0) {
                lista.innerHTML = '<p class="text-center text-muted">No hay productos seleccionados</p>';
            } else {
                lista.innerHTML = '';
                for (const [id, info] of Object.entries(productosSeleccionados)) {
                    const div = document.createElement("div");
                    div.className = "producto-item";
                    div.innerHTML = `
                        <span>${info.nombre} × ${info.cantidad}</span>
                        <span class="fw-bold">$${(info.precio * info.cantidad).toFixed(0)}</span>
                    `;
                    lista.appendChild(div);
                }
            }
            // Actualiza los hidden inputs
            const hiddenContainer = document.getElementById("hiddenInputs");
            hiddenContainer.innerHTML = '';
            for (const [id, info] of Object.entries(productosSeleccionados)) {
                const inp = document.createElement("input");
                inp.type = 'hidden';
                inp.name = `productos[${id}]`;
                inp.value = info.cantidad;
                hiddenContainer.appendChild(inp);
            }
            // Actualiza el stock visual en los botones
            for (const id in stockActual) {
                const stockDiv = document.getElementById('stock_' + id);
                if (stockDiv) {
                    stockDiv.innerText = 'Stock: ' + stockActual[id];
                }
                const boton = document.querySelector(`.producto[data-id='${id}']`);
                if (boton) {
                    if (stockActual[id] <= 0) {
                        boton.disabled = true;
                        boton.classList.add('stock-bajo');
                        boton.classList.remove('stock-medio');
                    } else if (stockActual[id] <= 3) {
                        boton.classList.remove('stock-bajo');
                        boton.classList.add('stock-medio');
                        boton.disabled = false;
                    } else {
                        boton.classList.remove('stock-bajo');
                        boton.classList.remove('stock-medio');
                        boton.disabled = false;
                    }
                }
            }
        }

        // Función para corregir el último producto agregado
        window.corregirUltimo = function() {
            if (historialProductos.length > 0) {
                const ultimoProducto = historialProductos.pop();
                const id = ultimoProducto.id;
                if (productosSeleccionados[id]) {
                    if (productosSeleccionados[id].cantidad > 1) {
                        productosSeleccionados[id].cantidad--;
                    } else {
                        delete productosSeleccionados[id];
                    }
                    totalVenta -= ultimoProducto.precio;
                    // Reponer stock visual
                    stockActual[id] = (stockActual[id] || 0) + 1;
                    actualizarTotal();
                }
            }
        }

        // Función para limpiar todo
        window.limpiarTodo = function() {
            // Reponer stock visual de todos los seleccionados
            for (const [id, info] of Object.entries(productosSeleccionados)) {
                stockActual[id] = (stockActual[id] || 0) + info.cantidad;
            }
            productosSeleccionados = {};
            historialProductos = [];
            totalVenta = 0;
            actualizarTotal();
        }

        // Evento para los botones de productos
        const botonesProducto = document.querySelectorAll('.producto');
        botonesProducto.forEach(boton => {
            boton.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                const precio = parseFloat(this.getAttribute('data-precio'));
                const stockDisponible = parseInt(stockActual[id], 10) || 0;
                const cantidadAgregar = Math.min(obtenerCantidadPorToque(), stockDisponible);
                if (cantidadAgregar > 0) {
                    if (productosSeleccionados[id]) {
                        productosSeleccionados[id].cantidad += cantidadAgregar;
                    } else {
                        productosSeleccionados[id] = {
                            nombre,
                            cantidad: cantidadAgregar,
                            precio
                        };
                    }

                    for (let i = 0; i < cantidadAgregar; i++) {
                        historialProductos.push({
                            id,
                            precio
                        });
                    }

                    totalVenta += precio * cantidadAgregar;
                    stockActual[id] -= cantidadAgregar;
                    actualizarTotal();
                    resetearCantidadPorToque();
                }
            });
        });

        // Prevenir envío si no hay productos
        const ventaForm = document.getElementById('ventaForm');
        if (ventaForm) {
            ventaForm.addEventListener('submit', function(e) {
                if (Object.keys(productosSeleccionados).length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta seleccionar productos',
                        text: 'Debes agregar al menos un producto antes de finalizar la venta.'
                    });
                    return false;
                }
                if (!metodoPagoInput || !['Efectivo', 'Transferencia'].includes(metodoPagoInput.value)) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta método de pago',
                        text: 'Selecciona Efectivo o Transferencia para continuar.'
                    });
                    return false;
                }
                if (metodoPagoInput.value === 'Transferencia' && cuentaTransferenciaInput && !cuentaTransferenciaInput.value) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta la cuenta de transferencia',
                        text: 'Selecciona la cuenta de Mercado Pago para registrar esta venta.'
                    });
                    return false;
                }
                btnFinalizar.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                btnFinalizar.classList.add('loading');
            });
        }

        // Inicializar
        actualizarTotal();
    });
    </script>
    <script>
    // Código de seguridad para Control de Capital (SweetAlert2, responsivo)
    document.addEventListener('DOMContentLoaded', function() {
        const btnCapital = document.getElementById('btnCapital');
        if (btnCapital) {
            btnCapital.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Código de seguridad',
                    input: 'password',
                    inputLabel: 'Ingrese el código para acceder al Control de Capital',
                    inputPlaceholder: 'Código',
                    inputAttributes: {
                        maxlength: 10,
                        autocapitalize: 'off',
                        autocorrect: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Ingresar',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    customClass: {
                        popup: 'swal2-responsive'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value === '1203') {
                            window.location.href = 'capital.php';
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Código incorrecto',
                                text: 'El código ingresado no es válido.',
                                timer: 1800,
                                showConfirmButton: false
                            });
                        }
                    }
                });
            });
        }
    });
    </script>
    <script>
    // Flujo de cierre: diferencia, confirmación por botón y habilitación de cierre
    document.addEventListener('DOMContentLoaded', function() {
        const saldoRealInput = document.getElementById('saldo_real');
        const esperadoEl = document.getElementById('esperado_en_caja');
        const diferenciaDiv = document.getElementById('diferencia_caja');
        const estadoConfirmacion = document.getElementById('estado_confirmacion_cierre');
        const saldoCierreHidden = document.getElementById('saldo_cierre_hidden');
        const btnConfirmarCierre = document.getElementById('btnConfirmarCierre');
        const btnCerrarTurno = document.getElementById('btnCerrarTurno');
        const formCerrarTurno = document.getElementById('formCerrarTurno');

        if (!saldoRealInput || !esperadoEl || !diferenciaDiv || !estadoConfirmacion || !saldoCierreHidden || !btnConfirmarCierre || !btnCerrarTurno || !formCerrarTurno) {
            return;
        }

        const esperado = parseFloat(esperadoEl.innerText.replace(/[^\d,.-]/g, '').replace(/,/g, '')) || 0;

        function resetConfirmacion() {
            saldoCierreHidden.value = '';
            btnCerrarTurno.disabled = true;
            estadoConfirmacion.className = 'alert alert-secondary py-2';
            estadoConfirmacion.textContent = 'Falta confirmar el monto contado para poder cerrar el turno.';
        }

        function actualizarDiferencia() {
            const real = parseFloat(saldoRealInput.value) || 0;
            const dif = real - esperado;
            if (saldoRealInput.value === '') {
                diferenciaDiv.style.display = 'none';
                btnConfirmarCierre.disabled = true;
                resetConfirmacion();
                return;
            }
            btnConfirmarCierre.disabled = real < 0;
            diferenciaDiv.style.display = 'block';
            if (Math.abs(dif) < 0.01) {
                diferenciaDiv.className = 'alert alert-success mt-2';
                diferenciaDiv.innerHTML =
                    '<b>¡Cuadre perfecto!</b> El saldo contado coincide con el esperado.';
            } else if (dif > 0) {
                diferenciaDiv.className = 'alert alert-warning mt-2';
                diferenciaDiv.innerHTML = 'Sobrante en caja: <b>$' + dif.toLocaleString('es-AR') + '</b>.';
            } else {
                diferenciaDiv.className = 'alert alert-danger mt-2';
                diferenciaDiv.innerHTML = 'Faltante en caja: <b>$' + Math.abs(dif).toLocaleString('es-AR') + '</b>.';
            }
            resetConfirmacion();
        }

        btnConfirmarCierre.addEventListener('click', function() {
            const real = parseFloat(saldoRealInput.value);
            if (!Number.isFinite(real) || real < 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Monto inválido',
                    text: 'Ingresa un saldo real válido para confirmar.'
                });
                return;
            }
            saldoCierreHidden.value = real.toFixed(2);
            btnCerrarTurno.disabled = false;
            estadoConfirmacion.className = 'alert alert-success py-2';
            estadoConfirmacion.innerHTML = 'Monto confirmado: <b>$' + real.toLocaleString('es-AR') + '</b>. Ya puedes cerrar el turno.';
        });

        formCerrarTurno.addEventListener('submit', function(e) {
            if (saldoCierreHidden.value === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta confirmar monto',
                    text: 'Primero confirma el monto contado para cerrar el turno.'
                });
            }
        });

        saldoRealInput.addEventListener('input', actualizarDiferencia);
        actualizarDiferencia();
    });
    </script>
</body>

</html>

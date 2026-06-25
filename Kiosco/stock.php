<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';
$mensaje = '';
$mensajeTipo = 'success';

// Verificar permisos de superadmin
$rol = $db->prepare("SELECT es_admin, super_admin FROM usuarios WHERE id = ?");
$rol->execute([$_SESSION['usuario_id']]);
$rol = $rol->fetch(PDO::FETCH_ASSOC) ?: [];
$_SESSION['usuario_admin'] = (int) ($rol['es_admin'] ?? 0);
$_SESSION['usuario_super_admin'] = (int) ($rol['super_admin'] ?? 0);
$isSuperAdmin = !empty($_SESSION['usuario_super_admin']) && (int) $_SESSION['usuario_super_admin'] === 1;

// Modificar producto/stock (unificado)
if (isset($_POST['modificar_producto'])) {
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $motivo = trim($_POST['motivo'] ?? '');
    $tipo_movimiento = strtolower(trim($_POST['tipo_movimiento'] ?? ''));
    if (!in_array($tipo_movimiento, ['sumar', 'restar'], true)) {
        $tipo_movimiento = strcasecmp($motivo, 'Ajuste de inventario') === 0 ? 'restar' : 'sumar';
    }
    // Solo superadmin puede modificar precios y costos
    $nuevo_precio = ($isSuperAdmin && isset($_POST['precio'])) ? floatval($_POST['precio']) : null;
    $nuevo_costo = ($isSuperAdmin && isset($_POST['costo'])) ? floatval($_POST['costo']) : null;

    // Obtener precio y costo actuales
    $producto_actual = $db->prepare("SELECT precio, costo FROM productos WHERE id = ?");
    $producto_actual->execute([$producto_id]);
    $datos_actuales = $producto_actual->fetch(PDO::FETCH_ASSOC);

    if (!$datos_actuales) {
        $mensaje = "Producto no encontrado.";
        $mensajeTipo = 'danger';
    } else {
        try {
            $db->beginTransaction();

            // Si cantidad > 0, actualizar stock y registrar movimiento
            if ($cantidad > 0) {
                $stock_actual_stmt = $db->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
                $stock_actual_stmt->execute([$producto_id]);
                $stock_actual_row = $stock_actual_stmt->fetch(PDO::FETCH_ASSOC);
                $stock_actual = (int)($stock_actual_row['stock'] ?? 0);
                $cantidad_movimiento = $cantidad;

                if ($tipo_movimiento === 'restar') {
                    if ($cantidad > $stock_actual) {
                        throw new RuntimeException("No se puede restar $cantidad unidad(es): stock disponible $stock_actual.");
                    }
                    $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$cantidad, $producto_id]);
                    $cantidad_movimiento = -$cantidad;
                } else {
                    $db->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")->execute([$cantidad, $producto_id]);
                }

                $turno_actual = $db->query("SELECT id FROM turnos WHERE fecha_cierre IS NULL ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($turno_actual) {
                    $descripcion_movimiento = $motivo !== '' ? $motivo : ($tipo_movimiento === 'restar' ? 'Ajuste de inventario' : 'Ajuste de stock');
                    $db->prepare("INSERT INTO transacciones (turno_id, fecha, tipo, producto_id, descripcion, cantidad, metodo_pago, importe, usuario_id) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([
                        $turno_actual['id'],
                        date('Y-m-d H:i:s'),
                        'AjusteStock',
                        $producto_id,
                        $descripcion_movimiento,
                        $cantidad_movimiento,
                        null,
                        0,
                        $_SESSION['usuario_id']
                    ]);
                }
            }
            // Si cambió precio o costo, actualizar
            if ($nuevo_precio !== null && $nuevo_precio != $datos_actuales['precio']) {
                $db->prepare("UPDATE productos SET precio = ? WHERE id = ?")->execute([$nuevo_precio, $producto_id]);
            }
            if ($nuevo_costo !== null && $nuevo_costo != $datos_actuales['costo']) {
                $db->prepare("UPDATE productos SET costo = ? WHERE id = ?")->execute([$nuevo_costo, $producto_id]);
            }

            $db->commit();
            $mensaje = "Producto/stock modificado correctamente.";
            $mensajeTipo = 'success';
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $mensaje = "No se pudo guardar el cambio de stock: " . $e->getMessage();
            $mensajeTipo = 'danger';
        }
    }
}

// Cargar productos
$productos = $db->query("SELECT * FROM productos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$stockResumen = [
    'total_productos' => count($productos),
    'total_unidades' => 0,
    'stock_bajo' => 0,
    'stock_medio' => 0,
    'stock_ok' => 0,
];
foreach ($productos as $sp) {
    $stock = (int) ($sp['stock'] ?? 0);
    $stockResumen['total_unidades'] += $stock;
    if ($stock <= 0) {
        $stockResumen['stock_bajo']++;
    } elseif ($stock <= 3) {
        $stockResumen['stock_medio']++;
    } else {
        $stockResumen['stock_ok']++;
    }
}

// Agregar producto nuevo (todos los usuarios pueden crear)
if (isset($_POST['crear_producto'])) {
    $ref = trim($_POST['nuevo_ref'] ?? '');
    $nombre = trim($_POST['nuevo_nombre']);
    $precio = floatval($_POST['nuevo_precio']);
    $costo = floatval($_POST['nuevo_costo']);
    $stock = intval($_POST['nuevo_stock']);
    if ($nombre && $precio > 0 && $costo >= 0 && $stock >= 0) {
        $db->prepare("INSERT INTO productos (ref, nombre, precio, costo, stock) VALUES (?, ?, ?, ?, ?)")->execute([$ref, $nombre, $precio, $costo, $stock]);
        header('Location: stock.php');
        exit;
    } else {
        $mensaje = "Datos invalidos para crear producto.";
        $mensajeTipo = 'danger';
    }
}

// Eliminar producto
if (isset($_POST['eliminar_producto']) && isset($_POST['producto_id_eliminar'])) {
    $id = intval($_POST['producto_id_eliminar']);
    $db->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
    header('Location: stock.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Stock - v2.1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1f3b57 0%, #446e8d 55%, #8cb7d6 100%);
            min-height: 100vh;
        }
        .main-container {
            background: #f8fafc;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.22);
            margin: 18px auto;
            max-width: 1250px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 18px 20px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header h1 {
            margin: 0;
            font-size: 1.7rem;
        }
        .header-user {
            font-size: 0.95rem;
            font-weight: 600;
            opacity: 0.95;
        }
        .header-actions {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .toolbar {
            background: #ffffff;
            border: 1px solid #dce7f3;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .toolbar-actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .toolbar-actions .btn {
            font-weight: 600;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .stat-card {
            border-radius: 12px;
            border: 1px solid #dce7f3;
            background: #fff;
            padding: 10px 12px;
        }
        .stat-card .label {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .stat-card .value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }
        .stat-danger { border-color: #fecaca; background: #fef2f2; }
        .stat-warning { border-color: #fde68a; background: #fffbeb; }
        .stat-success { border-color: #bbf7d0; background: #f0fdf4; }
        .filter-bar {
            background: #fff;
            border: 1px solid #dce7f3;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .stock-visible {
            font-size: 0.85rem;
            color: #475569;
            font-weight: 600;
            margin-top: 8px;
        }
        .product-card {
            border-radius: 14px;
            border: 1px solid #dbe7f4;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
            height: 100%;
            background: #fff;
        }
        .stock-bajo { border-left: 6px solid #dc2626; }
        .stock-medio { border-left: 6px solid #d97706; }
        .stock-ok { border-left: 6px solid #16a34a; }
        .stock-chip {
            border-radius: 999px;
            font-size: 0.77rem;
            padding: 3px 10px;
            font-weight: 700;
        }
        .stock-chip-bajo { background: #fee2e2; color: #991b1b; }
        .stock-chip-medio { background: #fef3c7; color: #92400e; }
        .stock-chip-ok { background: #dcfce7; color: #166534; }
        .product-ref {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 2px;
        }
        .product-meta {
            font-size: 0.9rem;
            color: #1f2937;
            margin: 8px 0 10px 0;
            line-height: 1.45;
        }
        .product-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .stock-empty {
            display: none;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            color: #475569;
        }
        @media (max-width: 992px) {
            .toolbar-actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 600px) {
            .main-container {
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
            }
            .header {
                padding: 14px 12px;
            }
            .header h1 {
                font-size: 1.35rem;
            }
            .toolbar-actions {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 7px;
            }
            .product-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <div class="header-top">
                <h1><i class="fas fa-warehouse"></i> Gestión de Stock</h1>
                <div class="header-user">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver al POS
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </div>
        </div>

        <div class="container-fluid p-4">
            <?php if($mensaje): ?>
            <div class="alert alert-<?= $mensajeTipo ?> mb-3"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="toolbar">
                <div class="toolbar-actions">
                    <a href="exportar_stock_excel.php" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="exportar_faltantes_pdf.php" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Faltantes PDF
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                        <i class="fas fa-plus"></i> Nuevo producto
                    </button>
                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('filtroProducto').focus()">>
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Productos</div>
                    <div class="value"><?= number_format((int) $stockResumen['total_productos'], 0, ',', '.') ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Unidades totales</div>
                    <div class="value"><?= number_format((int) $stockResumen['total_unidades'], 0, ',', '.') ?></div>
                </div>
                <div class="stat-card stat-danger">
                    <div class="label">Sin stock</div>
                    <div class="value"><?= number_format((int) $stockResumen['stock_bajo'], 0, ',', '.') ?></div>
                </div>
                <div class="stat-card <?= ((int) $stockResumen['stock_medio'] > 0) ? 'stat-warning' : 'stat-success' ?>">
                    <div class="label">Stock bajo (1-3)</div>
                    <div class="value"><?= number_format((int) $stockResumen['stock_medio'], 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="filter-bar">
                <div class="row g-2">
                    <div class="col-12 col-md-8">
                        <label for="filtroProducto" class="form-label mb-1">Buscar producto</label>
                        <input type="text" id="filtroProducto" class="form-control" placeholder="Nombre o referencia">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="filtroEstado" class="form-label mb-1">Estado de stock</label>
                        <select id="filtroEstado" class="form-select">
                            <option value="todos">Todos</option>
                            <option value="bajo">Sin stock</option>
                            <option value="medio">Stock bajo (1-3)</option>
                            <option value="ok">Stock OK (4+)</option>
                        </select>
                    </div>
                </div>
                <div class="stock-visible" id="stockVisibleCount"></div>
            </div>

            <h3 class="mb-3"><i class="fas fa-boxes"></i> Stock Actual</h3>
            <div class="row g-3" id="productosGrid">
                <?php foreach($productos as $p): ?>
                <?php
                    $stock = (int) ($p['stock'] ?? 0);
                    $stockClass = 'stock-ok';
                    $stockState = 'ok';
                    $stockText = 'Stock OK';
                    $stockChipClass = 'stock-chip-ok';
                    if ($stock <= 0) {
                        $stockClass = 'stock-bajo';
                        $stockState = 'bajo';
                        $stockText = 'Sin stock';
                        $stockChipClass = 'stock-chip-bajo';
                    } elseif ($stock <= 3) {
                        $stockClass = 'stock-medio';
                        $stockState = 'medio';
                        $stockText = 'Stock bajo';
                        $stockChipClass = 'stock-chip-medio';
                    }
                    $nombreProducto = (string) ($p['nombre'] ?? '');
                    $refProducto = trim((string) ($p['ref'] ?? ''));
                    $searchText = strtolower(trim($nombreProducto . ' ' . $refProducto));
                ?>
                <div class="col-12 col-md-6 col-xl-4 producto-col">
                    <div class="card product-card <?= $stockClass ?>"
                        data-stock-state="<?= $stockState ?>"
                        data-product-name="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8') ?></h5>
                                    <div class="product-ref"><?= $refProducto !== '' ? 'Ref: ' . htmlspecialchars($refProducto, ENT_QUOTES, 'UTF-8') : 'Sin referencia' ?></div>
                                </div>
                                <span class="stock-chip <?= $stockChipClass ?>"><?= $stockText ?></span>
                            </div>
                            <div class="product-meta">
                                <div><strong>Stock:</strong> <?= number_format($stock, 0, ',', '.') ?> unidades</div>
                                <div><strong>Precio:</strong> $<?= number_format((float) $p['precio'], 2, ',', '.') ?></div>
                                <div><strong>Costo:</strong> $<?= number_format((float) $p['costo'], 2, ',', '.') ?></div>
                            </div>
                            <div class="product-actions">
                                <button class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalStock"
                                    onclick="abrirModalModificarProducto(<?= (int) $p['id'] ?>, '<?= htmlspecialchars($nombreProducto, ENT_QUOTES) ?>', <?= (float) $p['precio'] ?>, <?= (float) $p['costo'] ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-outline-danger btn-sm"
                                    onclick="confirmarEliminarProducto(<?= (int) $p['id'] ?>, '<?= htmlspecialchars($nombreProducto, ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="stock-empty mt-2" id="stockSinResultados">
                No hay productos que coincidan con el filtro seleccionado.
            </div>
        </div>
    </div>

    <!-- Modal Bootstrap para modificar producto/stock -->
    <div class="modal fade" id="modalStock" tabindex="-1" aria-labelledby="modalStockLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form method="post" id="stockForm">
            <input type="hidden" name="producto_id" id="producto_id">
            <input type="hidden" name="modificar_producto" value="1">
            <div class="modal-header">
              <h5 class="modal-title" id="modalStockLabel"><i class="fas fa-edit"></i> Modificar producto/Stock</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Producto</label>
                <input type="text" class="form-control" id="producto_nombre" readonly>
              </div>
              <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad de ajuste (puede ser 0)</label>
                <input type="number" name="cantidad" class="form-control" min="0" id="cantidad" required>
              </div>
              <div class="mb-3">
                <label for="tipo_movimiento" class="form-label">Tipo de ajuste de stock</label>
                <select name="tipo_movimiento" class="form-control" id="tipo_movimiento">
                  <option value="sumar">Sumar al stock</option>
                  <option value="restar">Restar del stock</option>
                </select>
                <small class="text-muted" id="tipoMovimientoAyuda">Sumar incrementa stock. Restar descuenta unidades del inventario.</small>
              </div>
              <div class="mb-3">
                <label for="motivo" class="form-label">Motivo</label>
                <select name="motivo" class="form-control" id="motivo">
                  <option value="Reposición de stock">Reposición de stock</option>
                  <option value="Compra nueva">Compra nueva</option>
                  <option value="Ajuste de inventario">Ajuste de inventario</option>
                  <option value="Devolución">Devolución</option>
                </select>
              </div>
              <?php if ($isSuperAdmin): ?>
              <div class="mb-3">
                <label for="precio" class="form-label">Precio de venta</label>
                <input type="number" name="precio" class="form-control" id="precio" min="1" step="0.01" required>
              </div>
              <div class="mb-3">
                <label for="costo" class="form-label">Costo</label>
                <input type="number" name="costo" class="form-control" id="costo" min="0" step="0.01" required>
              </div>
              <?php else: ?>
              <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Solo el superadmin puede modificar precios y costos.
                <div class="mt-2">
                  <strong>Precio actual:</strong> $<span id="precio_display">-</span><br>
                  <strong>Costo actual:</strong> $<span id="costo_display">-</span>
                </div>
              </div>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Bootstrap para nuevo producto -->
    <div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-labelledby="modalNuevoProductoLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
          <form method="post" id="nuevoProductoForm">
            <input type="hidden" name="crear_producto" value="1">
            <div class="modal-header">
              <h5 class="modal-title" id="modalNuevoProductoLabel"><i class="fas fa-plus"></i> Nuevo Producto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="nuevo_ref" class="form-label">Referencia (opcional)</label>
                <input type="text" name="nuevo_ref" class="form-control" id="nuevo_ref" maxlength="30">
              </div>
              <div class="mb-3">
                <label for="nuevo_nombre" class="form-label">Nombre</label>
                <input type="text" name="nuevo_nombre" class="form-control" id="nuevo_nombre" required maxlength="50">
              </div>
              <div class="mb-3">
                <label for="nuevo_precio" class="form-label">Precio de venta</label>
                <input type="number" name="nuevo_precio" class="form-control" id="nuevo_precio" min="1" step="0.01" required>
              </div>
              <div class="mb-3">
                <label for="nuevo_costo" class="form-label">Costo</label>
                <input type="number" name="nuevo_costo" class="form-control" id="nuevo_costo" min="0" step="0.01" required>
              </div>
              <div class="mb-3">
                <label for="nuevo_stock" class="form-label">Stock inicial</label>
                <input type="number" name="nuevo_stock" class="form-control" id="nuevo_stock" min="0" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function aplicarFiltrosStock() {
            const input = document.getElementById('filtroProducto');
            const estado = document.getElementById('filtroEstado');
            const columnas = document.querySelectorAll('.producto-col');
            const visibleNode = document.getElementById('stockVisibleCount');
            const emptyNode = document.getElementById('stockSinResultados');

            if (!input || !estado || columnas.length === 0) {
                return;
            }

            const query = (input.value || '').trim().toLowerCase();
            const estadoSel = estado.value || 'todos';
            let visibles = 0;

            columnas.forEach((col) => {
                const card = col.querySelector('.product-card');
                if (!card) return;
                const productName = (card.getAttribute('data-product-name') || '').toLowerCase();
                const stockState = card.getAttribute('data-stock-state') || 'ok';
                const coincideTexto = query === '' || productName.includes(query);
                const coincideEstado = estadoSel === 'todos' || stockState === estadoSel;
                const mostrar = coincideTexto && coincideEstado;
                col.style.display = mostrar ? '' : 'none';
                if (mostrar) visibles++;
            });

            if (visibleNode) {
                visibleNode.textContent = visibles + ' producto' + (visibles === 1 ? '' : 's') + ' visible' + (visibles === 1 ? '' : 's');
            }
            if (emptyNode) {
                emptyNode.style.display = visibles === 0 ? 'block' : 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('filtroProducto');
            const estado = document.getElementById('filtroEstado');
            if (input) {
                input.addEventListener('input', aplicarFiltrosStock);
            }
            if (estado) {
                estado.addEventListener('change', aplicarFiltrosStock);
            }
            aplicarFiltrosStock();
        });

        function abrirModalModificarProducto(id, nombre, precio, costo) {
            document.getElementById('producto_id').value = id;
            document.getElementById('producto_nombre').value = nombre;
            
            // Solo poplar campos editables si existen (superadmin)
            const precioInput = document.getElementById('precio');
            const costoInput = document.getElementById('costo');
            if (precioInput && costoInput) {
                precioInput.value = precio;
                costoInput.value = costo;
                precioInput.setAttribute('data-original', precio);
                costoInput.setAttribute('data-original', costo);
            }
            
            // Mostrar valores informativos si existen (usuario normal)
            const precioDisplay = document.getElementById('precio_display');
            const costoDisplay = document.getElementById('costo_display');
            if (precioDisplay && costoDisplay) {
                precioDisplay.textContent = precio.toFixed(2);
                costoDisplay.textContent = costo.toFixed(2);
            }
            
            document.getElementById('cantidad').value = 0;
            document.getElementById('tipo_movimiento').value = 'sumar';
            document.getElementById('motivo').selectedIndex = 0;
            actualizarAyudaTipoMovimiento();
        }

        function actualizarAyudaTipoMovimiento() {
            const tipoSelect = document.getElementById('tipo_movimiento');
            const ayuda = document.getElementById('tipoMovimientoAyuda');
            if (!tipoSelect || !ayuda) {
                return;
            }
            if (tipoSelect.value === 'restar') {
                ayuda.textContent = 'Restar descuenta unidades. No se permite dejar stock negativo.';
                return;
            }
            ayuda.textContent = 'Sumar incrementa stock. Restar descuenta unidades del inventario.';
        }

        document.getElementById('stockForm').addEventListener('submit', function(e) {
            var precioInput = document.getElementById('precio');
            var costoInput = document.getElementById('costo');
            
            // Solo validar precios si los campos existen (superadmin)
            if (precioInput && costoInput) {
                var precioOriginal = precioInput.getAttribute('data-original');
                var costoOriginal = costoInput.getAttribute('data-original');
                var precioNuevo = precioInput.value;
                var costoNuevo = costoInput.value;
                
                if ((precioNuevo != precioOriginal || costoNuevo != costoOriginal)) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Actualizar precio o costo?',
                        text: 'Has modificado el precio o el costo. ¿Deseas guardar estos cambios?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, guardar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            e.target.submit();
                        }
                    });
                }
            }
        });

        document.getElementById('tipo_movimiento').addEventListener('change', actualizarAyudaTipoMovimiento);
        document.getElementById('motivo').addEventListener('change', function() {
            const tipoSelect = document.getElementById('tipo_movimiento');
            if (!tipoSelect) {
                return;
            }
            if (this.value === 'Ajuste de inventario') {
                tipoSelect.value = 'restar';
                actualizarAyudaTipoMovimiento();
            }
        });

        function confirmarEliminarProducto(id, nombre) {
            Swal.fire({
                title: '¿Eliminar producto?',
                text: '¿Seguro que quieres eliminar "' + nombre + '"? Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Crear y enviar formulario oculto
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    form.innerHTML = '<input type="hidden" name="eliminar_producto" value="1">' +
                        '<input type="hidden" name="producto_id_eliminar" value="' + id + '">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html> 

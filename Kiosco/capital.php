<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';
$cuentasTransferencia = get_cuentas_transferencia($db);

// Obtener capital actual
$stmt = $db->query("SELECT 
    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN efectivo ELSE -efectivo END), 0) as efectivo_actual,
    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN transferencia ELSE -transferencia END), 0) as transferencia_actual
FROM capital_liquido");
$capital_actual = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener valor del inventario
$stmt = $db->query("SELECT COALESCE(SUM(stock * costo), 0) as valor_inventario FROM productos");
$inventario = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener balance de hoy
$hoy = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM balance_diario WHERE fecha = ?");
$stmt->execute([$hoy]);
$balance_hoy = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT 
    cp.id,
    cp.nombre,
    COALESCE(SUM(CASE WHEN cl.tipo = 'ingreso' THEN cl.transferencia ELSE -cl.transferencia END), 0) AS saldo
FROM cuentas_pago cp
LEFT JOIN capital_liquido cl ON cl.cuenta_id = cp.id
WHERE cp.activo = 1 AND cp.tipo = 'transferencia'
GROUP BY cp.id, cp.nombre
ORDER BY cp.orden ASC, cp.id ASC");
$saldos_cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimas transacciones
$stmt = $db->query("SELECT cl.*, cp.nombre AS cuenta_nombre, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
FROM capital_liquido cl
LEFT JOIN cuentas_pago cp ON cp.id = cl.cuenta_id
LEFT JOIN usuarios u ON u.id = cl.usuario_id
ORDER BY cl.created_at DESC
LIMIT 10");
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimas compras
$stmt = $db->query("SELECT cm.*, cp.nombre AS cuenta_nombre, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
FROM compras_mercaderia cm
LEFT JOIN cuentas_pago cp ON cp.id = cm.cuenta_id
LEFT JOIN usuarios u ON u.id = cm.usuario_id
ORDER BY cm.created_at DESC
LIMIT 10");
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$capital_total = $capital_actual['efectivo_actual'] + $capital_actual['transferencia_actual'] + $inventario['valor_inventario'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Capital - POS San Martín</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    :root {
        --bg-a: #0f4c75;
        --bg-b: #3282b8;
        --surface: #ffffff;
        --surface-soft: #f6f9fc;
        --border: #d8e2ec;
        --text: #102a43;
        --muted: #627d98;
        --radius: 14px;
    }

    body {
        background:
            radial-gradient(circle at 15% 18%, rgba(255, 255, 255, 0.14), transparent 35%),
            linear-gradient(150deg, var(--bg-a), var(--bg-b));
        min-height: 100vh;
        color: var(--text);
    }

    .app-shell {
        max-width: 1220px;
        margin: 0 auto;
        padding: 10px;
    }

    .page-header {
        background: linear-gradient(130deg, #102a43, #1f4f75);
        color: #fff;
        border-radius: 16px;
        padding: 14px;
        margin-bottom: 12px;
    }

    .page-title {
        margin: 0;
        font-weight: 700;
        font-size: 1.25rem;
    }

    .page-subtitle {
        margin-top: 3px;
        color: rgba(255, 255, 255, 0.86);
        font-size: 0.93rem;
    }

    .header-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 12px;
    }

    .header-actions .btn {
        width: 100%;
    }

    .metric-card {
        border: 0;
        border-radius: var(--radius);
        color: #fff;
        min-height: 148px;
        box-shadow: 0 8px 18px rgba(3, 27, 48, 0.24);
    }

    .metric-card .card-body {
        padding: 14px;
    }

    .metric-card h3 {
        margin-bottom: 0;
        font-size: 1.55rem;
    }

    .metric-capital {
        background: linear-gradient(145deg, #ff6b6b, #f06595);
    }

    .metric-inventario {
        background: linear-gradient(145deg, #00b4d8, #48cae4);
    }

    .metric-total {
        background: linear-gradient(145deg, #2f9e44, #51cf66);
    }

    .metric-balance {
        background: linear-gradient(145deg, #ff922b, #fcc419);
        color: #2b2b2b;
    }

    .section-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: 0 10px 24px rgba(10, 35, 60, 0.11);
        overflow: hidden;
    }

    .section-head {
        padding: 12px 14px;
        border-bottom: 1px solid var(--border);
        background: var(--surface-soft);
    }

    .section-title {
        margin: 0;
        font-size: 1.02rem;
        font-weight: 700;
    }

    .section-content {
        padding: 12px;
    }

    .account-tile {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 11px 12px;
        background: #fff;
        height: 100%;
    }

    .action-grid .btn {
        min-height: 52px;
        font-weight: 600;
        border-radius: 12px;
    }

    .desktop-only {
        display: none;
    }

    .mobile-list-item {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 10px;
        background: #fff;
        margin-bottom: 9px;
    }

    .item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 7px;
    }

    .item-date {
        color: var(--muted);
        font-size: 0.86rem;
    }

    .item-amount {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 4px 0;
    }

    .item-meta {
        color: var(--muted);
        font-size: 0.88rem;
        margin-bottom: 4px;
    }

    .item-desc {
        font-size: 0.92rem;
        margin-bottom: 8px;
        color: #334e68;
    }

    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
    }

    .table th {
        background-color: #f8fafc;
        border: none;
        font-weight: 600;
    }

    .table td {
        border: none;
        border-bottom: 1px solid #e6edf5;
    }

    .fila-comprobante {
        cursor: pointer;
    }

    .fila-comprobante:hover {
        background-color: #eef7ff;
    }

    .hint-comprobante {
        font-size: 0.78rem;
        color: #4b5563;
    }

    .info-box {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid #bee3f8;
        color: #1e3a5f;
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 0.95rem;
    }

    .capital-modal .modal-content {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .capital-modal .modal-header {
        border-bottom: 0;
    }

    .capital-modal .modal-body {
        background: #f8fafc;
    }

    .capital-modal .modal-footer {
        border-top: 1px solid #e6edf5;
    }

    .capital-modal .form-label {
        font-weight: 600;
    }

    .capital-modal .input-group-text {
        background: #eef3f8;
        border-color: #ced7e2;
    }

    @media (min-width: 768px) {
        .app-shell {
            padding: 20px;
        }

        .header-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 0;
        }
    }

    @media (min-width: 992px) {
        .desktop-only {
            display: block;
        }

        .mobile-only {
            display: none;
        }
    }
    </style>
</head>

<body>
    <div class="app-shell">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-chart-line me-2"></i>Control de Capital</h1>
            <div class="page-subtitle">
                <i class="fas fa-user"></i>
                <?= htmlspecialchars($_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="header-actions">
                <a href="exportar_capital_excel.php" class="btn btn-light btn-sm">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </a>
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> POS
                </a>
                <a href="logout.php" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i> Salir
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card metric-card metric-capital h-100">
                    <div class="card-body">
                        <i class="fas fa-money-bill-wave fa-lg mb-2"></i>
                        <div class="small">Capital líquido</div>
                        <h3>$<?= number_format($capital_actual['efectivo_actual'] + $capital_actual['transferencia_actual'], 0, ',', '.') ?></h3>
                        <div class="small">Ef: $<?= number_format($capital_actual['efectivo_actual'], 0, ',', '.') ?></div>
                        <div class="small">Tr: $<?= number_format($capital_actual['transferencia_actual'], 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card metric-card metric-inventario h-100">
                    <div class="card-body">
                        <i class="fas fa-boxes fa-lg mb-2"></i>
                        <div class="small">Capital en productos</div>
                        <h3>$<?= number_format($inventario['valor_inventario'], 0, ',', '.') ?></h3>
                        <div class="small">Valor de inventario</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card metric-card metric-total h-100">
                    <div class="card-body">
                        <i class="fas fa-chart-pie fa-lg mb-2"></i>
                        <div class="small">Capital total</div>
                        <h3>$<?= number_format($capital_total, 0, ',', '.') ?></h3>
                        <div class="small">Líquido + productos</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card metric-card metric-balance h-100">
                    <div class="card-body">
                        <i class="fas fa-calendar-day fa-lg mb-2"></i>
                        <div class="small">Balance de hoy</div>
                        <?php if ($balance_hoy): ?>
                        <h3>$<?= number_format($balance_hoy['capital_final_efectivo'] + $balance_hoy['capital_final_transferencia'], 0, ',', '.') ?></h3>
                        <div class="small">Capital final del día</div>
                        <?php else: ?>
                        <h3>--</h3>
                        <div class="small">Sin balance cargado</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card mb-3">
            <div class="section-head">
                <h2 class="section-title"><i class="fas fa-wallet me-2"></i>Saldos por cuentas de transferencia</h2>
            </div>
            <div class="section-content">
                <?php if (count($saldos_cuentas) === 0): ?>
                <div class="alert alert-warning mb-0">No hay cuentas de transferencia configuradas.</div>
                <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($saldos_cuentas as $sc): ?>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="account-tile">
                            <div class="fw-bold"><?= htmlspecialchars($sc['nombre']) ?></div>
                            <div class="text-muted small">Saldo transferencias</div>
                            <div class="h5 mt-2 mb-0">$<?= number_format((float) $sc['saldo'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-card mb-3">
            <div class="section-head">
                <h2 class="section-title"><i class="fas fa-cogs me-2"></i>Gestión de capital</h2>
            </div>
            <div class="section-content">
                <div class="row g-2 action-grid">
                    <div class="col-6 col-lg-3">
                        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalIngreso">
                            <i class="fas fa-plus me-1"></i> Ingreso
                        </button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#modalEgreso">
                            <i class="fas fa-minus me-1"></i> Egreso
                        </button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#modalCompra">
                            <i class="fas fa-shopping-cart me-1"></i> Compra
                        </button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <a class="btn btn-primary w-100" href="balance_completo.php">
                            <i class="fas fa-chart-pie me-1"></i> Balance completo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="section-card h-100">
                    <div class="section-head">
                        <h2 class="section-title"><i class="fas fa-history me-2"></i>Últimas transacciones</h2>
                    </div>
                    <div class="section-content">
                        <div class="mobile-only">
                            <?php if (empty($transacciones)): ?>
                            <div class="alert alert-light border mb-0">No hay transacciones recientes.</div>
                            <?php endif; ?>
                            <?php foreach ($transacciones as $t): ?>
                            <?php
                                $esEgreso = isset($t['tipo']) && $t['tipo'] === 'egreso';
                                $tieneComprobante = !empty($t['comprobante_path']);
                                $pathComprobante = $tieneComprobante ? (string) $t['comprobante_path'] : '';
                                $tipoComprobante = $t['comprobante_tipo'] ?? '';
                                $usuarioTransaccion = trim((string) (($t['usuario_nombre'] ?? '') . ' ' . ($t['usuario_apellido'] ?? '')));
                            ?>
                            <div class="mobile-list-item">
                                <div class="item-top">
                                    <div class="item-date"><?= date('d/m/Y', strtotime($t['fecha'])) ?></div>
                                    <div>
                                        <?php if ($t['tipo'] == 'ingreso'): ?>
                                        <span class="badge bg-success">Ingreso</span>
                                        <?php elseif ($t['tipo'] == 'egreso'): ?>
                                        <span class="badge bg-danger">Egreso</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning text-dark">Ajuste</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-amount">$<?= number_format($t['efectivo'] + $t['transferencia'], 0, ',', '.') ?></div>
                                <div class="item-meta">Cuenta: <?= htmlspecialchars($t['cuenta_nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="item-meta">Usuario: <?= htmlspecialchars($usuarioTransaccion !== '' ? $usuarioTransaccion : '-', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="item-desc"><?= htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($tieneComprobante): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-comprobante-modal"
                                    data-comprobante-path="<?= htmlspecialchars($pathComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                    data-comprobante-tipo="<?= htmlspecialchars((string) $tipoComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                    data-comprobante-label="<?= $esEgreso ? 'egreso' : 'movimiento' ?>">
                                    Ver comprobante
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="desktop-only table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Monto</th>
                                        <th>Cuenta</th>
                                        <th>Usuario</th>
                                        <th>Descripción</th>
                                        <th>Comprobante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transacciones)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay transacciones recientes.</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php foreach ($transacciones as $t): ?>
                                    <?php
                                        $esEgreso = isset($t['tipo']) && $t['tipo'] === 'egreso';
                                        $tieneComprobante = !empty($t['comprobante_path']);
                                        $pathComprobante = $tieneComprobante ? (string) $t['comprobante_path'] : '';
                                        $tipoComprobante = $t['comprobante_tipo'] ?? '';
                                        $rowClass = ($esEgreso && $tieneComprobante) ? 'fila-comprobante' : '';
                                        $usuarioTransaccion = trim((string) (($t['usuario_nombre'] ?? '') . ' ' . ($t['usuario_apellido'] ?? '')));
                                    ?>
                                    <tr class="<?= $rowClass ?>"
                                        <?php if ($esEgreso && $tieneComprobante): ?>
                                        data-comprobante-path="<?= htmlspecialchars($pathComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                        data-comprobante-tipo="<?= htmlspecialchars((string) $tipoComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                        data-comprobante-label="egreso"
                                        title="Toca para ver el comprobante"
                                        <?php endif; ?>>
                                        <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                                        <td>
                                            <?php if ($t['tipo'] == 'ingreso'): ?>
                                            <span class="badge bg-success">Ingreso</span>
                                            <?php elseif ($t['tipo'] == 'egreso'): ?>
                                            <span class="badge bg-danger">Egreso</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">Ajuste</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?= number_format($t['efectivo'] + $t['transferencia'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($t['cuenta_nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($usuarioTransaccion !== '' ? $usuarioTransaccion : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($tieneComprobante): ?>
                                            <a href="<?= htmlspecialchars($pathComprobante, ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                                                class="btn btn-sm btn-outline-primary btn-ver-comprobante">Ver</a>
                                            <?php if ($esEgreso): ?>
                                            <div class="hint-comprobante mt-1">Tocar fila</div>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="section-card h-100">
                    <div class="section-head">
                        <h2 class="section-title"><i class="fas fa-shopping-bag me-2"></i>Últimas compras</h2>
                    </div>
                    <div class="section-content">
                        <div class="mobile-only">
                            <?php if (empty($compras)): ?>
                            <div class="alert alert-light border mb-0">No hay compras recientes.</div>
                            <?php endif; ?>
                            <?php foreach ($compras as $c): ?>
                            <?php
                                $compraTieneComprobante = !empty($c['comprobante_path']);
                                $compraPathComprobante = $compraTieneComprobante ? (string) $c['comprobante_path'] : '';
                                $compraTipoComprobante = $c['comprobante_tipo'] ?? '';
                                $usuarioCompra = trim((string) (($c['usuario_nombre'] ?? '') . ' ' . ($c['usuario_apellido'] ?? '')));
                            ?>
                            <div class="mobile-list-item">
                                <div class="item-top">
                                    <div class="item-date"><?= date('d/m/Y', strtotime($c['fecha'])) ?></div>
                                    <div>
                                        <?php if ($c['metodo_pago'] == 'efectivo'): ?>
                                        <span class="badge bg-success">Efectivo</span>
                                        <?php else: ?>
                                        <span class="badge bg-info text-dark">Transferencia</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="item-amount">$<?= number_format($c['monto'], 0, ',', '.') ?></div>
                                <div class="item-meta">Cuenta: <?= htmlspecialchars($c['cuenta_nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="item-meta">Usuario: <?= htmlspecialchars($usuarioCompra !== '' ? $usuarioCompra : '-', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="item-desc"><?= htmlspecialchars($c['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($compraTieneComprobante): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-comprobante-modal"
                                    data-comprobante-path="<?= htmlspecialchars($compraPathComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                    data-comprobante-tipo="<?= htmlspecialchars((string) $compraTipoComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                    data-comprobante-label="compra">
                                    Ver comprobante
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="desktop-only table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                        <th>Cuenta</th>
                                        <th>Usuario</th>
                                        <th>Descripción</th>
                                        <th>Comprobante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($compras)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay compras recientes.</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php foreach ($compras as $c): ?>
                                    <?php
                                        $compraTieneComprobante = !empty($c['comprobante_path']);
                                        $compraPathComprobante = $compraTieneComprobante ? (string) $c['comprobante_path'] : '';
                                        $compraTipoComprobante = $c['comprobante_tipo'] ?? '';
                                        $compraRowClass = $compraTieneComprobante ? 'fila-comprobante' : '';
                                        $usuarioCompra = trim((string) (($c['usuario_nombre'] ?? '') . ' ' . ($c['usuario_apellido'] ?? '')));
                                    ?>
                                    <tr class="<?= $compraRowClass ?>"
                                        <?php if ($compraTieneComprobante): ?>
                                        data-comprobante-path="<?= htmlspecialchars($compraPathComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                        data-comprobante-tipo="<?= htmlspecialchars((string) $compraTipoComprobante, ENT_QUOTES, 'UTF-8') ?>"
                                        data-comprobante-label="compra"
                                        title="Toca para ver el comprobante"
                                        <?php endif; ?>>
                                        <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                                        <td>$<?= number_format($c['monto'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($c['metodo_pago'] == 'efectivo'): ?>
                                            <span class="badge bg-success">Efectivo</span>
                                            <?php else: ?>
                                            <span class="badge bg-info text-dark">Transferencia</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($c['cuenta_nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($usuarioCompra !== '' ? $usuarioCompra : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($c['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($compraTieneComprobante): ?>
                                            <a href="<?= htmlspecialchars($compraPathComprobante, ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                                                class="btn btn-sm btn-outline-primary btn-ver-comprobante">Ver</a>
                                            <div class="hint-comprobante mt-1">Tocar fila</div>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-box mb-2 text-center">
            <i class="fas fa-info-circle me-1"></i> El balance del día se actualiza
            <b>solo al generar el balance completo</b>, después de cerrar todos los turnos y registrar compras.
        </div>
    </div>

    <div class="modal fade" id="modalComprobante" tabindex="-1" aria-labelledby="modalComprobanteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalComprobanteLabel">Comprobante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-2 p-md-3">
                    <div id="comprobantePreviewWrap" class="text-center"></div>
                </div>
                <div class="modal-footer">
                    <a id="btnAbrirComprobanteNuevaPestana" href="#" target="_blank" class="btn btn-outline-primary">
                        Abrir en pestaña nueva
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php include 'modales_capital.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function generarBalance() {
        Swal.fire({
            title: '¿Generar balance del día de hoy?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'generar_balance.php';
            }
        });
    }

    // Mostrar alertas con SweetAlert2 si hay parámetros en la URL
    document.addEventListener('DOMContentLoaded', function() {
        function initCuentaToggle(formId, metodoId, cuentaWrapId, cuentaId) {
            const form = document.getElementById(formId);
            const metodo = document.getElementById(metodoId);
            const cuentaWrap = document.getElementById(cuentaWrapId);
            const cuenta = document.getElementById(cuentaId);
            if (!form || !metodo || !cuentaWrap || !cuenta) {
                return;
            }

            const toggle = () => {
                if (metodo.value === 'transferencia') {
                    cuentaWrap.style.display = 'block';
                    cuenta.required = true;
                } else {
                    cuentaWrap.style.display = 'none';
                    cuenta.required = false;
                    cuenta.value = '';
                }
            };
            toggle();
            metodo.addEventListener('change', toggle);
        }

        initCuentaToggle('formIngreso', 'metodo_ingreso', 'cuenta_ingreso_wrap', 'cuenta_ingreso');
        initCuentaToggle('formEgreso', 'metodo_egreso', 'cuenta_egreso_wrap', 'cuenta_egreso');
        initCuentaToggle('formCompra', 'metodo_compra', 'cuenta_compra_wrap', 'cuenta_compra');

        const modalComprobanteEl = document.getElementById('modalComprobante');
        const modalComprobanteTitle = document.getElementById('modalComprobanteLabel');
        const previewWrap = document.getElementById('comprobantePreviewWrap');
        const btnNuevaPestana = document.getElementById('btnAbrirComprobanteNuevaPestana');
        const modalComprobante = modalComprobanteEl ? new bootstrap.Modal(modalComprobanteEl) : null;

        function renderComprobante(path, tipo, label) {
            if (!previewWrap || !btnNuevaPestana || !modalComprobanteTitle) {
                return;
            }
            const safePath = path || '';
            const normalizedTipo = (tipo || '').toLowerCase();
            const isPdf = normalizedTipo === 'pdf' || safePath.toLowerCase().endsWith('.pdf');
            const normalizedLabel = (label || '').toLowerCase();

            previewWrap.innerHTML = '';
            btnNuevaPestana.href = safePath;
            if (normalizedLabel === 'compra') {
                modalComprobanteTitle.textContent = 'Comprobante de compra';
            } else if (normalizedLabel === 'egreso') {
                modalComprobanteTitle.textContent = 'Comprobante de egreso';
            } else {
                modalComprobanteTitle.textContent = 'Comprobante';
            }

            if (isPdf) {
                const iframe = document.createElement('iframe');
                iframe.src = safePath;
                iframe.style.width = '100%';
                iframe.style.height = '65vh';
                iframe.style.border = '0';
                iframe.setAttribute('title', 'Vista previa de comprobante PDF');
                previewWrap.appendChild(iframe);
                return;
            }

            const img = document.createElement('img');
            img.src = safePath;
            img.alt = 'Comprobante';
            img.style.maxWidth = '100%';
            img.style.maxHeight = '65vh';
            img.style.borderRadius = '8px';
            img.className = 'img-fluid';
            previewWrap.appendChild(img);
        }

        document.querySelectorAll('tr[data-comprobante-path]').forEach((row) => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.btn-ver-comprobante')) {
                    return;
                }
                const path = this.getAttribute('data-comprobante-path');
                const tipo = this.getAttribute('data-comprobante-tipo') || '';
                const label = this.getAttribute('data-comprobante-label') || '';
                if (!path || !modalComprobante) {
                    return;
                }
                renderComprobante(path, tipo, label);
                modalComprobante.show();
            });
        });

        document.querySelectorAll('.btn-comprobante-modal').forEach((btn) => {
            btn.addEventListener('click', function() {
                const path = this.getAttribute('data-comprobante-path');
                const tipo = this.getAttribute('data-comprobante-tipo') || '';
                const label = this.getAttribute('data-comprobante-label') || '';
                if (!path || !modalComprobante) {
                    return;
                }
                renderComprobante(path, tipo, label);
                modalComprobante.show();
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        const type = urlParams.get('type');
        const goto = urlParams.get('goto');
        if (msg) {
            Swal.fire({
                icon: type === 'danger' ? 'error' : (type === 'success' ? 'success' : 'info'),
                title: type === 'danger' ? 'Error' : (type === 'success' ? 'Éxito' : 'Aviso'),
                text: msg,
                confirmButtonText: 'Aceptar',
                timer: 3500
            }).then(() => {
                if (goto === 'stock') {
                    window.location.href = 'stock.php';
                }
            });
            // Limpiar la URL para que no se repita la alerta
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
    </script>
</body>

</html>

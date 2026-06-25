<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

// Filtros de periodo
$hoy = date('Y-m-d');
$modo = $_GET['modo'] ?? 'diario';
$modosPermitidos = ['diario', 'semanal', 'historico'];
if (!in_array($modo, $modosPermitidos, true)) {
    $modo = 'diario';
}

$parseFecha = static function (?string $valor, string $fallback): string {
    if ($valor === null || $valor === '') {
        return $fallback;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $valor);
    if (!$dt || $dt->format('Y-m-d') !== $valor) {
        return $fallback;
    }
    return $valor;
};

$fecha = $parseFecha($_GET['fecha'] ?? null, $hoy);
$semana = $parseFecha($_GET['semana'] ?? null, $hoy);
$desde = $parseFecha($_GET['desde'] ?? null, date('Y-m-01'));
$hasta = $parseFecha($_GET['hasta'] ?? null, $hoy);

$periodoDesde = $fecha;
$periodoHasta = $fecha;
$tituloPeriodo = 'Día';
$descripcionPeriodo = date('d/m/Y', strtotime($fecha));
$textoConsultado = 'consultado';
$articuloPeriodo = 'del';

if ($modo === 'semanal') {
    $periodoDesde = get_inicio_semana_lunes($semana);
    $periodoHasta = get_fin_semana_domingo($periodoDesde);
    $tituloPeriodo = 'Semana';
    $descripcionPeriodo = date('d/m/Y', strtotime($periodoDesde)) . ' al ' . date('d/m/Y', strtotime($periodoHasta));
    $textoConsultado = 'consultada';
    $articuloPeriodo = 'de la';
} elseif ($modo === 'historico') {
    if ($desde > $hasta) {
        [$desde, $hasta] = [$hasta, $desde];
    }
    $periodoDesde = $desde;
    $periodoHasta = $hasta;
    $tituloPeriodo = 'Histórico';
    $descripcionPeriodo = date('d/m/Y', strtotime($periodoDesde)) . ' al ' . date('d/m/Y', strtotime($periodoHasta));
    $textoConsultado = 'consultado';
}

$resumen = $modo === 'diario'
    ? calcular_resumen_balance_dia($db, $periodoDesde)
    : calcular_resumen_balance_periodo($db, $periodoDesde, $periodoHasta);
$capital_inicial_efectivo = $resumen['capital_inicial_efectivo'];
$capital_inicial_transferencia = $resumen['capital_inicial_transferencia'];
$ventas = [
    'ventas_efectivo' => $resumen['ventas_efectivo'],
    'ventas_transferencia' => $resumen['ventas_transferencia'],
];
$compras = [
    'compras_efectivo' => $resumen['compras_efectivo'],
    'compras_transferencia' => $resumen['compras_transferencia'],
];
$ingresos = [
    'ingresos_efectivo' => $resumen['ingresos_efectivo'],
    'ingresos_transferencia' => $resumen['ingresos_transferencia'],
];
$egresos = [
    'egresos_efectivo' => $resumen['egresos_efectivo'],
    'egresos_transferencia' => $resumen['egresos_transferencia'],
];
$capital_final_efectivo = $resumen['capital_final_efectivo'];
$capital_final_transferencia = $resumen['capital_final_transferencia'];
$inventario = ['valor_inventario' => $resumen['capital_productos']];

// Movimientos del periodo
$ventas_det = $db->prepare("
    SELECT t.*, u.nombre, u.apellido
    FROM transacciones t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    WHERE t.tipo = 'Venta' AND DATE(t.fecha) BETWEEN ? AND ?
    ORDER BY t.fecha DESC
");
$ventas_det->execute([$periodoDesde, $periodoHasta]);
$ventas_det = $ventas_det->fetchAll(PDO::FETCH_ASSOC);

$compras_det = $db->prepare("SELECT cm.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
    FROM compras_mercaderia cm
    LEFT JOIN usuarios u ON u.id = cm.usuario_id
    WHERE cm.fecha BETWEEN ? AND ?
    ORDER BY cm.created_at DESC, cm.fecha DESC");
$compras_det->execute([$periodoDesde, $periodoHasta]);
$compras_det = $compras_det->fetchAll(PDO::FETCH_ASSOC);

$ingresos_det = $db->prepare("SELECT cl.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
    FROM capital_liquido cl
    LEFT JOIN usuarios u ON u.id = cl.usuario_id
    WHERE cl.fecha BETWEEN ? AND ? AND cl.tipo = 'ingreso'
    ORDER BY cl.created_at DESC, cl.fecha DESC");
$ingresos_det->execute([$periodoDesde, $periodoHasta]);
$ingresos_det = $ingresos_det->fetchAll(PDO::FETCH_ASSOC);

$egresos_det = $db->prepare("SELECT cl.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido
    FROM capital_liquido cl
    LEFT JOIN usuarios u ON u.id = cl.usuario_id
    WHERE cl.fecha BETWEEN ? AND ? AND cl.tipo = 'egreso'
    ORDER BY cl.created_at DESC, cl.fecha DESC");
$egresos_det->execute([$periodoDesde, $periodoHasta]);
$egresos_det = $egresos_det->fetchAll(PDO::FETCH_ASSOC);

$recalcularFecha = $periodoHasta;
$exportQuery = http_build_query([
    'modo' => $modo,
    'fecha' => $fecha,
    'semana' => $semana,
    'desde' => $desde,
    'hasta' => $hasta,
]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Completo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-a: #0f4c75;
            --bg-b: #3282b8;
            --surface: #ffffff;
            --surface-soft: #f4f8fc;
            --border: #dbe6f2;
            --text: #102a43;
            --muted: #627d98;
            --radius: 14px;
        }

        body {
            background:
                radial-gradient(circle at 16% 14%, rgba(255, 255, 255, 0.14), transparent 36%),
                linear-gradient(145deg, var(--bg-a), var(--bg-b));
            min-height: 100vh;
            color: var(--text);
        }

        .app-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px;
        }

        .main-container {
            background: var(--surface);
            border-radius: 18px;
            box-shadow: 0 16px 36px rgba(8, 31, 52, 0.22);
            padding: 12px;
        }

        .header-balance {
            background: linear-gradient(130deg, #102a43, #1f4f75);
            color: white;
            border-radius: 14px;
            padding: 13px;
            margin-bottom: 12px;
        }

        .header-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .header-sub {
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.86);
            margin-top: 2px;
        }

        .query-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 12px;
        }

        .query-grid > * {
            min-width: 0;
        }

        .query-grid .btn {
            width: 100%;
        }

        .summary-card,
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 8px 20px rgba(11, 34, 57, 0.08);
            margin-bottom: 12px;
            overflow: hidden;
        }

        .summary-head,
        .section-head {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            background: var(--surface-soft);
        }

        .summary-title,
        .section-title {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 700;
        }

        .summary-body,
        .section-body {
            padding: 12px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .metric-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            padding: 10px;
        }

        .metric-label {
            font-size: 0.82rem;
            color: var(--muted);
            margin-bottom: 2px;
        }

        .metric-value {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .info-box {
            margin-top: 10px;
            border-radius: 10px;
            font-size: 0.93rem;
        }

        .move-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
            background: #fff;
            margin-bottom: 8px;
        }

        .move-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .move-date {
            color: var(--muted);
            font-size: 0.84rem;
        }

        .move-amount {
            font-size: 1.08rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .move-meta {
            color: var(--muted);
            font-size: 0.88rem;
            margin-bottom: 4px;
        }

        .move-desc {
            font-size: 0.92rem;
            color: #334e68;
        }

        .desktop-only {
            display: none;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            white-space: nowrap;
        }

        @media (min-width: 768px) {
            .app-shell {
                padding: 20px;
            }

            .main-container {
                padding: 18px;
            }

            .query-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .metrics-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .mobile-only {
                display: none;
            }

            .desktop-only {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <div class="main-container">
            <div class="header-balance">
                <h1 class="header-title"><i class="fas fa-balance-scale me-2"></i>Balance Completo</h1>
                <div class="header-sub"><?= htmlspecialchars($tituloPeriodo, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($textoConsultado, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($descripcionPeriodo, ENT_QUOTES, 'UTF-8') ?></div>
                <form class="query-grid" method="get">
                    <select name="modo" id="modo_balance" class="form-select">
                        <option value="diario" <?= $modo === 'diario' ? 'selected' : '' ?>>Diario</option>
                        <option value="semanal" <?= $modo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        <option value="historico" <?= $modo === 'historico' ? 'selected' : '' ?>>Histórico</option>
                    </select>
                    <div id="field_fecha" style="<?= $modo === 'diario' ? '' : 'display:none;' ?>">
                        <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div id="field_semana" style="<?= $modo === 'semanal' ? '' : 'display:none;' ?>">
                        <input type="date" class="form-control" name="semana" value="<?= htmlspecialchars($semana, ENT_QUOTES, 'UTF-8') ?>" title="Selecciona un día de la semana">
                    </div>
                    <div id="field_desde" style="<?= $modo === 'historico' ? '' : 'display:none;' ?>">
                        <input type="date" class="form-control" name="desde" value="<?= htmlspecialchars($desde, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div id="field_hasta" style="<?= $modo === 'historico' ? '' : 'display:none;' ?>">
                        <input type="date" class="form-control" name="hasta" value="<?= htmlspecialchars($hasta, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <button type="submit" class="btn btn-light"><i class="fas fa-search me-1"></i> Consultar</button>
                    <a href="generar_balance.php?fecha=<?= urlencode($recalcularFecha) ?>" class="btn btn-warning"><i class="fas fa-sync-alt me-1"></i> Recalcular día final</a>
                    <a href="exportar_balance_excel.php?<?= htmlspecialchars($exportQuery, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> Excel</a>
                    <a href="capital.php" class="btn btn-outline-light"><i class="fas fa-arrow-left me-1"></i> Volver</a>
                </form>
            </div>

            <div class="summary-card">
                <div class="summary-head">
                    <h2 class="summary-title">Resumen <?= htmlspecialchars($articuloPeriodo, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($tituloPeriodo, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($descripcionPeriodo, ENT_QUOTES, 'UTF-8') ?>)</h2>
                </div>
                <div class="summary-body">
                    <div class="metrics-grid">
                        <div class="metric-item">
                            <div class="metric-label">Capital inicial efectivo</div>
                            <div class="metric-value">$<?= number_format($capital_inicial_efectivo, 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Capital inicial transferencia</div>
                            <div class="metric-value">$<?= number_format($capital_inicial_transferencia, 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Ventas efectivo</div>
                            <div class="metric-value">$<?= number_format($ventas['ventas_efectivo'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Ventas transferencia</div>
                            <div class="metric-value">$<?= number_format($ventas['ventas_transferencia'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Compras efectivo</div>
                            <div class="metric-value">$<?= number_format($compras['compras_efectivo'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Compras transferencia</div>
                            <div class="metric-value">$<?= number_format($compras['compras_transferencia'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Ingresos de capital (efectivo)</div>
                            <div class="metric-value">$<?= number_format($ingresos['ingresos_efectivo'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Ingresos de capital (transferencia)</div>
                            <div class="metric-value">$<?= number_format($ingresos['ingresos_transferencia'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Egresos de capital (efectivo)</div>
                            <div class="metric-value">$<?= number_format($egresos['egresos_efectivo'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Egresos de capital (transferencia)</div>
                            <div class="metric-value">$<?= number_format($egresos['egresos_transferencia'], 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Capital final efectivo</div>
                            <div class="metric-value">$<?= number_format($capital_final_efectivo, 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Capital final transferencia</div>
                            <div class="metric-value">$<?= number_format($capital_final_transferencia, 0, ',', '.') ?></div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Valor inventario</div>
                            <div class="metric-value">$<?= number_format($inventario['valor_inventario'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="alert alert-info info-box mb-0">
                        El capital final se calcula con movimientos reales de <b>capital_liquido</b> del día
                        (ingresos - egresos), para evitar doble conteo entre ventas y caja.
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-head"><h3 class="section-title"><i class="fas fa-shopping-cart me-2"></i>Ventas</h3></div>
                <div class="section-body">
                    <div class="mobile-only">
                        <?php if (count($ventas_det) === 0): ?>
                        <div class="alert alert-light border mb-0">Sin ventas.</div>
                        <?php endif; ?>
                        <?php foreach ($ventas_det as $v): ?>
                        <div class="move-item">
                            <div class="move-top">
                                <div class="move-date"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></div>
                                <span class="badge bg-primary"><?= ucfirst((string) $v['metodo_pago']) ?></span>
                            </div>
                            <div class="move-amount">$<?= number_format($v['importe'], 0, ',', '.') ?></div>
                            <div class="move-meta">ID #<?= (int) $v['id'] ?></div>
                            <div class="move-desc"><?= htmlspecialchars((isset($v['nombre']) ? $v['nombre'] . ' ' . $v['apellido'] : '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="desktop-only table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr><th>ID</th><th>Fecha</th><th>Total</th><th>Método</th><th>Usuario</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventas_det as $v): ?>
                                <tr>
                                    <td><?= (int) $v['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                    <td>$<?= number_format($v['importe'], 0, ',', '.') ?></td>
                                    <td><?= ucfirst((string) $v['metodo_pago']) ?></td>
                                    <td><?= htmlspecialchars((isset($v['nombre']) ? $v['nombre'] . ' ' . $v['apellido'] : '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; if (count($ventas_det) === 0): ?>
                                <tr><td colspan="5" class="text-center">Sin ventas</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-head"><h3 class="section-title"><i class="fas fa-shopping-bag me-2"></i>Compras</h3></div>
                <div class="section-body">
                    <div class="mobile-only">
                        <?php if (count($compras_det) === 0): ?>
                        <div class="alert alert-light border mb-0">Sin compras.</div>
                        <?php endif; ?>
                        <?php foreach ($compras_det as $c): ?>
                        <?php $usuarioCompra = trim((string) (($c['usuario_nombre'] ?? '') . ' ' . ($c['usuario_apellido'] ?? ''))); ?>
                        <div class="move-item">
                            <div class="move-top">
                                <div class="move-date"><?= date('d/m/Y H:i', strtotime($c['created_at'] ?? $c['fecha'])) ?></div>
                                <span class="badge bg-info text-dark"><?= ucfirst((string) $c['metodo_pago']) ?></span>
                            </div>
                            <div class="move-amount">$<?= number_format($c['monto'], 0, ',', '.') ?></div>
                            <div class="move-meta">ID #<?= (int) $c['id'] ?></div>
                            <div class="move-meta">Usuario: <?= htmlspecialchars($usuarioCompra !== '' ? $usuarioCompra : '-', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="move-desc"><?= htmlspecialchars($c['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="desktop-only table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr><th>ID</th><th>Fecha</th><th>Monto</th><th>Método</th><th>Usuario</th><th>Descripción</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compras_det as $c): ?>
                                <?php $usuarioCompra = trim((string) (($c['usuario_nombre'] ?? '') . ' ' . ($c['usuario_apellido'] ?? ''))); ?>
                                <tr>
                                    <td><?= (int) $c['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($c['created_at'] ?? $c['fecha'])) ?></td>
                                    <td>$<?= number_format($c['monto'], 0, ',', '.') ?></td>
                                    <td><?= ucfirst((string) $c['metodo_pago']) ?></td>
                                    <td><?= htmlspecialchars($usuarioCompra !== '' ? $usuarioCompra : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($c['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; if (count($compras_det) === 0): ?>
                                <tr><td colspan="6" class="text-center">Sin compras</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-head"><h3 class="section-title"><i class="fas fa-plus-circle me-2"></i>Ingresos de Capital</h3></div>
                <div class="section-body">
                    <div class="mobile-only">
                        <?php if (count($ingresos_det) === 0): ?>
                        <div class="alert alert-light border mb-0">Sin ingresos.</div>
                        <?php endif; ?>
                        <?php foreach ($ingresos_det as $i): ?>
                        <?php $usuarioIngreso = trim((string) (($i['usuario_nombre'] ?? '') . ' ' . ($i['usuario_apellido'] ?? ''))); ?>
                        <div class="move-item">
                            <div class="move-top">
                                <div class="move-date"><?= date('d/m/Y H:i', strtotime($i['fecha'])) ?></div>
                                <span class="badge bg-success">Ingreso</span>
                            </div>
                            <div class="move-amount">$<?= number_format(((float) $i['efectivo'] + (float) $i['transferencia']), 0, ',', '.') ?></div>
                            <div class="move-meta">Ef: $<?= number_format($i['efectivo'], 0, ',', '.') ?> | Tr: $<?= number_format($i['transferencia'], 0, ',', '.') ?></div>
                            <div class="move-meta">Usuario: <?= htmlspecialchars($usuarioIngreso !== '' ? $usuarioIngreso : '-', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="move-desc"><?= htmlspecialchars($i['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="desktop-only table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr><th>ID</th><th>Fecha</th><th>Efectivo</th><th>Transferencia</th><th>Usuario</th><th>Descripción</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ingresos_det as $i): ?>
                                <?php $usuarioIngreso = trim((string) (($i['usuario_nombre'] ?? '') . ' ' . ($i['usuario_apellido'] ?? ''))); ?>
                                <tr>
                                    <td><?= (int) $i['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($i['fecha'])) ?></td>
                                    <td>$<?= number_format($i['efectivo'], 0, ',', '.') ?></td>
                                    <td>$<?= number_format($i['transferencia'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($usuarioIngreso !== '' ? $usuarioIngreso : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($i['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; if (count($ingresos_det) === 0): ?>
                                <tr><td colspan="6" class="text-center">Sin ingresos</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="section-card mb-0">
                <div class="section-head"><h3 class="section-title"><i class="fas fa-minus-circle me-2"></i>Egresos de Capital</h3></div>
                <div class="section-body">
                    <div class="mobile-only">
                        <?php if (count($egresos_det) === 0): ?>
                        <div class="alert alert-light border mb-0">Sin egresos.</div>
                        <?php endif; ?>
                        <?php foreach ($egresos_det as $e): ?>
                        <?php $usuarioEgreso = trim((string) (($e['usuario_nombre'] ?? '') . ' ' . ($e['usuario_apellido'] ?? ''))); ?>
                        <div class="move-item">
                            <div class="move-top">
                                <div class="move-date"><?= date('d/m/Y H:i', strtotime($e['fecha'])) ?></div>
                                <span class="badge bg-danger">Egreso</span>
                            </div>
                            <div class="move-amount">$<?= number_format(((float) $e['efectivo'] + (float) $e['transferencia']), 0, ',', '.') ?></div>
                            <div class="move-meta">Ef: $<?= number_format($e['efectivo'], 0, ',', '.') ?> | Tr: $<?= number_format($e['transferencia'], 0, ',', '.') ?></div>
                            <div class="move-meta">Usuario: <?= htmlspecialchars($usuarioEgreso !== '' ? $usuarioEgreso : '-', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="move-desc"><?= htmlspecialchars($e['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($e['comprobante_path'])): ?>
                            <div class="mt-2">
                                <a href="<?= htmlspecialchars((string) $e['comprobante_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-paperclip me-1"></i> Ver comprobante
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="desktop-only table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr><th>ID</th><th>Fecha</th><th>Efectivo</th><th>Transferencia</th><th>Usuario</th><th>Descripción</th><th>Comprobante</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($egresos_det as $e): ?>
                                <?php $usuarioEgreso = trim((string) (($e['usuario_nombre'] ?? '') . ' ' . ($e['usuario_apellido'] ?? ''))); ?>
                                <tr>
                                    <td><?= (int) $e['id'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($e['fecha'])) ?></td>
                                    <td>$<?= number_format($e['efectivo'], 0, ',', '.') ?></td>
                                    <td>$<?= number_format($e['transferencia'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($usuarioEgreso !== '' ? $usuarioEgreso : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($e['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if (!empty($e['comprobante_path'])): ?>
                                        <a href="<?= htmlspecialchars((string) $e['comprobante_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; if (count($egresos_det) === 0): ?>
                                <tr><td colspan="7" class="text-center">Sin egresos</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modoSelect = document.getElementById('modo_balance');
        const fieldFecha = document.getElementById('field_fecha');
        const fieldSemana = document.getElementById('field_semana');
        const fieldDesde = document.getElementById('field_desde');
        const fieldHasta = document.getElementById('field_hasta');
        if (!modoSelect || !fieldFecha || !fieldSemana || !fieldDesde || !fieldHasta) {
            return;
        }

        const inputFecha = fieldFecha.querySelector('input');
        const inputSemana = fieldSemana.querySelector('input');
        const inputDesde = fieldDesde.querySelector('input');
        const inputHasta = fieldHasta.querySelector('input');

        const setVisibility = () => {
            const mode = modoSelect.value;
            fieldFecha.style.display = mode === 'diario' ? 'block' : 'none';
            fieldSemana.style.display = mode === 'semanal' ? 'block' : 'none';
            fieldDesde.style.display = mode === 'historico' ? 'block' : 'none';
            fieldHasta.style.display = mode === 'historico' ? 'block' : 'none';

            if (inputFecha) inputFecha.required = mode === 'diario';
            if (inputSemana) inputSemana.required = mode === 'semanal';
            if (inputDesde) inputDesde.required = mode === 'historico';
            if (inputHasta) inputHasta.required = mode === 'historico';
        };

        setVisibility();
        modoSelect.addEventListener('change', setVisibility);
    });
    </script>
</body>
</html> 

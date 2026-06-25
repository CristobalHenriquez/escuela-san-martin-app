<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';

// Eliminar turno si se recibe POST con id y código correcto
if (isset($_POST['eliminar_turno_id'], $_POST['codigo_eliminar']) && $_POST['codigo_eliminar'] === '1203') {
    if (empty($_SESSION['usuario_admin']) || (int) $_SESSION['usuario_admin'] !== 1) {
        http_response_code(403);
        exit('No autorizado');
    }
    $id = intval($_POST['eliminar_turno_id']);
    $db->prepare('DELETE FROM turnos WHERE id = ?')->execute([$id]);
    $db->prepare('DELETE FROM transacciones WHERE turno_id = ?')->execute([$id]);
    header('Location: reportes.php');
    exit;
}

// Paginación
$por_pagina = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $por_pagina;
$modoVista = trim((string) ($_GET['modo'] ?? 'dia'));
if (!in_array($modoVista, ['dia', 'turno'], true)) {
    $modoVista = 'dia';
}

// Filtro por fechas
$hoy = date('Y-m-d');
$fecha_desde = trim((string) ($_GET['desde'] ?? ''));
$fecha_hasta = trim((string) ($_GET['hasta'] ?? ''));
if (!$fecha_desde && !$fecha_hasta) {
    $fecha_desde = $hoy;
    $fecha_hasta = $hoy;
}

if ($fecha_desde && $fecha_hasta && $fecha_desde > $fecha_hasta) {
    [$fecha_desde, $fecha_hasta] = [$fecha_hasta, $fecha_desde];
}

$whereParts = ['t.fecha_cierre IS NOT NULL'];
$params = [];
if ($fecha_desde !== '') {
    $whereParts[] = 't.fecha_cierre >= ?';
    $params[] = $fecha_desde . ' 00:00:00';
}
if ($fecha_hasta !== '') {
    $whereParts[] = 't.fecha_cierre <= ?';
    $params[] = $fecha_hasta . ' 23:59:59';
}
$where = 'WHERE ' . implode(' AND ', $whereParts);

$resumen_stmt = $db->prepare("SELECT
    COUNT(DISTINCT t.id) AS total_turnos,
    COALESCE(SUM(CASE WHEN tr.tipo = 'Venta' AND tr.metodo_pago = 'Efectivo' THEN tr.importe ELSE 0 END), 0) AS total_ventas_efectivo,
    COALESCE(SUM(CASE WHEN tr.tipo = 'Venta' AND tr.metodo_pago = 'Transferencia' THEN tr.importe ELSE 0 END), 0) AS total_ventas_transferencia
    FROM turnos t
    LEFT JOIN transacciones tr ON tr.turno_id = t.id
    $where");
$resumen_stmt->execute($params);
$resumen = $resumen_stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_turnos' => 0,
    'total_ventas_efectivo' => 0,
    'total_ventas_transferencia' => 0,
];

$turnos = [];
$dias = [];
$turnosPorDia = [];

if ($modoVista === 'turno') {
    $total_stmt = $db->prepare("SELECT COUNT(*) FROM turnos t $where");
    $total_stmt->execute($params);
    $total_reportes = (int) $total_stmt->fetchColumn();
    $total_paginas = max(1, (int) ceil($total_reportes / $por_pagina));

    // Listar reportes paginados por turno
    $turnos = $db->prepare("SELECT t.*, g.nombre AS grupo_nombre
        FROM turnos t
        LEFT JOIN grupos g ON g.id = t.grupo_id
        $where
        ORDER BY t.id DESC
        LIMIT $por_pagina OFFSET $offset");
    $turnos->execute($params);
    $turnos = $turnos->fetchAll(PDO::FETCH_ASSOC);
} else {
    $total_stmt = $db->prepare("SELECT COUNT(DISTINCT DATE(t.fecha_cierre)) FROM turnos t $where");
    $total_stmt->execute($params);
    $total_reportes = (int) $total_stmt->fetchColumn();
    $total_paginas = max(1, (int) ceil($total_reportes / $por_pagina));

    $dias_stmt = $db->prepare("SELECT
        DATE(t.fecha_cierre) AS fecha,
        COUNT(DISTINCT t.id) AS turnos_cerrados,
        COALESCE(SUM(t.saldo_inicial), 0) AS saldo_inicial_total,
        COALESCE(SUM(t.saldo_cierre), 0) AS saldo_cierre_total,
        COALESCE(SUM(tv.ventas_efectivo), 0) AS ventas_efectivo,
        COALESCE(SUM(tv.ventas_transferencia), 0) AS ventas_transferencia
        FROM turnos t
        LEFT JOIN (
            SELECT
                tr.turno_id,
                COALESCE(SUM(CASE WHEN tr.tipo = 'Venta' AND tr.metodo_pago = 'Efectivo' THEN tr.importe ELSE 0 END), 0) AS ventas_efectivo,
                COALESCE(SUM(CASE WHEN tr.tipo = 'Venta' AND tr.metodo_pago = 'Transferencia' THEN tr.importe ELSE 0 END), 0) AS ventas_transferencia
            FROM transacciones tr
            GROUP BY tr.turno_id
        ) tv ON tv.turno_id = t.id
        $where
        GROUP BY DATE(t.fecha_cierre)
        ORDER BY DATE(t.fecha_cierre) DESC
        LIMIT $por_pagina OFFSET $offset");
    $dias_stmt->execute($params);
    $dias = $dias_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($dias) > 0) {
        $fechas = array_map(static fn($d) => (string) $d['fecha'], $dias);
        $placeholders = implode(',', array_fill(0, count($fechas), '?'));
        $turnos_dia_stmt = $db->prepare("SELECT
            t.id,
            t.fecha_apertura,
            t.fecha_cierre,
            t.saldo_inicial,
            t.saldo_cierre,
            g.nombre AS grupo_nombre,
            COALESCE(SUM(CASE WHEN tr.tipo = 'Venta' AND tr.metodo_pago = 'Efectivo' THEN tr.importe ELSE 0 END), 0) AS ventas_efectivo,
            COALESCE(SUM(CASE WHEN tr.tipo = 'Venta' AND tr.metodo_pago = 'Transferencia' THEN tr.importe ELSE 0 END), 0) AS ventas_transferencia
            FROM turnos t
            LEFT JOIN grupos g ON g.id = t.grupo_id
            LEFT JOIN transacciones tr ON tr.turno_id = t.id
            WHERE DATE(t.fecha_cierre) IN ($placeholders)
            GROUP BY t.id
            ORDER BY t.fecha_cierre DESC, t.id DESC");
        $turnos_dia_stmt->execute($fechas);
        foreach ($turnos_dia_stmt->fetchAll(PDO::FETCH_ASSOC) as $td) {
            $fechaKey = date('Y-m-d', strtotime((string) $td['fecha_cierre']));
            if (!isset($turnosPorDia[$fechaKey])) {
                $turnosPorDia[$fechaKey] = [];
            }
            $turnosPorDia[$fechaKey][] = $td;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Turnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        background: #f3f3fa;
    }

    .main-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        margin: 30px auto;
        width: 90vw;
        max-width: 700px;
        min-width: 0;
        overflow: hidden;
        padding: 0 1.2rem 1.2rem 1.2rem;
    }

    @media (max-width: 600px) {
        .main-container {
            width: 100vw;
            max-width: 100vw;
            margin: 0;
            border-radius: 0;
            padding: 0 0.5rem 1rem 0.5rem;
            min-height: 100vh;
        }

        .table-responsive {
            border-radius: 0 !important;
        }

        .header {
            border-radius: 0 !important;
        }
    }

    .header {
        background: linear-gradient(45deg, #2c3e50, #34495e);
        color: white;
        padding: 20px;
        text-align: center;
    }

    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-detalle {
        background: linear-gradient(45deg, #3498db, #2980b9);
        border: none;
        color: white;
    }

    /* Mejorar visualización del modal y tablas cargadas por AJAX */
    #detalleTurnoBody table {
        font-size: 0.95rem;
    }

    #detalleTurnoBody h5 {
        margin-top: 1rem;
    }

    #detalleTurnoBody .accordion-button {
        font-size: 1rem;
    }

    #detalleTurnoBody .accordion-item {
        border-radius: 8px;
        overflow: hidden;
    }

    #detalleTurnoBody .table {
        margin-bottom: 0.5rem;
    }

    .kpi-card {
        border: 0;
        border-radius: 12px;
        padding: 0.9rem;
        color: #0f172a;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .kpi-title {
        font-size: 0.85rem;
        color: #475569;
        margin-bottom: 0.25rem;
    }

    .kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0;
    }

    .badge-grupo {
        background-color: #e0f2fe;
        color: #0c4a6e;
        font-weight: 600;
    }

    .acciones-wrap {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .vista-switch {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 0.9rem;
    }

    .vista-switch .btn {
        border-radius: 999px;
        font-weight: 600;
    }

    .dia-card {
        background: #fff;
        border: 1px solid #dbe5ef;
        border-radius: 14px;
        box-shadow: 0 2px 9px rgba(15, 23, 42, 0.05);
        margin-bottom: 0.85rem;
        overflow: hidden;
    }

    .dia-card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
        padding: 0.75rem 0.85rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .dia-card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .dia-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.45rem;
        padding: 0.65rem 0.85rem;
        border-bottom: 1px solid #e5edf5;
    }

    .dia-kpi {
        background: #f8fbff;
        border: 1px solid #dce7f3;
        border-radius: 10px;
        padding: 0.48rem 0.55rem;
    }

    .dia-kpi .label {
        font-size: 0.73rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.16rem;
    }

    .dia-kpi .value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }

    .dia-turnos-list {
        padding: 0.65rem 0.85rem 0.85rem 0.85rem;
        display: grid;
        gap: 0.55rem;
    }

    .dia-turno-item {
        border: 1px solid #dce7f3;
        border-radius: 11px;
        padding: 0.6rem 0.68rem;
        background: #fff;
    }

    .dia-turno-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.3rem;
    }

    .dia-turno-sub {
        color: #475569;
        font-size: 0.88rem;
        margin-bottom: 0.35rem;
    }

    .dia-turno-meta {
        display: flex;
        gap: 0.45rem;
        flex-wrap: wrap;
        margin-bottom: 0.4rem;
    }

    .dia-turno-meta span {
        background: #f1f5f9;
        border-radius: 999px;
        padding: 0.18rem 0.52rem;
        font-size: 0.8rem;
        color: #334155;
        font-weight: 600;
    }

    #modalDetalle .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
    }

    #modalDetalle .modal-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #fff;
        border-bottom: 0;
    }

    #modalDetalle .modal-header .btn-close {
        filter: invert(1) grayscale(1);
    }

    #modalDetalle .modal-body {
        background: #f8fafc;
    }

    #modalDetalle .modal-footer {
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }

    #modalDetalle .btn-modal-action {
        min-width: 170px;
    }

    #detalleTurnoBody .detalle-turno {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    #detalleTurnoBody .detalle-card {
        background: #fff;
        border: 1px solid #dce6f1;
        border-radius: 14px;
        padding: 0.85rem 0.95rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    #detalleTurnoBody .detalle-title {
        font-size: 1.03rem;
        margin: 0 0 0.5rem 0;
        color: #0f172a;
        font-weight: 700;
    }

    #detalleTurnoBody .meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    #detalleTurnoBody .meta-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        padding: 0.25rem 0.62rem;
        background: #eef4ff;
        color: #1e3a8a;
        font-size: 0.82rem;
        font-weight: 600;
    }

    #detalleTurnoBody .resumen-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.55rem;
    }

    #detalleTurnoBody .resumen-item {
        border: 1px solid #dbe5ef;
        border-radius: 12px;
        padding: 0.58rem 0.65rem;
        background: #fff;
    }

    #detalleTurnoBody .resumen-item .label {
        font-size: 0.74rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.18rem;
    }

    #detalleTurnoBody .resumen-item .value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }

    #detalleTurnoBody .resumen-item.ok {
        background: #ecfdf5;
        border-color: #a7f3d0;
    }

    #detalleTurnoBody .resumen-item.warn {
        background: #fef2f2;
        border-color: #fecaca;
    }

    #detalleTurnoBody .venta-accordion .accordion-item {
        border: 1px solid #dbe5ef;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 0.55rem;
    }

    #detalleTurnoBody .venta-accordion .accordion-button {
        background: #fff;
        box-shadow: none;
        padding: 0.65rem 0.8rem;
    }

    #detalleTurnoBody .venta-accordion .accordion-button:not(.collapsed) {
        background: #eff6ff;
        color: #1d4ed8;
    }

    #detalleTurnoBody .venta-head {
        display: flex;
        width: 100%;
        justify-content: space-between;
        align-items: center;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    #detalleTurnoBody .venta-left {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
        font-weight: 600;
    }

    #detalleTurnoBody .venta-total {
        font-weight: 700;
        font-size: 0.98rem;
        color: #0f172a;
    }

    @media (max-width: 768px) {
        #modalDetalle .modal-content {
            border-radius: 0;
        }

        #modalDetalle .modal-body {
            padding: 0.75rem !important;
        }

        #modalDetalle .modal-footer {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.45rem;
            padding: 0.75rem;
        }

        #modalDetalle .btn-modal-action,
        #modalDetalle .modal-footer .btn {
            width: 100%;
            min-width: 0;
        }

        #detalleTurnoBody .resumen-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dia-kpis {
            grid-template-columns: 1fr;
        }

        .tabla-reportes thead {
            display: none;
        }

        .tabla-reportes tbody tr {
            display: block;
            border: 1px solid #dbe3ec;
            border-radius: 12px;
            padding: 0.7rem 0.75rem;
            margin-bottom: 0.7rem;
            background: #ffffff;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
        }

        .tabla-reportes tbody td {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.8rem;
            border: 0;
            padding: 0.28rem 0;
            font-size: 0.95rem;
        }

        .tabla-reportes tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #334155;
            min-width: 100px;
            flex-shrink: 0;
        }

        .tabla-reportes tbody td.td-acciones {
            display: block;
            padding-top: 0.45rem;
        }

        .tabla-reportes tbody td.td-acciones::before {
            display: block;
            margin-bottom: 0.35rem;
        }

        .tabla-reportes .acciones-wrap .btn {
            flex: 1 1 auto;
            min-width: 112px;
        }

        .tabla-reportes tbody td.td-sin-datos {
            display: block;
            text-align: center;
        }

        .tabla-reportes tbody td.td-sin-datos::before {
            content: '';
            display: none;
        }

        #detalleTurnoBody .table-detalle-mobile thead {
            display: none;
        }

        #detalleTurnoBody .table-detalle-mobile tbody tr {
            display: block;
            border: 1px solid #dbe3ec;
            border-radius: 10px;
            padding: 0.55rem 0.65rem;
            margin-bottom: 0.55rem;
            background: #fff;
        }

        #detalleTurnoBody .table-detalle-mobile tbody td {
            display: flex;
            justify-content: space-between;
            gap: 0.7rem;
            border: 0;
            padding: 0.2rem 0;
        }

        #detalleTurnoBody .table-detalle-mobile tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #334155;
            min-width: 86px;
        }

        #detalleTurnoBody .table-detalle-mobile tbody td .text-end {
            text-align: left !important;
        }
    }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Reportes de Turnos</h1>
            <span class="me-3 fw-bold text-light" style="font-size:1rem;">
                <i class="fas fa-user"></i> <?= $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido'] ?>
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
            <a href="index.php" class="btn btn-secondary mt-2"><i class="fas fa-arrow-left"></i> Volver al POS</a>
        </div>
        <div class="container-fluid p-4">
            <form class="row g-3 mb-4" method="get" id="filtroForm">
                <input type="hidden" name="modo" value="<?= htmlspecialchars($modoVista, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12 col-md-4">
                    <label for="desde" class="form-label">Desde</label>
                    <input type="date" class="form-control" name="desde" id="desde"
                        value="<?=htmlspecialchars($fecha_desde)?>">
                </div>
                <div class="col-12 col-md-4">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input type="date" class="form-control" name="hasta" id="hasta"
                        value="<?=htmlspecialchars($fecha_hasta)?>">
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                    <button type="button" class="btn btn-outline-secondary w-100" id="btnHoy"><i
                            class="fas fa-calendar-day"></i> Hoy</button>
                </div>
            </form>
            <div class="vista-switch">
                <a href="?<?= http_build_query(array_merge($_GET, ['modo' => 'dia', 'page' => 1])) ?>"
                    class="btn <?= $modoVista === 'dia' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-calendar-day me-1"></i> Vista por día
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['modo' => 'turno', 'page' => 1])) ?>"
                    class="btn <?= $modoVista === 'turno' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-clock me-1"></i> Vista por turno
                </a>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-title">Turnos cerrados</div>
                        <p class="kpi-value"><?= number_format((float)($resumen['total_turnos'] ?? 0), 0, ',', '.') ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-title">Ventas efectivo</div>
                        <p class="kpi-value">$<?= number_format((float)($resumen['total_ventas_efectivo'] ?? 0), 0, ',', '.') ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-title">Ventas transferencia</div>
                        <p class="kpi-value">$<?= number_format((float)($resumen['total_ventas_transferencia'] ?? 0), 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <?php if ($modoVista === 'dia'): ?>
            <?php if (count($dias) === 0): ?>
            <div class="alert alert-info">No hay días con turnos cerrados en el rango seleccionado.</div>
            <?php endif; ?>
            <?php foreach ($dias as $dia): ?>
            <?php
                $fechaDia = (string) $dia['fecha'];
                $turnosDelDia = $turnosPorDia[$fechaDia] ?? [];
                $turnosCount = (int) ($dia['turnos_cerrados'] ?? 0);
            ?>
            <div class="dia-card">
                <div class="dia-card-header">
                    <h6 class="dia-card-title"><?= date('d/m/Y', strtotime($fechaDia)) ?></h6>
                    <span class="badge <?= $turnosCount === 2 ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $turnosCount ?> turno<?= $turnosCount === 1 ? '' : 's' ?>
                    </span>
                </div>
                <div class="dia-kpis">
                    <div class="dia-kpi">
                        <div class="label">Ventas efectivo</div>
                        <div class="value">$<?= number_format((float) ($dia['ventas_efectivo'] ?? 0), 0, ',', '.') ?></div>
                    </div>
                    <div class="dia-kpi">
                        <div class="label">Ventas transferencia</div>
                        <div class="value">$<?= number_format((float) ($dia['ventas_transferencia'] ?? 0), 0, ',', '.') ?></div>
                    </div>
                    <div class="dia-kpi">
                        <div class="label">Cierre total caja</div>
                        <div class="value">$<?= number_format((float) ($dia['saldo_cierre_total'] ?? 0), 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="dia-turnos-list">
                    <?php foreach ($turnosDelDia as $t): ?>
                    <div class="dia-turno-item">
                        <div class="dia-turno-top">
                            <strong>Turno #<?= (int) $t['id'] ?></strong>
                            <span class="badge badge-grupo"><?= htmlspecialchars($t['grupo_nombre'] ?: 'Sin grupo', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="dia-turno-sub">
                            <?= date('H:i', strtotime($t['fecha_apertura'])) ?> - <?= date('H:i', strtotime($t['fecha_cierre'])) ?>
                        </div>
                        <div class="dia-turno-meta">
                            <span>Ef: $<?= number_format((float) ($t['ventas_efectivo'] ?? 0), 0, ',', '.') ?></span>
                            <span>Tr: $<?= number_format((float) ($t['ventas_transferencia'] ?? 0), 0, ',', '.') ?></span>
                            <span>Cierre: $<?= number_format((float) ($t['saldo_cierre'] ?? 0), 0, ',', '.') ?></span>
                        </div>
                        <button type="button" class="btn btn-detalle btn-sm w-100" data-bs-toggle="modal"
                            data-bs-target="#modalDetalle" onclick="cargarDetalleTurno(<?= (int) $t['id'] ?>)">
                            Ver detalle
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($turnosDelDia) === 0): ?>
                    <div class="text-muted">Sin turnos detallados para este día.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped tabla-reportes">
                    <thead class="table-dark">
                        <tr>
                            <th>Inicio</th>
                            <th>Grupo</th>
                            <th>Saldo inicial</th>
                            <th>Saldo cierre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($turnos) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4 td-sin-datos">No hay turnos cerrados en el rango seleccionado.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach($turnos as $t): ?>
                        <tr>
                            <td data-label="Inicio"><?=date('d/m/Y H:i', strtotime($t['fecha_apertura']))?></td>
                            <td data-label="Grupo">
                                <span class="badge badge-grupo">
                                    <?= htmlspecialchars($t['grupo_nombre'] ?: 'Sin grupo', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td data-label="Saldo inicial">$<?=number_format((float)$t['saldo_inicial'],0)?></td>
                            <td data-label="Saldo cierre">$<?=number_format((float)$t['saldo_cierre'],0)?></td>
                            <td data-label="Acciones" class="td-acciones">
                                <div class="acciones-wrap">
                                    <button type="button" class="btn btn-detalle btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#modalDetalle" onclick="cargarDetalleTurno(<?=$t['id']?>)">
                                        Detalle
                                    </button>
                                    <?php if (!empty($_SESSION['usuario_admin']) && (int) $_SESSION['usuario_admin'] === 1): ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                        onclick="eliminarTurno(<?=$t['id']?>)">Eliminar</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación de reportes">
                <ul class="pagination justify-content-center my-3">
                    <li class="page-item<?=($page<=1?' disabled':'')?>">
                        <a class="page-link"
                            href="?<?=http_build_query(array_merge($_GET, ['page'=>$page-1]))?>">Anterior</a>
                    </li>
                    <?php for($i=1;$i<=$total_paginas;$i++): ?>
                    <li class="page-item<?=($i==$page?' active':'')?>">
                        <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>$i]))?>"><?=$i?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item<?=($page>=$total_paginas?' disabled':'')?>">
                        <a class="page-link"
                            href="?<?=http_build_query(array_merge($_GET, ['page'=>$page+1]))?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detalle de Turno -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleLabel"><i class="fas fa-info-circle"></i> Detalle del Turno
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-3" id="detalleTurnoBody">
                    <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-modal-action" id="btnExportarExcel"><i
                            class="fas fa-file-excel"></i> Exportar a Excel</button>
                    <button type="button" class="btn btn-secondary btn-modal-action" onclick="window.print()"><i
                            class="fas fa-print"></i> Imprimir</button>
                    <button type="button" class="btn btn-danger btn-modal-action" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let turnoSeleccionado = null;

    function cargarDetalleTurno(turnoId) {
        turnoSeleccionado = turnoId;
        document.getElementById('detalleTurnoBody').innerHTML =
            '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
        fetch('reporte_turno_detalle.php?id=' + turnoId)
            .then(r => {
                if (!r.ok) throw new Error('No se pudo cargar el detalle.');
                return r.text();
            })
            .then(html => {
                document.getElementById('detalleTurnoBody').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('detalleTurnoBody').innerHTML =
                    '<div class="alert alert-danger">Error al cargar el detalle del turno.</div>';
            });
    }
    // Exportar a Excel del turno seleccionado
    document.getElementById('btnExportarExcel').onclick = function() {
        if (turnoSeleccionado) {
            window.open('exportar_turno_excel.php?id=' + turnoSeleccionado, '_blank');
        } else {
            alert('Primero selecciona un turno.');
        }
    };
    // Botón Hoy: pone la fecha de hoy en ambos campos y envía el formulario
    document.getElementById('btnHoy').onclick = function() {
        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');
        const hoyStr = `${yyyy}-${mm}-${dd}`;
        document.getElementById('desde').value = hoyStr;
        document.getElementById('hasta').value = hoyStr;
        document.getElementById('filtroForm').submit();
    };

    function eliminarTurno(turnoId) {
        Swal.fire({
            title: 'Eliminar reporte',
            text: 'Para eliminar este reporte, ingresa el código de seguridad.',
            input: 'password',
            inputLabel: 'Código de seguridad',
            inputPlaceholder: 'Ingresa el código',
            inputAttributes: {
                maxlength: 10,
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) return 'Debes ingresar el código';
                if (value !== '1203') return 'Código incorrecto';
            }
        }).then((result) => {
            if (result.isConfirmed && result.value === '1203') {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Esta acción eliminará el reporte y no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                }).then((r2) => {
                    if (r2.isConfirmed) {
                        // Crear y enviar formulario oculto
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '';
                        form.innerHTML = '<input type="hidden" name="eliminar_turno_id" value="' +
                            turnoId + '">' +
                            '<input type="hidden" name="codigo_eliminar" value="1203">';
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
    }
    </script>
</body>

</html>

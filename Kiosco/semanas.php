<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_super_admin']) && isset($_SESSION['usuario_id'])) {
    $stmtRol = $db->prepare("SELECT es_admin, super_admin FROM usuarios WHERE id = ? LIMIT 1");
    $stmtRol->execute([(int) $_SESSION['usuario_id']]);
    $rol = $stmtRol->fetch(PDO::FETCH_ASSOC) ?: [];
    $_SESSION['usuario_admin'] = (int) ($rol['es_admin'] ?? 0);
    $_SESSION['usuario_super_admin'] = (int) ($rol['super_admin'] ?? 0);
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_super_admin']) || (int) $_SESSION['usuario_super_admin'] !== 1) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$error = '';
$hoy = date('Y-m-d');
$fechaInicioDefault = get_inicio_semana_lunes($hoy);
$fechaFinDefault = get_fin_semana_domingo($fechaInicioDefault);
$gruposActivos = get_grupos_activos($db);
$usuarioNombre = trim((string) ($_SESSION['usuario_nombre'] ?? ''));
$usuarioApellido = trim((string) ($_SESSION['usuario_apellido'] ?? ''));
$usuarioDisplay = trim($usuarioNombre . ' ' . $usuarioApellido);
if ($usuarioDisplay === '') {
    $usuarioDisplay = 'Super Admin';
}

$inputFechaInicio = $_POST['fecha_inicio'] ?? $fechaInicioDefault;
$inputFechaFin = $_POST['fecha_fin'] ?? $fechaFinDefault;
$inputGrupoId = isset($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : 0;
$inputObservaciones = trim($_POST['observaciones'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_semana'])) {
        $fechaInicioRaw = trim((string) ($_POST['fecha_inicio'] ?? ''));
        $fechaFinRaw = trim((string) ($_POST['fecha_fin'] ?? ''));
        $grupoId = isset($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : 0;
        $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

        $fechaInicio = DateTime::createFromFormat('Y-m-d', $fechaInicioRaw);
        $fechaFin = DateTime::createFromFormat('Y-m-d', $fechaFinRaw);
        $fechaInicioValida = $fechaInicio && $fechaInicio->format('Y-m-d') === $fechaInicioRaw;
        $fechaFinValida = $fechaFin && $fechaFin->format('Y-m-d') === $fechaFinRaw;

        if (!$fechaInicioValida) {
            $error = 'La fecha de inicio es invalida.';
        } elseif (!$fechaFinValida) {
            $error = 'La fecha de cierre es invalida.';
        } elseif ($fechaFin < $fechaInicio) {
            $error = 'La fecha de cierre no puede ser menor a la fecha de inicio.';
        } elseif ($grupoId <= 0 || !is_grupo_activo_valido($db, $grupoId)) {
            $error = 'Debes seleccionar un grupo activo.';
        } else {
            $fechaInicioYmd = $fechaInicio->format('Y-m-d');
            $fechaFinYmd = $fechaFin->format('Y-m-d');

            $stmtOverlap = $db->prepare("SELECT COUNT(*)
                FROM semanas_operativas
                WHERE estado <> 'cerrada'
                  AND NOT (fecha_fin < ? OR fecha_inicio > ?)");
            $stmtOverlap->execute([$fechaInicioYmd, $fechaFinYmd]);
            $haySolape = (int) $stmtOverlap->fetchColumn() > 0;

            if ($haySolape) {
                $error = 'Ya existe una semana asignada que se superpone con ese rango.';
            } else {
                $estado = 'planificada';
                if ($hoy >= $fechaInicioYmd && $hoy <= $fechaFinYmd) {
                    $estado = 'activa';
                } elseif ($hoy > $fechaFinYmd) {
                    $estado = 'cerrada';
                }

                try {
                    $db->beginTransaction();

                    if ($estado === 'activa') {
                        $db->exec("UPDATE semanas_operativas SET estado = 'planificada' WHERE estado = 'activa'");
                    }

                    $stmtInsert = $db->prepare("INSERT INTO semanas_operativas
                        (fecha_inicio, fecha_fin, grupo_id, estado, observaciones, created_by)
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtInsert->execute([
                        $fechaInicioYmd,
                        $fechaFinYmd,
                        $grupoId,
                        $estado,
                        $observaciones !== '' ? $observaciones : null,
                        (int) $_SESSION['usuario_id'],
                    ]);

                    $db->commit();
                    $mensaje = 'Semana operativa creada correctamente.';
                    $inputFechaInicio = date('Y-m-d', strtotime($fechaFinYmd . ' +1 day'));
                    $inputFechaFin = date('Y-m-d', strtotime($inputFechaInicio . ' +6 days'));
                    $inputGrupoId = 0;
                    $inputObservaciones = '';
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $error = 'No se pudo crear la semana: ' . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['activar_semana_id'])) {
        $semanaId = (int) $_POST['activar_semana_id'];
        $stmtSemana = $db->prepare("SELECT id, estado FROM semanas_operativas WHERE id = ?");
        $stmtSemana->execute([$semanaId]);
        $semana = $stmtSemana->fetch(PDO::FETCH_ASSOC);

        if (!$semana) {
            $error = 'La semana seleccionada no existe.';
        } elseif ((string) $semana['estado'] === 'cerrada') {
            $error = 'No se puede activar una semana cerrada.';
        } else {
            try {
                $db->beginTransaction();
                $db->exec("UPDATE semanas_operativas SET estado = 'planificada' WHERE estado = 'activa'");
                $db->prepare("UPDATE semanas_operativas SET estado = 'activa' WHERE id = ?")->execute([$semanaId]);
                $db->commit();
                $mensaje = 'Semana activada correctamente.';
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'No se pudo activar la semana: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['cerrar_semana_id'])) {
        $semanaId = (int) $_POST['cerrar_semana_id'];
        $stmtSemana = $db->prepare("SELECT id, estado FROM semanas_operativas WHERE id = ?");
        $stmtSemana->execute([$semanaId]);
        $semana = $stmtSemana->fetch(PDO::FETCH_ASSOC);

        if (!$semana) {
            $error = 'La semana seleccionada no existe.';
        } elseif ((string) $semana['estado'] === 'cerrada') {
            $error = 'La semana ya estaba cerrada.';
        } else {
            $stmtCerrar = $db->prepare("UPDATE semanas_operativas SET estado = 'cerrada', closed_by = ? WHERE id = ?");
            $stmtCerrar->execute([(int) $_SESSION['usuario_id'], $semanaId]);
            $mensaje = 'Semana cerrada correctamente.';
        }
    }
}

$semanaActual = get_semana_operativa_actual($db, $hoy);
$stmtSemanas = $db->query("SELECT so.*, g.nombre AS grupo_nombre,
    CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')) AS creado_por_nombre,
    CONCAT(COALESCE(uc.nombre, ''), ' ', COALESCE(uc.apellido, '')) AS cerrado_por_nombre
    FROM semanas_operativas so
    INNER JOIN grupos g ON g.id = so.grupo_id
    LEFT JOIN usuarios u ON u.id = so.created_by
    LEFT JOIN usuarios uc ON uc.id = so.closed_by
    ORDER BY so.fecha_inicio DESC, so.id DESC
    LIMIT 32");
$semanas = $stmtSemanas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semanas Operativas | Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-a: #0f4c75;
            --bg-b: #3282b8;
            --panel: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #dbe4ee;
            --ok: #198754;
            --warn: #fd7e14;
            --danger: #dc3545;
            --info: #0dcaf0;
            --radius: 14px;
        }
        body {
            background: linear-gradient(140deg, var(--bg-a), var(--bg-b));
            min-height: 100vh;
            color: var(--text);
        }
        .page-shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 12px;
        }
        .panel {
            background: var(--panel);
            border-radius: var(--radius);
            box-shadow: 0 14px 28px rgba(3, 22, 43, 0.24);
            overflow: hidden;
        }
        .panel-header {
            background: linear-gradient(140deg, #102a43, #1f4f75);
            color: #fff;
            padding: 14px 16px;
        }
        .panel-body {
            padding: 14px;
        }
        .metric-box {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 12px;
            background: #f7fafc;
        }
        .metric-label {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 2px;
        }
        .metric-value {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .badge-state {
            font-size: 0.75rem;
            padding: 0.35rem 0.5rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .state-activa {
            background: rgba(25, 135, 84, 0.12);
            color: #0f5132;
        }
        .state-planificada {
            background: rgba(13, 202, 240, 0.16);
            color: #055160;
        }
        .state-cerrada {
            background: rgba(220, 53, 69, 0.1);
            color: #842029;
        }
        .week-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            background: #fff;
            margin-bottom: 10px;
        }
        .week-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .week-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }
        .week-sub {
            font-size: 0.92rem;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .week-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .desktop-only {
            display: none;
        }
        @media (min-width: 768px) {
            .page-shell {
                padding: 20px;
            }
            .panel-body {
                padding: 20px;
            }
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
    <div class="page-shell">
        <div class="panel">
            <div class="panel-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h1 class="h4 mb-1"><i class="fas fa-calendar-week"></i> Semanas Operativas</h1>
                        <div class="small">Panel Super Admin · <?= htmlspecialchars($usuarioDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Inicio</a>
                        <a href="usuarios.php" class="btn btn-outline-light btn-sm"><i class="fas fa-users"></i> Usuarios</a>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <?php if ($mensaje !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="metric-box">
                            <div class="metric-label">Hoy</div>
                            <div class="metric-value"><?= date('d/m/Y', strtotime($hoy)) ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="metric-box">
                            <div class="metric-label">Semana activa</div>
                            <div class="metric-value">
                                <?= $semanaActual ? htmlspecialchars((string) $semanaActual['grupo_nombre'], ENT_QUOTES, 'UTF-8') : 'Sin asignar' ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="metric-box">
                            <div class="metric-label">Rango</div>
                            <div class="metric-value" style="font-size:1rem;">
                                <?php if ($semanaActual): ?>
                                <?= date('d/m', strtotime($semanaActual['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($semanaActual['fecha_fin'])) ?>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="post" class="border rounded-3 p-3 mb-4">
                    <input type="hidden" name="crear_semana" value="1">
                    <h2 class="h5 mb-3">Asignar nueva semana</h2>
                    <div class="row g-2">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Inicio</label>
                            <input type="date" id="fecha_inicio_input" name="fecha_inicio" class="form-control" required
                                value="<?= htmlspecialchars((string) $inputFechaInicio, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Cierre</label>
                            <input type="date" id="fecha_fin_input" name="fecha_fin" class="form-control" required
                                value="<?= htmlspecialchars((string) $inputFechaFin, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Grupo</label>
                            <select name="grupo_id" class="form-select" required>
                                <option value="">Seleccionar grupo</option>
                                <?php foreach ($gruposActivos as $grupo): ?>
                                <option value="<?= (int) $grupo['id'] ?>" <?= $inputGrupoId === (int) $grupo['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $grupo['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control" maxlength="255"
                                value="<?= htmlspecialchars((string) $inputObservaciones, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Opcional: entrega de caja, ajustes, etc.">
                        </div>
                    </div>
                    <div class="small text-muted mt-2">
                        Puedes definir manualmente fecha de inicio y cierre por grupo. No se permite solapar rangos.
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="fas fa-plus-circle"></i> Guardar Semana
                    </button>
                </form>

                <h2 class="h5 mb-3">Historial y control</h2>

                <div class="mobile-only">
                    <?php if (empty($semanas)): ?>
                    <div class="alert alert-secondary">Todavía no hay semanas registradas.</div>
                    <?php endif; ?>
                    <?php foreach ($semanas as $semana): ?>
                    <?php
                        $estado = (string) ($semana['estado'] ?? 'planificada');
                        $stateClass = 'state-planificada';
                        if ($estado === 'activa') {
                            $stateClass = 'state-activa';
                        } elseif ($estado === 'cerrada') {
                            $stateClass = 'state-cerrada';
                        }
                    ?>
                    <div class="week-card">
                        <div class="week-head">
                            <h3 class="week-title"><?= htmlspecialchars((string) $semana['grupo_nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="badge-state <?= $stateClass ?>"><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="week-sub">
                            <?= date('d/m/Y', strtotime($semana['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($semana['fecha_fin'])) ?>
                        </div>
                        <?php if (!empty($semana['observaciones'])): ?>
                        <div class="small mb-2"><strong>Obs:</strong> <?= htmlspecialchars((string) $semana['observaciones'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mb-2">
                            Creado por: <?= htmlspecialchars(trim((string) $semana['creado_por_nombre']) ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="week-actions">
                            <?php if ($estado !== 'activa' && $estado !== 'cerrada'): ?>
                            <form method="post">
                                <input type="hidden" name="activar_semana_id" value="<?= (int) $semana['id'] ?>">
                                <button type="submit" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-play"></i> Activar
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($estado !== 'cerrada'): ?>
                            <form method="post">
                                <input type="hidden" name="cerrar_semana_id" value="<?= (int) $semana['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-lock"></i> Cerrar
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="desktop-only">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Semana</th>
                                    <th>Grupo</th>
                                    <th>Estado</th>
                                    <th>Observaciones</th>
                                    <th>Creado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($semanas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Todavía no hay semanas registradas.</td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($semanas as $semana): ?>
                                <?php
                                    $estado = (string) ($semana['estado'] ?? 'planificada');
                                    $stateClass = 'state-planificada';
                                    if ($estado === 'activa') {
                                        $stateClass = 'state-activa';
                                    } elseif ($estado === 'cerrada') {
                                        $stateClass = 'state-cerrada';
                                    }
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($semana['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($semana['fecha_fin'])) ?></td>
                                    <td><?= htmlspecialchars((string) $semana['grupo_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge-state <?= $stateClass ?>"><?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars((string) ($semana['observaciones'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(trim((string) $semana['creado_por_nombre']) ?: 'N/D', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php if ($estado !== 'activa' && $estado !== 'cerrada'): ?>
                                            <form method="post">
                                                <input type="hidden" name="activar_semana_id" value="<?= (int) $semana['id'] ?>">
                                                <button type="submit" class="btn btn-outline-success btn-sm">Activar</button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if ($estado !== 'cerrada'): ?>
                                            <form method="post">
                                                <input type="hidden" name="cerrar_semana_id" value="<?= (int) $semana['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Cerrar</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
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
    <script>
    (function () {
        var inicio = document.getElementById('fecha_inicio_input');
        var cierre = document.getElementById('fecha_fin_input');
        if (!inicio || !cierre) {
            return;
        }

        function syncMinCierre() {
            if (!inicio.value) {
                cierre.removeAttribute('min');
                return;
            }
            cierre.min = inicio.value;
            if (!cierre.value || cierre.value < inicio.value) {
                cierre.value = inicio.value;
            }
        }

        inicio.addEventListener('change', syncMinCierre);
        syncMinCierre();
    })();
    </script>
</body>
</html>

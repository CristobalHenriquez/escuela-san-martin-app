<?php
session_start();
require 'db.php';

// Solo admin puede acceder
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_admin']) || $_SESSION['usuario_admin'] != 1) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$error = '';
$gruposActivos = get_grupos_activos($db);
$gruposMap = [];
foreach ($gruposActivos as $grupoTmp) {
    $gruposMap[(int) $grupoTmp['id']] = $grupoTmp['nombre'];
}

// Crear usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $grupoId = isset($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : 0;
    if ($nombre && $apellido && $email && $password) {
        if ($grupoId <= 0 || !is_grupo_activo_valido($db, $grupoId)) {
            $error = 'Debes asignar un grupo activo al usuario.';
        } else {
        // Verificar que no exista el email
        $stmt = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'El email ya existe.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('INSERT INTO usuarios (nombre, apellido, email, password, telefono, grupo_id, es_admin) VALUES (?, ?, ?, ?, ?, ?, 0)')
                ->execute([$nombre, $apellido, $email, $hash, $telefono, $grupoId]);
            $mensaje = 'Usuario creado correctamente.';
        }
        }
    } else {
        $error = 'Todos los campos obligatorios deben estar completos.';
    }
}

// Actualizar grupo asignado de usuario
if (isset($_POST['actualizar_grupo_usuario_id'])) {
    $id = (int) $_POST['actualizar_grupo_usuario_id'];
    $nuevoGrupoId = isset($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : 0;

    if ($id <= 0) {
        $error = 'Usuario inválido.';
    } elseif ($nuevoGrupoId <= 0 || !is_grupo_activo_valido($db, $nuevoGrupoId)) {
        $error = 'Debes seleccionar un grupo activo válido.';
    } else {
        $db->prepare('UPDATE usuarios SET grupo_id = ? WHERE id = ?')->execute([$nuevoGrupoId, $id]);
        $mensaje = 'Grupo de usuario actualizado correctamente.';

        if (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === $id) {
            $_SESSION['usuario_grupo_id'] = $nuevoGrupoId;
            $_SESSION['usuario_grupo_nombre'] = $gruposMap[$nuevoGrupoId] ?? get_grupo_nombre_por_id($db, $nuevoGrupoId);
        }
    }
}

// Cambiar contraseña de usuario
if (isset($_POST['cambiar_password_usuario_id'])) {
    $id = (int) ($_POST['cambiar_password_usuario_id'] ?? 0);
    $passwordNueva = (string) ($_POST['password_nueva'] ?? '');
    $passwordConfirm = (string) ($_POST['password_nueva_confirm'] ?? '');

    if ($id <= 0) {
        $error = 'Usuario inválido para cambio de contraseña.';
    } elseif ($passwordNueva === '' || $passwordConfirm === '') {
        $error = 'Debes ingresar y confirmar la nueva contraseña.';
    } elseif ($passwordNueva !== $passwordConfirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($passwordNueva) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $stmt = $db->prepare('SELECT id, nombre, apellido, super_admin FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $usuarioObjetivo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuarioObjetivo) {
            $error = 'El usuario seleccionado no existe.';
        } elseif (!empty($usuarioObjetivo['super_admin']) && empty($_SESSION['usuario_super_admin'])) {
            $error = 'Solo un super admin puede cambiar la contraseña de otro super admin.';
        } else {
            $hash = password_hash($passwordNueva, PASSWORD_DEFAULT);
            $db->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([$hash, $id]);
            $nombreCompleto = trim((string) ($usuarioObjetivo['nombre'] . ' ' . $usuarioObjetivo['apellido']));
            $mensaje = 'Contraseña actualizada para ' . ($nombreCompleto !== '' ? $nombreCompleto : ('ID #' . $id)) . '.';
        }
    }
}

// Eliminar usuario (excepto admin principal)
if (isset($_POST['eliminar_usuario_id'])) {
    $id = intval($_POST['eliminar_usuario_id']);
    // No permitir eliminar al admin principal (id=1)
    if ($id !== 1) {
        $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        $mensaje = 'Usuario eliminado correctamente.';
    } else {
        $error = 'No se puede eliminar el usuario administrador principal.';
    }
}

// Cambiar estado de usuario (activar/desactivar)
if (isset($_POST['toggle_activo_id'])) {
    $id = intval($_POST['toggle_activo_id']);
    // No permitir desactivar al admin principal (id=1)
    if ($id !== 1) {
        $usuario = $db->prepare('SELECT * FROM usuarios WHERE id = ?');
        $usuario->execute([$id]);
        $usuario = $usuario->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            $nuevo_estado = $usuario['activo'] ? 0 : 1;
            $db->prepare('UPDATE usuarios SET activo = ? WHERE id = ?')->execute([$nuevo_estado, $id]);
            $mensaje = $nuevo_estado ? 'Usuario activado.' : 'Usuario desactivado.';
        }
    } else {
        $error = 'No se puede desactivar el usuario administrador principal.';
    }
}

// Limpiar datos operativos (mantener usuarios/productos, stock a 0)
if (isset($_POST['limpiar_datos_operativos'])) {
    $confirmacion = trim((string) ($_POST['confirmacion_limpieza'] ?? ''));
    if ($confirmacion !== 'LIMPIAR') {
        $error = 'Confirmación inválida. Escribe LIMPIAR para ejecutar el reseteo.';
    } else {
        $tablasReset = [
            'transacciones',
            'capital_liquido',
            'compras_mercaderia',
            'balance_diario',
            'turnos',
            'semanas_operativas',
        ];

        try {
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tablasReset as $tablaReset) {
                $db->exec('TRUNCATE TABLE ' . $tablaReset);
            }
            $db->exec('UPDATE productos SET stock = 0');
            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            $mensaje = 'Datos operativos limpiados. Se conservaron usuarios y productos; stock en cero.';
        } catch (Throwable $e) {
            try {
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Throwable $e2) {
                // Si falla restaurar FK checks, ya no bloqueamos el manejo de error principal.
            }
            $error = 'No se pudo completar la limpieza operativa.';
        }
    }
}

// Listar usuarios
$usuarios = $db->query('SELECT u.*, g.nombre AS grupo_nombre FROM usuarios u LEFT JOIN grupos g ON g.id = u.grupo_id ORDER BY u.es_admin DESC, u.id ASC')->fetchAll(PDO::FETCH_ASSOC);
$totalUsuarios = count($usuarios);
$usuariosActivos = 0;
$usuariosInactivos = 0;
$usuariosAdmin = 0;
foreach ($usuarios as $uTmp) {
    if (!empty($uTmp['activo'])) {
        $usuariosActivos++;
    } else {
        $usuariosInactivos++;
    }
    if (!empty($uTmp['es_admin']) || !empty($uTmp['super_admin'])) {
        $usuariosAdmin++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-a: #0f4c75;
            --bg-b: #3282b8;
            --surface: #ffffff;
            --surface-soft: #f8fbff;
            --border: #dbe6f2;
            --text: #102a43;
            --muted: #52606d;
            --radius: 14px;
        }
        body {
            margin: 0;
            background:
                radial-gradient(circle at 12% 15%, rgba(255, 255, 255, 0.12), transparent 30%),
                linear-gradient(150deg, var(--bg-a), var(--bg-b));
            min-height: 100vh;
            color: var(--text);
        }
        .page-shell {
            max-width: 1140px;
            margin: 0 auto;
            padding: 12px;
        }
        .main-container {
            background: var(--surface);
            border-radius: 18px;
            box-shadow: 0 18px 38px rgba(11, 34, 58, 0.22);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(140deg, #102a43, #1f4f75);
            color: white;
            padding: 14px 16px;
        }
        .header-title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
        }
        .header-sub {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.85);
        }
        .content-wrap {
            padding: 12px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .stat-card {
            background: var(--surface-soft);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--muted);
            margin-bottom: 2px;
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 12px;
            margin-bottom: 12px;
        }
        .section-title {
            margin-bottom: 10px;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.35rem;
        }
        .usuarios-mobile .user-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            padding: 11px;
            margin-bottom: 10px;
        }
        .user-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
        }
        .user-name {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.3;
        }
        .user-email {
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 8px;
            word-break: break-word;
        }
        .group-form-mobile {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 6px;
            margin-bottom: 8px;
        }
        .card-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .usuarios-desktop {
            display: none;
        }
        .table > :not(caption) > * > * {
            vertical-align: middle;
        }
        .table thead th {
            white-space: nowrap;
        }
        .btn-accion, .btn-eliminar {
            font-size: 0.83rem;
            padding: 0.28rem 0.55rem;
        }
        .header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        @media (min-width: 768px) {
            .page-shell {
                padding: 24px;
            }
            .content-wrap {
                padding: 18px;
            }
            .stats-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
            .header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }
            .header-actions {
                margin-top: 0;
                justify-content: flex-end;
            }
        }
        @media (min-width: 992px) {
            .usuarios-mobile {
                display: none;
            }
            .usuarios-desktop {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <div class="main-container">
            <div class="header">
                <div>
                    <h1 class="header-title"><i class="fas fa-users"></i> Administrar Usuarios</h1>
                    <div class="header-sub">
                        <i class="fas fa-user-shield"></i>
                        <?= htmlspecialchars(trim($_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido']), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if (!empty($_SESSION['usuario_super_admin'])): ?>
                    <a href="semanas.php" class="btn btn-outline-light btn-sm"><i class="fas fa-calendar-week"></i> Semanas</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Volver al POS</a>
                </div>
            </div>

            <div class="content-wrap">
                <?php if ($mensaje): ?>
                <div class="alert alert-success mb-3"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger mb-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total usuarios</div>
                        <div class="stat-value"><?= (int) $totalUsuarios ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Activos</div>
                        <div class="stat-value text-success"><?= (int) $usuariosActivos ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Inactivos</div>
                        <div class="stat-value text-danger"><?= (int) $usuariosInactivos ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Admin / Super</div>
                        <div class="stat-value text-primary"><?= (int) $usuariosAdmin ?></div>
                    </div>
                </div>

                <div class="section-card border border-danger">
                    <h2 class="section-title text-danger mb-2"><i class="fas fa-triangle-exclamation me-1"></i> Reinicio operativo</h2>
                    <p class="small text-muted mb-2">
                        Limpia turnos, movimientos y balances. Mantiene usuarios y productos, pero deja todo el stock en <b>0</b>.
                    </p>
                    <button type="button" class="btn btn-danger btn-sm btn-reset-datos">
                        <i class="fas fa-broom"></i> Limpiar datos operativos
                    </button>
                </div>

                <div class="section-card">
                    <h2 class="section-title">Crear nuevo usuario</h2>
                    <form method="post">
                        <input type="hidden" name="crear_usuario" value="1">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label">Nombre*</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Apellido*</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Email*</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Contraseña*</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Grupo asignado*</label>
                                <select name="grupo_id" class="form-select" required>
                                    <option value="">Seleccionar grupo</option>
                                    <?php foreach ($gruposActivos as $grupo): ?>
                                    <option value="<?= (int) $grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus"></i> Crear usuario
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="section-card">
                    <h2 class="section-title">Usuarios existentes</h2>
                    <div class="alert alert-light border small py-2">
                        Por seguridad no se puede ver la contraseña actual guardada. Sí puedes cambiarla y verla mientras la escribes.
                    </div>

                    <div class="usuarios-mobile">
                        <?php foreach ($usuarios as $u): ?>
                        <div class="user-card">
                            <div class="user-head">
                                <h3 class="user-name"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <?php if (!empty($u['super_admin'])): ?>
                                <span class="badge bg-dark">Super Admin</span>
                                <?php elseif (!empty($u['es_admin'])): ?>
                                <span class="badge bg-primary">Admin</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">General</span>
                                <?php endif; ?>
                            </div>

                            <div class="user-email"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></div>

                            <div class="d-flex align-items-center gap-2 mb-2">
                                <?php if (!empty($u['activo'])): ?>
                                <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                                <small class="text-muted">ID #<?= (int) $u['id'] ?></small>
                            </div>

                            <form method="post" class="group-form-mobile">
                                <input type="hidden" name="actualizar_grupo_usuario_id" value="<?= (int) $u['id'] ?>">
                                <select name="grupo_id" class="form-select form-select-sm" <?= $u['id'] == 1 ? 'disabled' : '' ?>>
                                    <option value="">Sin grupo</option>
                                    <?php foreach ($gruposActivos as $grupo): ?>
                                    <option value="<?= (int) $grupo['id'] ?>" <?= ((int) ($u['grupo_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($grupo['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($u['id'] != 1): ?>
                                <button type="submit" class="btn btn-outline-primary btn-sm btn-accion">Guardar</button>
                                <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled>Bloqueado</button>
                                <?php endif; ?>
                            </form>

                            <div class="card-actions">
                                <?php $puedeCambiarPassword = ($u['id'] != 1) && (!empty($_SESSION['usuario_super_admin']) || empty($u['super_admin'])); ?>
                                <?php if ($u['id'] != 1): ?>
                                <?php if ($puedeCambiarPassword): ?>
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm btn-accion btn-password"
                                    data-user-id="<?= (int) $u['id'] ?>"
                                    data-user-nombre="<?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-key"></i> Clave
                                </button>
                                <?php endif; ?>
                                <form method="post" class="form-accion">
                                    <input type="hidden" name="toggle_activo_id" value="<?= (int) $u['id'] ?>">
                                    <button type="button"
                                        class="btn btn-<?= $u['activo'] ? 'warning' : 'success' ?> btn-sm btn-accion btn-toggle"
                                        data-nombre="<?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-accion="<?= $u['activo'] ? 'desactivar' : 'activar' ?>">
                                        <i class="fas fa-user-<?= $u['activo'] ? 'slash' : 'check' ?>"></i>
                                        <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                                <form method="post" class="form-accion">
                                    <input type="hidden" name="eliminar_usuario_id" value="<?= (int) $u['id'] ?>">
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar btn-borrar"
                                        data-nombre="<?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted small">Usuario principal protegido</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="usuarios-desktop table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Grupo</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form method="post" class="d-flex gap-1 align-items-center">
                                            <input type="hidden" name="actualizar_grupo_usuario_id" value="<?= (int) $u['id'] ?>">
                                            <select name="grupo_id" class="form-select form-select-sm" style="min-width:125px;" <?= $u['id'] == 1 ? 'disabled' : '' ?>>
                                                <option value="">Sin grupo</option>
                                                <?php foreach ($gruposActivos as $grupo): ?>
                                                <option value="<?= (int) $grupo['id'] ?>" <?= ((int) ($u['grupo_id'] ?? 0) === (int) $grupo['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($grupo['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ($u['id'] != 1): ?>
                                            <button type="submit" class="btn btn-outline-primary btn-sm btn-accion">Guardar</button>
                                            <?php else: ?>
                                            <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['super_admin'])): ?>
                                        <span class="badge bg-dark">Super Admin</span>
                                        <?php elseif (!empty($u['es_admin'])): ?>
                                        <span class="badge bg-primary">Admin</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['activo'])): ?>
                                        <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['id'] != 1): ?>
                                        <?php $puedeCambiarPassword = ($u['id'] != 1) && (!empty($_SESSION['usuario_super_admin']) || empty($u['super_admin'])); ?>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php if ($puedeCambiarPassword): ?>
                                            <button type="button"
                                                class="btn btn-outline-secondary btn-sm btn-accion btn-password"
                                                data-user-id="<?= (int) $u['id'] ?>"
                                                data-user-nombre="<?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-key"></i> Clave
                                            </button>
                                            <?php endif; ?>
                                            <form method="post" class="form-accion">
                                                <input type="hidden" name="toggle_activo_id" value="<?= (int) $u['id'] ?>">
                                                <button type="button"
                                                    class="btn btn-<?= $u['activo'] ? 'warning' : 'success' ?> btn-sm btn-accion btn-toggle"
                                                    data-nombre="<?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-accion="<?= $u['activo'] ? 'desactivar' : 'activar' ?>">
                                                    <i class="fas fa-user-<?= $u['activo'] ? 'slash' : 'check' ?>"></i>
                                                    <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>
                                            <form method="post" class="form-accion">
                                                <input type="hidden" name="eliminar_usuario_id" value="<?= (int) $u['id'] ?>">
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar btn-borrar"
                                                    data-nombre="<?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
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
    <form method="post" id="formCambiarPassword" class="d-none">
        <input type="hidden" name="cambiar_password_usuario_id" value="">
        <input type="hidden" name="password_nueva" value="">
        <input type="hidden" name="password_nueva_confirm" value="">
    </form>
    <form method="post" id="formResetOperativo" class="d-none">
        <input type="hidden" name="limpiar_datos_operativos" value="1">
        <input type="hidden" name="confirmacion_limpieza" value="">
    </form>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <script>
    // Confirmaciones con SweetAlert2
        const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));

        const formCambiarPassword = document.getElementById('formCambiarPassword');
        document.querySelectorAll('.btn-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id') || '';
                const userNombre = this.getAttribute('data-user-nombre') || 'usuario';
                const userNombreSafe = escapeHtml(userNombre);
                Swal.fire({
                    title: 'Cambiar contraseña',
                    html: `
                        <div class="text-start">
                            <p class="mb-2 small text-muted">Usuario: <b>${userNombreSafe}</b></p>
                            <label for="swal-pass-1" class="form-label mb-1">Nueva contraseña</label>
                            <input id="swal-pass-1" type="password" class="swal2-input mt-0 mb-2" placeholder="Mínimo 8 caracteres">
                            <label for="swal-pass-2" class="form-label mb-1">Confirmar contraseña</label>
                            <input id="swal-pass-2" type="password" class="swal2-input mt-0 mb-2" placeholder="Repetir contraseña">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="swal-show-pass">
                                <label class="form-check-label small" for="swal-show-pass">Ver contraseña</label>
                            </div>
                        </div>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar contraseña',
                    cancelButtonText: 'Cancelar',
                    didOpen: () => {
                        const pass1 = document.getElementById('swal-pass-1');
                        const pass2 = document.getElementById('swal-pass-2');
                        const show = document.getElementById('swal-show-pass');
                        if (show && pass1 && pass2) {
                            show.addEventListener('change', function() {
                                const type = this.checked ? 'text' : 'password';
                                pass1.type = type;
                                pass2.type = type;
                            });
                        }
                    },
                    preConfirm: () => {
                        const pass1 = (document.getElementById('swal-pass-1') || {}).value || '';
                        const pass2 = (document.getElementById('swal-pass-2') || {}).value || '';
                        if (!pass1 || !pass2) {
                            Swal.showValidationMessage('Completa y confirma la contraseña.');
                            return false;
                        }
                        if (pass1.length < 8) {
                            Swal.showValidationMessage('La contraseña debe tener al menos 8 caracteres.');
                            return false;
                        }
                        if (pass1 !== pass2) {
                            Swal.showValidationMessage('Las contraseñas no coinciden.');
                            return false;
                        }
                        return { pass1, pass2 };
                    }
                }).then((result) => {
                    if (!result.isConfirmed || !result.value || !formCambiarPassword) {
                        return;
                    }
                    formCambiarPassword.querySelector('input[name=\"cambiar_password_usuario_id\"]').value = userId;
                    formCambiarPassword.querySelector('input[name=\"password_nueva\"]').value = result.value.pass1;
                    formCambiarPassword.querySelector('input[name=\"password_nueva_confirm\"]').value = result.value.pass2;
                    formCambiarPassword.submit();
                });
            });
        });

        const formResetOperativo = document.getElementById('formResetOperativo');
        const btnResetDatos = document.querySelector('.btn-reset-datos');
        if (btnResetDatos && formResetOperativo) {
            btnResetDatos.addEventListener('click', function() {
                Swal.fire({
                    title: '¿Limpiar datos operativos?',
                    html: `
                        <div class="text-start">
                            <p class="mb-2">Esta acción eliminará:</p>
                            <ul class="small text-muted mb-2">
                                <li>Turnos</li>
                                <li>Movimientos de capital</li>
                                <li>Compras de mercadería</li>
                                <li>Balances diarios</li>
                                <li>Semanas operativas</li>
                            </ul>
                            <p class="small mb-0">Se conservarán usuarios y productos, pero el stock quedará en <b>0</b>.</p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                }).then((primerPaso) => {
                    if (!primerPaso.isConfirmed) {
                        return;
                    }
                    Swal.fire({
                        title: 'Confirmación final',
                        text: 'Escribe LIMPIAR para confirmar.',
                        input: 'text',
                        inputPlaceholder: 'LIMPIAR',
                        showCancelButton: true,
                        confirmButtonText: 'Ejecutar limpieza',
                        cancelButtonText: 'Cancelar',
                        inputValidator: (value) => {
                            if ((value || '').trim().toUpperCase() !== 'LIMPIAR') {
                                return 'Debes escribir LIMPIAR exactamente.';
                            }
                            return null;
                        }
                    }).then((segundoPaso) => {
                        if (!segundoPaso.isConfirmed) {
                            return;
                        }
                        formResetOperativo.querySelector('input[name=\"confirmacion_limpieza\"]').value = 'LIMPIAR';
                        formResetOperativo.submit();
                    });
                });
            });
        }

        document.querySelectorAll('.btn-toggle').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const form = this.closest('form');
                const nombre = this.getAttribute('data-nombre');
                const accion = this.getAttribute('data-accion');
                Swal.fire({
                    title: '¿Seguro que desea ' + accion + ' a ' + nombre + '?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, ' + accion,
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
        document.querySelectorAll('.btn-borrar').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const form = this.closest('form');
                const nombre = this.getAttribute('data-nombre');
                Swal.fire({
                    title: '¿Eliminar a ' + nombre + '?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html> 

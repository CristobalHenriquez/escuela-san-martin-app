<?php
// Dashboard principal del admin - EESO 225 San Martín
$admin_page_title = 'Panel de Administración | EESO 225 San Martín';
$admin_page_description = 'Panel principal de administración del sitio web escolar';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Generar token CSRF para proteger las operaciones
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Incluir el encabezado
include_once 'includes/head.php';
include_once 'includes/sweetalert.php';

require_once '../includes/conexion.php';
require_once 'config.php';

// Verificar autenticación
verificarAutenticacion();

// Obtener estadísticas del dashboard
$estadisticas = obtenerEstadisticasDashboard();

// Obtener actividad reciente
$actividad_reciente = obtenerActividadReciente(8);

// Obtener información del sistema
$info_sistema = obtenerInformacionSistema();

// Obtener usuario actual
$usuario_actual = obtenerUsuarioActual();
?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">Panel de Administración <i class="bi bi-speedometer2"></i></h1>
        <nav aria-label="breadcrumb">
            <ol class="admin-breadcrumb breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Panel</li>
            </ol>
        </nav>
    </div>
    <div class="text-end">
        <div class="small text-muted">Bienvenido, <strong><?= htmlspecialchars($usuario_actual['nombreyapellido']) ?></strong></div>
        <div class="small text-muted"><?= ESCUELA_NOMBRE ?></div>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card border-left-primary shadow h-100 py-2">
            <div class="admin-card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Noticias Escolares
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $estadisticas['noticias'] ?>
                        </div>
                        <div class="small text-muted">
                            <?= $estadisticas['noticias_publicadas'] ?> publicadas
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-newspaper fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card border-left-success shadow h-100 py-2">
            <div class="admin-card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Personal Docente
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $estadisticas['personal'] ?>
                        </div>
                        <div class="small text-muted">
                            <?= $estadisticas['personal_visible'] ?> visibles
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card border-left-warning shadow h-100 py-2">
            <div class="admin-card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Logros Estudiantiles
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $estadisticas['logros'] ?>
                        </div>
                        <div class="small text-muted">
                            <?= $estadisticas['logros_visibles'] ?> visibles
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-trophy fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card border-left-info shadow h-100 py-2">
            <div class="admin-card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Contenido
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $estadisticas['noticias'] + $estadisticas['personal'] + $estadisticas['logros'] ?>
                        </div>
                        <div class="small text-muted">
                            Elementos en total
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-collection fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contenido principal -->
<div class="row">
    <!-- Actividad Reciente -->
    <div class="col-lg-8 mb-4">
        <div class="admin-card shadow mb-4">
            <div class="admin-card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Actividad Reciente</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Acciones:</div>
                        <a class="dropdown-item" href="noticias.php">
                            <i class="bi bi-newspaper fa-sm fa-fw mr-2 text-gray-400"></i>
                            Ver todas las noticias
                        </a>
                        <a class="dropdown-item" href="personal.php">
                            <i class="bi bi-people fa-sm fa-fw mr-2 text-gray-400"></i>
                            Ver todo el personal
                        </a>
                        <a class="dropdown-item" href="logros.php">
                            <i class="bi bi-trophy fa-sm fa-fw mr-2 text-gray-400"></i>
                            Ver todos los logros
                        </a>
                    </div>
                </div>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($actividad_reciente)): ?>
                    <div class="timeline">
                        <?php foreach ($actividad_reciente as $index => $actividad): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <?php
                                    $icono = '';
                                    $color = '';
                                    switch ($actividad['tipo']) {
                                        case 'noticia':
                                            $icono = 'bi-newspaper';
                                            $color = 'text-primary';
                                            break;
                                        case 'personal':
                                            $icono = 'bi-people';
                                            $color = 'text-success';
                                            break;
                                        case 'logro':
                                            $icono = 'bi-trophy';
                                            $color = 'text-warning';
                                            break;
                                    }
                                    ?>
                                    <i class="bi <?= $icono ?> <?= $color ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="font-weight-bold"><?= htmlspecialchars($actividad['titulo']) ?></span>
                                        <span class="float-right text-muted small">
                                            <?= formatearFechaMostrar($actividad['created_at']) ?>
                                        </span>
                                    </div>
                                    <div class="timeline-body">
                                        <span class="badge bg-light text-dark">
                                            <?= ucfirst($actividad['tipo']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay actividad reciente</h5>
                        <p class="text-muted">Los elementos que agregues aparecerán aquí.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="col-lg-4 mb-4">
        <div class="admin-card shadow mb-4">
            <div class="admin-card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
            </div>
            <div class="admin-card-body">
                <div class="d-grid gap-2">
                    <a href="cargar-noticia.php" class="btn btn-admin-primary">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Noticia
                    </a>
                    <a href="personal.php" class="btn btn-admin-success">
                        <i class="bi bi-person-plus me-2"></i>Nuevo Personal
                    </a>
                    <a href="logros.php" class="btn btn-admin-warning">
                        <i class="bi bi-trophy me-2"></i>Nuevo Logro
                    </a>
                    <hr class="my-3">
                    <a href="usuarios.php" class="btn btn-admin-info">
                        <i class="bi bi-people-fill me-2"></i>Gestión de Usuarios
                    </a>
                </div>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="admin-card shadow mb-4">
            <div class="admin-card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Sistema</h6>
            </div>
            <div class="admin-card-body">
                <div class="mb-3">
                    <div class="small text-muted">Versión PHP</div>
                    <div class="fw-medium"><?= $info_sistema['php_version'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Versión MySQL</div>
                    <div class="fw-medium"><?= $info_sistema['mysql_version'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Espacio disponible</div>
                    <div class="fw-medium"><?= $info_sistema['espacio_disco'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Límite de memoria</div>
                    <div class="fw-medium"><?= $info_sistema['memoria_limit'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Tamaño máximo de archivo</div>
                    <div class="fw-medium"><?= $info_sistema['upload_max_filesize'] ?></div>
                </div>
            </div>
        </div>

        <!-- Enlaces Útiles -->
        <div class="admin-card shadow">
            <div class="admin-card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Enlaces Útiles</h6>
            </div>
            <div class="admin-card-body">
                <div class="list-group list-group-flush">
                    <a href="../public/" class="list-group-item list-group-item-action" target="_blank">
                        <i class="bi bi-globe me-2"></i>Ver sitio web
                    </a>
                    <a href="noticias.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-newspaper me-2"></i>Gestionar noticias
                    </a>
                    <a href="personal.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-people me-2"></i>Gestionar personal
                    </a>
                    <a href="logros.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-trophy me-2"></i>Gestionar logros
                    </a>
                    <a href="usuarios.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-people-fill me-2"></i>Gestión de usuarios
                    </a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos para el timeline -->
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 20px;
    height: 20px;
    background: #fff;
    border: 2px solid #e3e6f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.timeline-content {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #4e73df;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.timeline-body {
    font-size: 0.875rem;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.text-primary {
    color: #4e73df !important;
}

.text-success {
    color: #1cc88a !important;
}

.text-warning {
    color: #f6c23e !important;
}

.text-info {
    color: #36b9cc !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.text-gray-400 {
    color: #858796 !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.text-xs {
    font-size: 0.7rem;
}

.text-uppercase {
    text-transform: uppercase !important;
}
</style>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>


<?php
// Configuración de la página
$admin_page_title = 'Gestión de Noticias | EESO 225 "La San Martín"';
$admin_page_description = 'Administra las noticias y comunicados escolares';

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

// Manejo de mensajes
$mensaje = '';
$tipoMensaje = '';

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $mensaje = "Noticia guardada correctamente";
    $tipoMensaje = "success";
} elseif (isset($_GET['update']) && $_GET['update'] == 'ok') {
    $mensaje = "Noticia actualizada correctamente";
    $tipoMensaje = "success";
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'ok') {
    $mensaje = "Noticia eliminada correctamente";
    $tipoMensaje = "success";
}

// Procesar mensajes de error
if (isset($_GET['error'])) {
    $tipoMensaje = "error";

    if (isset($_SESSION['error_message'])) {
        $mensaje = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    } else {
        $mensaje = "Ocurrió un error al procesar la noticia.";
    }
}

// Paginación
$porPagina = ADMIN_POSTS_POR_PAGINA;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $porPagina;

// Filtros
$filtroCategoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$filtroFecha = isset($_GET['filtro_fecha']) ? $_GET['filtro_fecha'] : '';
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Construir la consulta SQL con filtros
$sql = "SELECT * FROM posts WHERE 1=1";
$params = [];
$types = "";

if (!empty($busqueda)) {
    $sql .= " AND (titulo LIKE ? OR contenido LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $types .= "ss";
}

if (!empty($filtroCategoria)) {
    $sql .= " AND categoria = ?";
    $params[] = $filtroCategoria;
    $types .= "s";
}

if (!empty($filtroFecha)) {
    if ($filtroFecha === 'hoy') {
        $sql .= " AND DATE(created_at) = CURDATE()";
    } elseif ($filtroFecha === 'semana') {
        $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filtroFecha === 'mes') {
        $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}

if (!empty($fechaInicio) && !empty($fechaFin)) {
    $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $fechaInicio;
    $params[] = $fechaFin;
    $types .= "ss";
}

// Contar total de registros para paginación
$sqlCount = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$stmtCount = $db->prepare($sqlCount);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalRegistros = $resultCount->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $porPagina);

// Consulta final con orden y límite
$sql .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $porPagina;
$types .= "ii";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">Noticias Escolares <i class="bi bi-newspaper"></i></h1>
        <nav aria-label="breadcrumb">
            <ol class="admin-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Noticias</li>
            </ol>
        </nav>
    </div>
    <a href="cargar-noticia.php" class="btn btn-admin-primary">
        <i class="bi bi-plus-circle me-1"></i> Nueva Noticia
    </a>
</div>

<?php if (!empty($mensaje)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($tipoMensaje === "success"): ?>
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: 'success',
                    title: '<?= $mensaje ?>'
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= $mensaje ?>',
                    confirmButtonColor: '#dc3545'
                });
            <?php endif; ?>
        });
    </script>
<?php endif; ?>

<!-- Filtros -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h5 class="admin-card-title">Filtros de búsqueda</h5>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="busqueda" class="form-label">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Título o contenido...">
                </div>
            </div>
            <div class="col-md-3">
                <label for="categoria" class="form-label">Categoría</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="">Todas las categorías</option>
                    <option value="noticia" <?= $filtroCategoria === 'noticia' ? 'selected' : '' ?>>Noticia</option>
                    <option value="evento" <?= $filtroCategoria === 'evento' ? 'selected' : '' ?>>Evento</option>
                    <option value="curso" <?= $filtroCategoria === 'curso' ? 'selected' : '' ?>>Curso</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filtro_fecha" class="form-label">Período</label>
                <select class="form-select" id="filtro_fecha" name="filtro_fecha">
                    <option value="">Todos los períodos</option>
                    <option value="hoy" <?= $filtroFecha === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                    <option value="semana" <?= $filtroFecha === 'semana' ? 'selected' : '' ?>>Última semana</option>
                    <option value="mes" <?= $filtroFecha === 'mes' ? 'selected' : '' ?>>Último mes</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary me-2">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
                <a href="noticias.php" class="btn btn-admin-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Listado de noticias -->
<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="admin-card-title">
            <i class="bi bi-newspaper me-2"></i>
            Listado de noticias escolares
        </h5>
        <span class="badge bg-primary"><?= $totalRegistros ?> registros</span>
    </div>
    <div class="admin-card-body p-0">
        <?php if ($totalRegistros > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th>Visitas</th>
                            <th>Visible</th>
                            <th>Imagen</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($noticia = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?= $noticia['id'] ?></td>
                                <td>
                                    <div class="fw-medium">
                                        <?= htmlspecialchars($noticia['titulo']) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= truncarTexto(strip_tags($noticia['contenido']), 100) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= ucfirst($noticia['categoria']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <?= formatearFechaMostrar($noticia['created_at']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= $noticia['visitas'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $noticia['visible'] ?
                                        '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sí</span>' :
                                        '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>No</span>' ?>
                                </td>
                                <td>
                                    <?php if (!empty($noticia['imagen'])): ?>
                                        <img src="../<?= htmlspecialchars($noticia['imagen']) ?>" class="img-thumbnail" style="height: 50px; width: auto;" alt="Imagen">
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Sin imagen</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <a href="editar-noticia.php?id=<?= $noticia['id'] ?>" class="btn btn-sm btn-admin-warning me-1" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-admin-danger"
                                            onclick="confirmarEliminar(<?= $noticia['id'] ?>, '<?= htmlspecialchars(addslashes($noticia['titulo'])) ?>')"
                                            title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPaginas > 1): ?>
                <div class="p-3 border-top">
                    <nav aria-label="Navegación de páginas">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            <?php
                            // Mostrar un número limitado de páginas
                            $maxPagesToShow = 5;
                            $startPage = max(1, min($pagina - floor($maxPagesToShow / 2), $totalPaginas - $maxPagesToShow + 1));
                            $endPage = min($startPage + $maxPagesToShow - 1, $totalPaginas);

                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPaginas): ?>
                                <?php if ($endPage < $totalPaginas - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?= $totalPaginas ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>">
                                        <?= $totalPaginas ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>" aria-label="Siguiente">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center p-5">
                <i class="bi bi-newspaper fs-1 text-muted mb-3"></i>
                <h5>No se encontraron noticias</h5>
                <p class="text-muted">
                    <?= !empty($busqueda) || !empty($filtroCategoria) || !empty($filtroFecha) ?
                        'No hay resultados con los filtros aplicados.' :
                        'Aún no hay noticias registradas.' ?>
                </p>
                <a href="cargar-noticia.php" class="btn btn-admin-primary mt-2">
                    <i class="bi bi-plus-circle me-1"></i> Crear nueva noticia
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script para manejar la eliminación de noticias -->
<script>
    function confirmarEliminar(id, titulo) {
        Swal.fire({
            title: '¿Eliminar noticia?',
            text: `¿Estás seguro que deseas eliminar la noticia "${titulo}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Segunda confirmación
                Swal.fire({
                    title: '¿Estás completamente seguro?',
                    text: 'Esta acción eliminará permanentemente la noticia y su imagen asociada.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar definitivamente',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((secondResult) => {
                    if (secondResult.isConfirmed) {
                        // Mostrar indicador de carga
                        Swal.fire({
                            title: 'Eliminando...',
                            html: 'Por favor espera mientras se elimina la noticia',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Realizar la eliminación mediante AJAX
                        const formData = new FormData();
                        formData.append('id', id);
                        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

                        fetch('controllers/eliminar-noticia.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Eliminada!',
                                        text: data.message,
                                        confirmButtonColor: '#28a745'
                                    }).then(() => {
                                        // Recargar la página para actualizar la lista
                                        window.location.href = 'noticias.php?delete=ok';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message,
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Ha ocurrido un error al procesar la solicitud.',
                                    confirmButtonColor: '#dc3545'
                                });
                            });
                    }
                });
            }
        });
    }
</script>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>

<?php
// Configuración de la página
$admin_page_title = 'Gestión de Logros Estudiantiles | EESO 225 San Martín';
$admin_page_description = 'Administra los logros y reconocimientos de los estudiantes';

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

// Procesar mensajes de éxito
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $mensaje = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "Logro estudiantil guardado correctamente";
    $tipoMensaje = "success";
    unset($_SESSION['success_message']);
} elseif (isset($_GET['update']) && $_GET['update'] == 'ok') {
    $mensaje = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "Logro estudiantil actualizado correctamente";
    $tipoMensaje = "success";
    unset($_SESSION['success_message']);
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'ok') {
    $mensaje = "Logro estudiantil eliminado correctamente";
    $tipoMensaje = "success";
}

// Procesar mensajes de error
if (isset($_GET['error'])) {
    $tipoMensaje = "error";

    if (isset($_SESSION['error_message'])) {
        $mensaje = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    } else {
        // Mensajes de error por defecto
        switch ($_GET['error']) {
            case 'nombre':
                $mensaje = "El nombre del estudiante es obligatorio.";
                break;
            case 'curso':
                $mensaje = "El curso y división son obligatorios.";
                break;
            case 'logro':
                $mensaje = "El tipo de logro es obligatorio.";
                break;
            case 'descripcion':
                $mensaje = "La descripción es obligatoria.";
                break;
            case 'imagen':
                $mensaje = "Error al procesar la imagen.";
                break;
            case 'formato_imagen':
                $mensaje = "Formato de imagen no válido. Solo se permiten JPG, PNG, GIF y WebP.";
                break;
            case 'db':
                $mensaje = "Error al guardar en la base de datos.";
                break;
            case 'csrf':
                $mensaje = "Error de seguridad: token inválido.";
                break;
            default:
                $mensaje = "Ha ocurrido un error.";
        }
    }
}

// Paginación
$porPagina = ADMIN_LOGROS_POR_PAGINA;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $porPagina;

// Filtros
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$filtroCurso = isset($_GET['curso']) ? $_GET['curso'] : '';
$filtroVisible = isset($_GET['visible']) ? $_GET['visible'] : '';

// Construir la consulta SQL con filtros
$sql = "SELECT * FROM logros_estudiantiles WHERE 1=1";
$params = [];
$types = "";

if (!empty($busqueda)) {
    $sql .= " AND (nombre_estudiante LIKE ? OR curso_division LIKE ? OR logro LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $types .= "ssss";
}

if (!empty($filtroCurso)) {
    $sql .= " AND curso_division LIKE ?";
    $params[] = "%$filtroCurso%";
    $types .= "s";
}

if ($filtroVisible !== '') {
    $sql .= " AND visible = ?";
    $params[] = $filtroVisible;
    $types .= "i";
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
$sql .= " ORDER BY fecha_logro DESC, fecha_creacion DESC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $porPagina;
$types .= "ii";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Obtener cursos únicos para el filtro
$cursosResult = $db->query("SELECT DISTINCT curso_division FROM logros_estudiantiles ORDER BY curso_division");
$cursos = [];
while ($row = $cursosResult->fetch_assoc()) {
    $cursos[] = $row['curso_division'];
}
?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">Logros Estudiantiles <i class="bi bi-trophy"></i></h1>
        <nav aria-label="breadcrumb">
            <ol class="admin-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Logros Estudiantiles</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoLogro">
        <i class="bi bi-plus-circle me-1"></i> Nuevo logro estudiantil
    </button>
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
                    <input type="text" class="form-control" id="busqueda" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Estudiante, curso, logro...">
                </div>
            </div>
            <div class="col-md-3">
                <label for="curso" class="form-label">Curso</label>
                <select class="form-select" id="curso" name="curso">
                    <option value="">Todos los cursos</option>
                    <?php foreach ($cursos as $curso): ?>
                        <option value="<?= htmlspecialchars($curso) ?>" <?= $filtroCurso === $curso ? 'selected' : '' ?>>
                            <?= htmlspecialchars($curso) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="visible" class="form-label">Visibilidad</label>
                <select class="form-select" id="visible" name="visible">
                    <option value="">Todos</option>
                    <option value="1" <?= $filtroVisible === '1' ? 'selected' : '' ?>>Visibles</option>
                    <option value="0" <?= $filtroVisible === '0' ? 'selected' : '' ?>>No visibles</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary me-2">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
                <a href="logros.php" class="btn btn-admin-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Listado de logros estudiantiles -->
<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="admin-card-title">
            <i class="bi bi-trophy me-2"></i>
            Listado de logros estudiantiles
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
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Logro</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Visible</th>
                            <th>Foto</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($logro = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?= $logro['id'] ?></td>
                                <td>
                                    <div class="fw-medium">
                                        <?= htmlspecialchars($logro['nombre_estudiante']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($logro['curso_division']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium text-warning">
                                        <?= htmlspecialchars($logro['logro']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($logro['descripcion']) ?>">
                                        <?= htmlspecialchars($logro['descripcion']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <?= !empty($logro['fecha_logro']) ? formatearFechaMostrar($logro['fecha_logro']) : '-' ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $logro['visible'] ?
                                        '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sí</span>' :
                                        '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>No</span>' ?>
                                </td>
                                <td>
                                    <?php if (!empty($logro['foto'])): ?>
                                        <img src="../<?= htmlspecialchars($logro['foto']) ?>" class="img-thumbnail" style="height: 50px; width: auto;" alt="Foto">
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Sin foto</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-admin-warning me-1"
                                            onclick="cargarDatosLogro(<?= $logro['id'] ?>, '<?= htmlspecialchars(addslashes($logro['nombre_estudiante'])) ?>', '<?= htmlspecialchars(addslashes($logro['curso_division'])) ?>', '<?= htmlspecialchars(addslashes($logro['logro'])) ?>', '<?= htmlspecialchars(addslashes($logro['descripcion'])) ?>', '<?= $logro['fecha_logro'] ?>', <?= $logro['visible'] ?>, '<?= htmlspecialchars(addslashes($logro['foto'] ?? '')) ?>')"
                                            data-bs-toggle="modal" data-bs-target="#modalEditarLogro" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-admin-danger"
                                            onclick="confirmarEliminar(<?= $logro['id'] ?>, '<?= htmlspecialchars(addslashes($logro['nombre_estudiante'])) ?>')"
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
                                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&curso=<?= urlencode($filtroCurso) ?>&visible=<?= urlencode($filtroVisible) ?>" aria-label="Anterior">
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
                                    <a class="page-link" href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>&curso=<?= urlencode($filtroCurso) ?>&visible=<?= urlencode($filtroVisible) ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&curso=<?= urlencode($filtroCurso) ?>&visible=<?= urlencode($filtroVisible) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPaginas): ?>
                                <?php if ($endPage < $totalPaginas - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?= $totalPaginas ?>&busqueda=<?= urlencode($busqueda) ?>&curso=<?= urlencode($filtroCurso) ?>&visible=<?= urlencode($filtroVisible) ?>">
                                        <?= $totalPaginas ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&curso=<?= urlencode($filtroCurso) ?>&visible=<?= urlencode($filtroVisible) ?>" aria-label="Siguiente">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center p-5">
                <i class="bi bi-trophy fs-1 text-muted mb-3"></i>
                <h5>No se encontraron logros estudiantiles</h5>
                <p class="text-muted">
                    <?= !empty($busqueda) || !empty($filtroCurso) || $filtroVisible !== '' ?
                        'No hay resultados con los filtros aplicados.' :
                        'Aún no hay logros estudiantiles registrados.' ?>
                </p>
                <button type="button" class="btn btn-admin-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalNuevoLogro">
                    <i class="bi bi-plus-circle me-1"></i> Agregar nuevo logro estudiantil
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Nuevo Logro Estudiantil -->
<div class="modal fade" id="modalNuevoLogro" tabindex="-1" aria-labelledby="modalNuevoLogroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoLogroLabel">Nuevo Logro Estudiantil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="controllers/cargar-logro.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre_estudiante" class="form-label">Nombre del Estudiante *</label>
                                <input type="text" class="form-control" id="nombre_estudiante" name="nombre_estudiante" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="curso_division" class="form-label">Curso y División *</label>
                                <input type="text" class="form-control" id="curso_division" name="curso_division" required placeholder="Ej: 3° A, 5° B">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="logro" class="form-label">Tipo de Logro *</label>
                        <input type="text" class="form-control" id="logro" name="logro" required placeholder="Ej: Primer puesto en Olimpiada de Matemáticas">
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción *</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required placeholder="Detalles del logro, competencia, reconocimiento..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_logro" class="form-label">Fecha del Logro</label>
                        <input type="date" class="form-control" id="fecha_logro" name="fecha_logro">
                    </div>

                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto (opcional)</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png, image/gif, image/webp">
                        <div class="form-text">
                            Formatos aceptados: JPG, PNG, GIF, WebP. La imagen se redimensionará y se convertirá a WebP.
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="visible" name="visible" value="1" checked>
                        <label class="form-check-label" for="visible">Visible en el sitio web</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin-success">Guardar logro estudiantil</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Logro Estudiantil -->
<div class="modal fade" id="modalEditarLogro" tabindex="-1" aria-labelledby="modalEditarLogroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLogroLabel">Editar Logro Estudiantil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="controllers/editar-logro.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="foto_actual" id="edit_foto_actual">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_nombre_estudiante" class="form-label">Nombre del Estudiante *</label>
                                <input type="text" class="form-control" id="edit_nombre_estudiante" name="nombre_estudiante" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_curso_division" class="form-label">Curso y División *</label>
                                <input type="text" class="form-control" id="edit_curso_division" name="curso_division" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_logro" class="form-label">Tipo de Logro *</label>
                        <input type="text" class="form-control" id="edit_logro" name="logro" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción *</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_fecha_logro" class="form-label">Fecha del Logro</label>
                        <input type="date" class="form-control" id="edit_fecha_logro" name="fecha_logro">
                    </div>

                    <div class="mb-3">
                        <label for="edit_foto" class="form-label">Foto</label>
                        <div id="foto_preview_container" class="mb-2 d-none">
                            <img id="foto_preview" src="/placeholder.svg" alt="Foto actual" class="img-thumbnail" style="width: 150px; height: auto;">
                            <div class="form-text">Foto actual. Suba una nueva imagen para reemplazarla.</div>
                        </div>
                        <input type="file" class="form-control" id="edit_foto" name="foto" accept="image/jpeg, image/png, image/gif, image/webp">
                        <div class="form-text">
                            Formatos aceptados: JPG, PNG, GIF, WebP. La imagen se redimensionará y se convertirá a WebP.
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_visible" name="visible" value="1">
                        <label class="form-check-label" for="edit_visible">Visible en el sitio web</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin-success">Actualizar logro estudiantil</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script para manejar la eliminación de logros estudiantiles -->
<script>
    function confirmarEliminar(id, nombre) {
        Swal.fire({
            title: '¿Eliminar logro estudiantil?',
            text: `¿Estás seguro que deseas eliminar el logro de "${nombre}"?`,
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
                    text: 'Esta acción eliminará permanentemente el logro estudiantil y su imagen asociada.',
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
                            html: 'Por favor espera mientras se elimina el logro estudiantil',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Realizar la eliminación mediante AJAX
                        const formData = new FormData();
                        formData.append('id', id);
                        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

                        fetch('controllers/eliminar-logro.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Eliminado!',
                                        text: data.message,
                                        confirmButtonColor: '#28a745'
                                    }).then(() => {
                                        // Recargar la página para actualizar la lista
                                        window.location.href = 'logros.php?delete=ok';
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

    // Función para cargar datos en el modal de edición
    function cargarDatosLogro(id, nombre, curso, logro, descripcion, fecha, visible, foto) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre_estudiante').value = nombre;
        document.getElementById('edit_curso_division').value = curso;
        document.getElementById('edit_logro').value = logro;
        document.getElementById('edit_descripcion').value = descripcion;
        document.getElementById('edit_fecha_logro').value = fecha;
        document.getElementById('edit_visible').checked = visible === 1;
        document.getElementById('edit_foto_actual').value = foto;

        // Mostrar la foto actual si existe
        const previewContainer = document.getElementById('foto_preview_container');
        const preview = document.getElementById('foto_preview');

        if (foto && foto !== '') {
            preview.src = '../' + foto;
            previewContainer.classList.remove('d-none');
        } else {
            previewContainer.classList.add('d-none');
        }
    }
</script>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>

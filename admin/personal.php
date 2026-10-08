<?php
// Configuración de la página
$admin_page_title = 'Gestión de Personal Docente | EESO 225 San Martín';
$admin_page_description = 'Administra el personal docente de la escuela';

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
    $mensaje = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "Personal docente guardado correctamente";
    $tipoMensaje = "success";
    unset($_SESSION['success_message']);
} elseif (isset($_GET['update']) && $_GET['update'] == 'ok') {
    $mensaje = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "Personal docente actualizado correctamente";
    $tipoMensaje = "success";
    unset($_SESSION['success_message']);
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'ok') {
    $mensaje = "Personal docente eliminado correctamente";
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
                $mensaje = "El nombre es obligatorio.";
                break;
            case 'apellido':
                $mensaje = "El apellido es obligatorio.";
                break;
            case 'materia':
                $mensaje = "La materia es obligatoria.";
                break;
            case 'biografia':
                $mensaje = "La biografía es obligatoria.";
                break;
            case 'imagen':
                $mensaje = "La foto es obligatoria.";
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
$porPagina = ADMIN_PERSONAL_POR_PAGINA;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $porPagina;

// Filtros
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$filtroMateria = isset($_GET['materia']) ? $_GET['materia'] : '';
$filtroVisible = isset($_GET['visible']) ? $_GET['visible'] : '';

// Construir la consulta SQL con filtros
$sql = "SELECT * FROM personal_docente WHERE 1=1";
$params = [];
$types = "";

if (!empty($busqueda)) {
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR materia LIKE ? OR especialidad LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $types .= "ssss";
}

if (!empty($filtroMateria)) {
    $sql .= " AND materia = ?";
    $params[] = $filtroMateria;
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
$sql .= " ORDER BY nombre ASC, apellido ASC LIMIT ?, ?";
$params[] = $inicio;
$params[] = $porPagina;
$types .= "ii";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Obtener materias únicas para el filtro
$materiasResult = $db->query("SELECT DISTINCT materia FROM personal_docente ORDER BY materia");
$materias = [];
while ($row = $materiasResult->fetch_assoc()) {
    $materias[] = $row['materia'];
}
?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">Personal Docente <i class="bi bi-people"></i></h1>
        <nav aria-label="breadcrumb">
            <ol class="admin-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Personal Docente</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoPersonal">
        <i class="bi bi-plus-circle me-1"></i> Nuevo personal docente
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
                    <input type="text" class="form-control" id="busqueda" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Nombre, apellido, materia...">
                </div>
            </div>
            <div class="col-md-3">
                <label for="materia" class="form-label">Materia</label>
                <select class="form-select" id="materia" name="materia">
                    <option value="">Todas las materias</option>
                    <?php foreach ($materias as $materia): ?>
                        <option value="<?= htmlspecialchars($materia) ?>" <?= $filtroMateria === $materia ? 'selected' : '' ?>>
                            <?= htmlspecialchars($materia) ?>
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
                <a href="personal.php" class="btn btn-admin-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Listado de personal docente -->
<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="admin-card-title">
            <i class="bi bi-people me-2"></i>
            Listado de personal docente
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
                            <th>Nombre</th>
                            <th>Materia</th>
                            <th>Especialidad</th>
                            <th>Contacto</th>
                            <th>Visible</th>
                            <th>Foto</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($personal = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?= $personal['id'] ?></td>
                                <td>
                                    <div class="fw-medium">
                                        <?= htmlspecialchars($personal['nombre'] . ' ' . $personal['apellido']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($personal['materia']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= !empty($personal['especialidad']) ? htmlspecialchars($personal['especialidad']) : '-' ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php if (!empty($personal['email_institucional'])): ?>
                                            <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($personal['email_institucional']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($personal['telefono'])): ?>
                                            <div><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($personal['telefono']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $personal['visible'] ?
                                        '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sí</span>' :
                                        '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>No</span>' ?>
                                </td>
                                <td>
                                    <?php if (!empty($personal['foto'])): ?>
                                        <img src="../<?= htmlspecialchars($personal['foto']) ?>" class="img-thumbnail rounded-circle" style="height: 50px; width: 50px; object-fit: cover;" alt="Foto">
                                    <?php else: ?>
                                        <img src="../uploads/personal/Default_Profes.png" class="img-thumbnail rounded-circle" style="height: 50px; width: 50px; object-fit: cover;" alt="Foto por defecto">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-admin-warning me-1 btn-editar-personal"
                                            data-id="<?= $personal['id'] ?>"
                                            data-nombre="<?= htmlspecialchars($personal['nombre']) ?>"
                                            data-apellido="<?= htmlspecialchars($personal['apellido']) ?>"
                                            data-materia="<?= htmlspecialchars($personal['materia']) ?>"
                                            data-especialidad="<?= htmlspecialchars($personal['especialidad'] ?? '') ?>"
                                            data-email="<?= htmlspecialchars($personal['email_institucional'] ?? '') ?>"
                                            data-telefono="<?= htmlspecialchars($personal['telefono'] ?? '') ?>"
                                            data-biografia="<?= htmlspecialchars($personal['biografia']) ?>"
                                            data-visible="<?= $personal['visible'] ?>"
                                            data-foto="<?= htmlspecialchars($personal['foto'] ?? '') ?>"
                                            data-linkedin="<?= htmlspecialchars($personal['linkedin'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalEditarPersonal" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-admin-danger"
                                            onclick="confirmarEliminar(<?= $personal['id'] ?>, '<?= htmlspecialchars(addslashes($personal['nombre'] . ' ' . $personal['apellido'])) ?>')"
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
                                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&materia=<?= urlencode($filtroMateria) ?>&visible=<?= urlencode($filtroVisible) ?>" aria-label="Anterior">
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
                                    <a class="page-link" href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>&materia=<?= urlencode($filtroMateria) ?>&visible=<?= urlencode($filtroVisible) ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&materia=<?= urlencode($filtroMateria) ?>&visible=<?= urlencode($filtroVisible) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPaginas): ?>
                                <?php if ($endPage < $totalPaginas - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?= $totalPaginas ?>&busqueda=<?= urlencode($busqueda) ?>&materia=<?= urlencode($filtroMateria) ?>&visible=<?= urlencode($filtroVisible) ?>">
                                        <?= $totalPaginas ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&materia=<?= urlencode($filtroMateria) ?>&visible=<?= urlencode($filtroVisible) ?>" aria-label="Siguiente">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center p-5">
                <i class="bi bi-people fs-1 text-muted mb-3"></i>
                <h5>No se encontró personal docente</h5>
                <p class="text-muted">
                    <?= !empty($busqueda) || !empty($filtroMateria) || $filtroVisible !== '' ?
                        'No hay resultados con los filtros aplicados.' :
                        'Aún no hay personal docente registrado.' ?>
                </p>
                <button type="button" class="btn btn-admin-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalNuevoPersonal">
                    <i class="bi bi-plus-circle me-1"></i> Agregar nuevo personal docente
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Nuevo Personal Docente -->
<div class="modal fade" id="modalNuevoPersonal" tabindex="-1" aria-labelledby="modalNuevoPersonalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoPersonalLabel">Nuevo Personal Docente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="controllers/cargar-personal.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="apellido" class="form-label">Apellido *</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="materia" class="form-label">Materia *</label>
                                <input type="text" class="form-control" id="materia" name="materia" required placeholder="Ej: Matemáticas, Lengua, Historia">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="especialidad" class="form-label">Especialidad</label>
                                <input type="text" class="form-control" id="especialidad" name="especialidad" placeholder="Ej: Matemática Avanzada, Literatura">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email_institucional" class="form-label">Email Institucional</label>
                                <input type="email" class="form-control" id="email_institucional" name="email_institucional" placeholder="profesor@eeso225.edu.ar">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="(011) 1234-5678">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="biografia" class="form-label">Biografía Profesional *</label>
                        <textarea class="form-control" id="biografia" name="biografia" rows="4" required placeholder="Descripción profesional, experiencia, logros..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="linkedin" class="form-label">LinkedIn (opcional)</label>
                        <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/usuario">
                    </div>

                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto *</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png, image/gif, image/webp" required>
                        <div class="form-text">
                            Formatos aceptados: JPG, PNG, GIF, WebP. La imagen se redimensionará a 400x400 píxeles y se convertirá a WebP.
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="visible" name="visible" value="1" checked>
                        <label class="form-check-label" for="visible">Visible en el sitio web</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin-success">Guardar personal docente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Personal Docente -->
<div class="modal fade" id="modalEditarPersonal" tabindex="-1" aria-labelledby="modalEditarPersonalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarPersonalLabel">Editar Personal Docente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="controllers/editar-personal.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="foto_actual" id="edit_foto_actual">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_apellido" class="form-label">Apellido *</label>
                                <input type="text" class="form-control" id="edit_apellido" name="apellido" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_materia" class="form-label">Materia *</label>
                                <input type="text" class="form-control" id="edit_materia" name="materia" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_especialidad" class="form-label">Especialidad</label>
                                <input type="text" class="form-control" id="edit_especialidad" name="especialidad">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email_institucional" class="form-label">Email Institucional</label>
                                <input type="email" class="form-control" id="edit_email_institucional" name="email_institucional">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="edit_telefono" name="telefono">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_biografia" class="form-label">Biografía Profesional *</label>
                        <textarea class="form-control" id="edit_biografia" name="biografia" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_linkedin" class="form-label">LinkedIn (opcional)</label>
                        <input type="url" class="form-control" id="edit_linkedin" name="linkedin">
                    </div>

                    <div class="mb-3">
                        <label for="edit_foto" class="form-label">Foto</label>
                        <div id="foto_preview_container" class="mb-2">
                            <img id="foto_preview" src="../uploads/personal/Default_Profes.png" alt="Foto actual" class="img-thumbnail rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                            <div class="form-text">Foto actual. Suba una nueva imagen para reemplazarla.</div>
                        </div>
                        <input type="file" class="form-control" id="edit_foto" name="foto" accept="image/jpeg, image/png, image/gif, image/webp">
                        <div class="form-text">
                            Formatos aceptados: JPG, PNG, GIF, WebP. La imagen se redimensionará a 400x400 píxeles y se convertirá a WebP.
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_visible" name="visible" value="1">
                        <label class="form-check-label" for="edit_visible">Visible en el sitio web</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-admin-success">Actualizar personal docente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script para manejar la eliminación de personal docente -->
<script>
    function confirmarEliminar(id, nombre) {
        Swal.fire({
            title: '¿Eliminar personal docente?',
            text: `¿Estás seguro que deseas eliminar a "${nombre}"?`,
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
                    text: 'Esta acción eliminará permanentemente al personal docente y su foto asociada.',
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
                            html: 'Por favor espera mientras se elimina el personal docente',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Realizar la eliminación mediante AJAX
                        const formData = new FormData();
                        formData.append('id', id);
                        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

                        fetch('controllers/eliminar-personal.php', {
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
                                        window.location.href = 'personal.php?delete=ok';
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
    function cargarDatosPersonal(datos) {
        console.log('Cargando datos:', datos); // Debug
        
        document.getElementById('edit_id').value = datos.id;
        document.getElementById('edit_nombre').value = datos.nombre;
        document.getElementById('edit_apellido').value = datos.apellido;
        document.getElementById('edit_materia').value = datos.materia;
        document.getElementById('edit_especialidad').value = datos.especialidad;
        document.getElementById('edit_email_institucional').value = datos.email_institucional;
        document.getElementById('edit_telefono').value = datos.telefono;
        document.getElementById('edit_biografia').value = datos.biografia;
        document.getElementById('edit_linkedin').value = datos.linkedin;
        document.getElementById('edit_visible').checked = datos.visible === 1 || datos.visible === '1';
        document.getElementById('edit_foto_actual').value = datos.foto;

        // Mostrar la foto actual si existe
        const preview = document.getElementById('foto_preview');

        if (datos.foto && datos.foto !== '') {
            preview.src = '../' + datos.foto;
        } else {
            // Usar imagen por defecto
            preview.src = '../uploads/personal/Default_Profes.png';
        }
        
        console.log('Datos cargados en el formulario'); // Debug
    }

    // Manejar clic en botones de editar
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-editar-personal').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const datos = {
                    id: this.dataset.id,
                    nombre: this.dataset.nombre,
                    apellido: this.dataset.apellido,
                    materia: this.dataset.materia,
                    especialidad: this.dataset.especialidad,
                    email_institucional: this.dataset.email,
                    telefono: this.dataset.telefono,
                    biografia: this.dataset.biografia,
                    visible: parseInt(this.dataset.visible),
                    foto: this.dataset.foto,
                    linkedin: this.dataset.linkedin
                };
                
                cargarDatosPersonal(datos);
            });
        });
    });
</script>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>

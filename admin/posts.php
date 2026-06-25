<?php
// Configuración de la página
$admin_page_title = 'Gestión de Posts | ALPA Servicios Ambientales';
$admin_page_description = 'Administra los artículos y publicaciones del sitio web';

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

// Manejo de mensajes
$mensaje = '';
$tipoMensaje = '';

if (isset($_GET['success']) && $_GET['success'] == 1) {
  $mensaje = "Publicación guardada correctamente";
  $tipoMensaje = "success";
} elseif (isset($_GET['update']) && $_GET['update'] == 'ok') {
  $mensaje = "Publicación actualizada correctamente";
  $tipoMensaje = "success";
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'ok') {
  $mensaje = "Publicación eliminada correctamente";
  $tipoMensaje = "success";
}

// Paginación
$porPagina = 10; // Publicaciones por página
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

if (!empty($filtroCategoria)) {
  $sql .= " AND categoria = ?";
  $params[] = $filtroCategoria;
  $types .= "s";
}

if (!empty($busqueda)) {
  $sql .= " AND (titulo LIKE ? OR contenido LIKE ?)";
  $params[] = "%$busqueda%";
  $params[] = "%$busqueda%";
  $types .= "ss";
}

// Filtro por fecha
if (!empty($filtroFecha)) {
  $hoy = date('Y-m-d');
  $ayer = date('Y-m-d', strtotime('-1 day'));
  $hace7Dias = date('Y-m-d', strtotime('-7 days'));
  $hace30Dias = date('Y-m-d', strtotime('-30 days'));

  switch ($filtroFecha) {
    case 'hoy':
      $sql .= " AND DATE(fecha_publicacion) = ?";
      $params[] = $hoy;
      $types .= "s";
      break;
    case 'ayer':
      $sql .= " AND DATE(fecha_publicacion) = ?";
      $params[] = $ayer;
      $types .= "s";
      break;
    case 'ultimos7dias':
      $sql .= " AND DATE(fecha_publicacion) BETWEEN ? AND ?";
      $params[] = $hace7Dias;
      $params[] = $hoy;
      $types .= "ss";
      break;
    case 'ultimomes':
      $sql .= " AND DATE(fecha_publicacion) BETWEEN ? AND ?";
      $params[] = $hace30Dias;
      $params[] = $hoy;
      $types .= "ss";
      break;
    case 'personalizado':
      if (!empty($fechaInicio) && !empty($fechaFin)) {
        $sql .= " AND DATE(fecha_publicacion) BETWEEN ? AND ?";
        $params[] = $fechaInicio;
        $params[] = $fechaFin;
        $types .= "ss";
      }
      break;
  }
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
$sql .= " ORDER BY fecha_publicacion DESC, orden ASC LIMIT ?, ?";
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
    <h1 class="h3 mb-2">Publicaciones <i class="bi bi-file-earmark-text"></i></h1>
    <nav aria-label="breadcrumb">
      <ol class="admin-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
        <li class="breadcrumb-item active" aria-current="page">Publicaciones</li>
      </ol>
    </nav>
  </div>
  <a href="cargar-post.php" class="btn btn-admin-primary">
    <i class="bi bi-plus-circle me-1"></i> Nueva publicación
  </a>
</div>

<?php if (!empty($mensaje)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
        icon: '<?= $tipoMensaje ?>',
        title: '<?= $mensaje ?>'
      });
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
          <option value="">Todas</option>
          <option value="noticia" <?= $filtroCategoria === 'noticia' ? 'selected' : '' ?>>Noticia</option>
          <option value="evento" <?= $filtroCategoria === 'evento' ? 'selected' : '' ?>>Evento</option>
          <option value="curso" <?= $filtroCategoria === 'curso' ? 'selected' : '' ?>>Curso</option>
        </select>
      </div>
      <div class="col-md-3">
        <label for="filtro_fecha" class="form-label">Periodo</label>
        <select class="form-select" id="filtro_fecha" name="filtro_fecha" onchange="toggleFechasPersonalizadas()">
          <option value="">Todos los periodos</option>
          <option value="hoy" <?= $filtroFecha === 'hoy' ? 'selected' : '' ?>>Hoy</option>
          <option value="ayer" <?= $filtroFecha === 'ayer' ? 'selected' : '' ?>>Ayer</option>
          <option value="ultimos7dias" <?= $filtroFecha === 'ultimos7dias' ? 'selected' : '' ?>>Últimos 7 días</option>
          <option value="ultimomes" <?= $filtroFecha === 'ultimomes' ? 'selected' : '' ?>>Último mes</option>
          <option value="personalizado" <?= $filtroFecha === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-admin-primary me-2">
          <i class="bi bi-filter"></i> Filtrar
        </button>
        <a href="posts.php" class="btn btn-admin-secondary">
          <i class="bi bi-x-circle"></i> Limpiar
        </a>
      </div>

      <!-- Fechas personalizadas (inicialmente ocultas) -->
      <div class="col-md-6" id="fecha_inicio_container" style="display: <?= $filtroFecha === 'personalizado' ? 'block' : 'none' ?>;">
        <label for="fecha_inicio" class="form-label">Fecha inicio</label>
        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>">
      </div>
      <div class="col-md-6" id="fecha_fin_container" style="display: <?= $filtroFecha === 'personalizado' ? 'block' : 'none' ?>;">
        <label for="fecha_fin" class="form-label">Fecha fin</label>
        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>">
      </div>
    </form>
  </div>
</div>

<!-- Listado de publicaciones -->
<div class="admin-card">
  <div class="admin-card-header">
    <h5 class="admin-card-title">
      <i class="bi bi-file-earmark-text me-2"></i>
      Listado de publicaciones
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
              <th style="min-width: 250px;">Título</th>
              <th>Categoría</th>
              <th>Visible</th>
              <th>Fecha</th>
              <th>Imagen</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($post = $resultado->fetch_assoc()): ?>
              <tr>
                <td><?= $post['id'] ?></td>
                <td>
                  <div class="fw-medium text-truncate" style="max-width: 300px;">
                    <?= htmlspecialchars($post['titulo']) ?>
                  </div>
                </td>
                <td>
                  <?php
                  $badgeClass = 'bg-info';
                  if ($post['categoria'] === 'evento') {
                    $badgeClass = 'bg-warning';
                  } elseif ($post['categoria'] === 'curso') {
                    $badgeClass = 'bg-primary';
                  }
                  ?>
                  <span class="badge <?= $badgeClass ?>"><?= ucfirst($post['categoria']) ?></span>
                </td>
                <td>
                  <?= $post['visible'] ?
                    '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sí</span>' :
                    '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>No</span>' ?>
                </td>
                <td><?= date('d/m/Y', strtotime($post['fecha_publicacion'])) ?></td>
                <td>
                  <?php if (!empty($post['imagen'])): ?>
                    <img src="../<?= htmlspecialchars($post['imagen']) ?>" class="img-thumbnail" style="height: 50px; width: auto;" alt="Miniatura">
                  <?php else: ?>
                    <span class="badge bg-light text-dark">Sin imagen</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex justify-content-end">
                    <a href="../posts-detail.php?id=<?= $post['id'] ?>" target="_blank" class="btn btn-sm btn-admin-info me-1" title="Ver">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="editar-post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-admin-warning me-1" title="Editar">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-admin-danger"
                      onclick="confirmarEliminar(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['titulo'])) ?>')"
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
                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>" aria-label="Anterior">
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
                  <a class="page-link" href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>">1</a>
                </li>
                <?php if ($startPage > 2): ?>
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
              <?php endif; ?>

              <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                  <a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>">
                    <?= $i ?>
                  </a>
                </li>
              <?php endfor; ?>

              <?php if ($endPage < $totalPaginas): ?>
                <?php if ($endPage < $totalPaginas - 1): ?>
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                  <a class="page-link" href="?pagina=<?= $totalPaginas ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>">
                    <?= $totalPaginas ?>
                  </a>
                </li>
              <?php endif; ?>

              <li class="page-item <?= ($pagina >= $totalPaginas) ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busqueda=<?= urlencode($busqueda) ?>&categoria=<?= urlencode($filtroCategoria) ?>&filtro_fecha=<?= urlencode($filtroFecha) ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>" aria-label="Siguiente">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="text-center p-5">
        <i class="bi bi-search fs-1 text-muted mb-3"></i>
        <h5>No se encontraron publicaciones</h5>
        <p class="text-muted">
          <?= !empty($busqueda) || !empty($filtroCategoria) || !empty($filtroFecha) ?
            'No hay resultados con los filtros aplicados.' :
            'Aún no hay publicaciones registradas.' ?>
        </p>
        <a href="cargar-post.php" class="btn btn-admin-primary mt-2">
          <i class="bi bi-plus-circle me-1"></i> Crear nueva publicación
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Script para manejar la eliminación de posts y el filtro de fechas -->
<script>
  // Función para mostrar/ocultar campos de fechas personalizadas
  function toggleFechasPersonalizadas() {
    const filtroFecha = document.getElementById('filtro_fecha').value;
    const fechaInicioContainer = document.getElementById('fecha_inicio_container');
    const fechaFinContainer = document.getElementById('fecha_fin_container');

    if (filtroFecha === 'personalizado') {
      fechaInicioContainer.style.display = 'block';
      fechaFinContainer.style.display = 'block';
    } else {
      fechaInicioContainer.style.display = 'none';
      fechaFinContainer.style.display = 'none';
    }
  }

  // Inicializar fechas si es necesario
  document.addEventListener('DOMContentLoaded', function() {
    // Si no hay fechas establecidas y se selecciona personalizado, establecer fechas por defecto
    const filtroFecha = document.getElementById('filtro_fecha').value;
    if (filtroFecha === 'personalizado') {
      const fechaInicio = document.getElementById('fecha_inicio');
      const fechaFin = document.getElementById('fecha_fin');

      if (!fechaInicio.value) {
        // Establecer fecha de inicio como el primer día del mes actual
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        fechaInicio.value = primerDiaMes.toISOString().split('T')[0];
      }

      if (!fechaFin.value) {
        // Establecer fecha fin como hoy
        const hoy = new Date();
        fechaFin.value = hoy.toISOString().split('T')[0];
      }
    }
  });

  function confirmarEliminar(id, titulo) {
    Swal.fire({
      title: '¿Eliminar publicación?',
      text: `¿Estás seguro que deseas eliminar la publicación "${titulo}"?`,
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
          text: 'Esta acción eliminará permanentemente la publicación y todos sus archivos asociados.',
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
              html: 'Por favor espera mientras se elimina la publicación',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            // Realizar la eliminación mediante AJAX
            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

            fetch('controllers/eliminar-post.php', {
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
                    window.location.href = 'posts.php?delete=ok';
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
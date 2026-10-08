<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title = ESCUELA_NOMBRE_CORTO . ' - Personal Docente';
$page_description = 'Conocé a todo nuestro equipo docente';
require_once __DIR__ . '/header.php';

?><link rel="stylesheet" href="assets/css/personal.css"><?php

// Función helper para truncar texto
if (!function_exists('truncarTexto')) {
    function truncarTexto($texto, $limite = 120) {
        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }
        return mb_substr($texto, 0, $limite) . '...';
    }
}

// Definir jerarquía de cargos docentes para ordenamiento
function obtenerJerarquia($materia) {
    $jerarquias = [
        'Director' => 1,
        'Directora' => 1,
        'Vicedirector' => 2,
        'Vicedirectora' => 2,
        'Secretario' => 3,
        'Secretaria' => 3,
        'Secretario Técnico' => 4,
        'Coordinador' => 5,
        'Coordinadora' => 5,
        'Preceptor' => 6,
        'Preceptora' => 6,
        'Bibliotecario' => 7,
        'Bibliotecaria' => 7,
    ];
    
    // Si la materia está en el array de jerarquías, retornar su valor
    if (isset($jerarquias[$materia])) {
        return $jerarquias[$materia];
    }
    
    // Si no es un cargo administrativo, es docente (jerarquía 10)
    return 10;
}

// Obtener conexión
$mysqli = get_mysqli();
if (!$mysqli) {
    die('<div class="alert alert-danger">Error de conexión a la base de datos</div>');
}

// Filtro por materia
$filtroMateria = isset($_GET['materia']) ? trim($_GET['materia']) : '';

// Construir consulta SQL
$sql = "SELECT * FROM personal_docente WHERE visible = 1";
$params = [];
$types = "";

if (!empty($filtroMateria)) {
    $sql .= " AND materia = ?";
    $params[] = $filtroMateria;
    $types .= "s";
}

$sql .= " ORDER BY apellido ASC, nombre ASC";

// Ejecutar consulta
$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
$personal = $resultado->fetch_all(MYSQLI_ASSOC);

// Ordenar por jerarquía en PHP (más flexible que en SQL)
usort($personal, function($a, $b) {
    $jerarquiaA = obtenerJerarquia($a['materia']);
    $jerarquiaB = obtenerJerarquia($b['materia']);
    
    if ($jerarquiaA != $jerarquiaB) {
        return $jerarquiaA - $jerarquiaB;
    }
    
    // Si tienen la misma jerarquía, ordenar por apellido
    return strcmp($a['apellido'], $b['apellido']);
});

// Obtener lista de materias únicas para el filtro
$materiasStmt = $mysqli->query("SELECT DISTINCT materia FROM personal_docente WHERE visible = 1 ORDER BY materia");
$materias = $materiasStmt->fetch_all(MYSQLI_ASSOC);

?>

<!-- Sección de Personal Docente -->
<section id="personal-docente" class="py-5">
    <div class="container" data-aos="fade-up">

        <!-- Botón volver al inicio -->
        <div class="mb-3">
            <a href="index.php" class="btn btn-outline-violeta">
                <i class="bi bi-arrow-left me-2"></i>Volver al inicio
            </a>
        </div>

        <div class="section-header text-center mb-4">
            <h1 style="color: var(--color-violeta); font-family: 'Montserrat', sans-serif;">Personal Docente</h1>
            <p class="lead">Conocé a todos los profesionales que forman parte de nuestra comunidad educativa.</p>
            
            <div class="personal-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-people-fill text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    Nuestro equipo docente está conformado por profesionales comprometidos con la enseñanza y el acompañamiento pedagógico. A través de su experiencia y dedicación, garantizan una educación de calidad, promoviendo el aprendizaje significativo y el crecimiento personal de cada estudiante en la E.E.S.O. Nº 225 General José de San Martín.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-6 col-md-8">
                <form method="GET" class="d-flex gap-2">
                    <select name="materia" class="form-select form-select-lg" onchange="this.form.submit()">
                        <option value="">Todas las materias / cargos</option>
                        <?php foreach ($materias as $mat): ?>
                            <option value="<?= htmlspecialchars($mat['materia']) ?>" 
                                    <?= $filtroMateria === $mat['materia'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mat['materia']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($filtroMateria)): ?>
                        <a href="personal.php" class="btn btn-outline-violeta btn-lg">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (!empty($personal)): ?>
            <!-- Grid de personal docente -->
            <div class="row gy-4">
                <?php foreach ($personal as $index => $miembro): ?>
                <div class="col-xl-3 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="<?= 50 * ($index + 1) ?>">
                    <div class="team-card h-100" data-bs-toggle="modal" data-bs-target="#modalPersonalFull<?= $miembro['id'] ?>" role="button">
                        <div class="team-card-img">
                            <img src="<?= !empty($miembro['foto']) ? htmlspecialchars($miembro['foto']) : 'uploads/personal/Default_Profes.png' ?>" 
                                class="img-fluid team-profile-photo"
                                 alt="Foto de <?= htmlspecialchars($miembro['nombre']) ?> <?= htmlspecialchars($miembro['apellido']) ?>">
                        </div>
                        
                        <div class="team-card-body">
                            <h4 class="team-card-name"><?= htmlspecialchars($miembro['nombre']) ?> <?= htmlspecialchars($miembro['apellido']) ?></h4>
                            <span class="team-card-subject"><?= htmlspecialchars($miembro['materia']) ?></span>
                            
                            <?php if (!empty($miembro['especialidad'])): ?>
                                <p class="team-card-specialty"><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($miembro['especialidad']) ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($miembro['biografia'])): ?>
                                <p class="team-card-bio"><?= truncarTexto(html_entity_decode(strip_tags($miembro['biografia']), ENT_QUOTES, 'UTF-8'), 100) ?></p>
                            <?php endif; ?>
                            
                            <div class="team-card-footer">
                                <span class="text-violeta fw-semibold"><i class="bi bi-info-circle me-1"></i>Ver más</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Modales de detalle para cada docente -->
            <?php foreach ($personal as $miembro): ?>
            <div class="modal fade" id="modalPersonalFull<?= $miembro['id'] ?>" tabindex="-1" aria-labelledby="modalPersonalFullLabel<?= $miembro['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    <img src="<?= !empty($miembro['foto']) ? htmlspecialchars($miembro['foto']) : 'uploads/personal/Default_Profes.png' ?>" 
                                         class="img-fluid rounded-3 shadow-sm mb-3 team-profile-photo"
                                         alt="Foto de <?= htmlspecialchars($miembro['nombre']) ?> <?= htmlspecialchars($miembro['apellido']) ?>">
                                    
                                    <?php if (!empty($miembro['linkedin'])): ?>
                                        <a href="<?= htmlspecialchars($miembro['linkedin']) ?>" target="_blank" class="btn btn-outline-violeta btn-sm w-100">
                                            <i class="bi bi-linkedin me-2"></i>Ver LinkedIn
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-8">
                                    <h3 class="mb-1" style="color: var(--color-violeta); font-family: 'Montserrat', sans-serif;">
                                        <?= htmlspecialchars($miembro['nombre']) ?> <?= htmlspecialchars($miembro['apellido']) ?>
                                    </h3>
                                    <p class="text-muted mb-3"><strong><?= htmlspecialchars($miembro['materia']) ?></strong></p>
                                    
                                    <?php if (!empty($miembro['especialidad'])): ?>
                                        <div class="mb-3">
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($miembro['especialidad']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($miembro['biografia'])): ?>
                                        <div class="mb-3">
                                            <h6 class="fw-bold mb-2" style="color: var(--color-gris);">Sobre mí</h6>
                                            <p class="text-muted" style="text-align: justify; line-height: 1.6;">
                                                <?= nl2br(htmlspecialchars($miembro['biografia'])) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="contact-info">
                                        <?php if (!empty($miembro['email_institucional'])): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-envelope text-violeta me-2"></i>
                                                <a href="mailto:<?= htmlspecialchars($miembro['email_institucional']) ?>" class="text-decoration-none text-muted">
                                                    <?= htmlspecialchars($miembro['email_institucional']) ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($miembro['telefono'])): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-telephone text-violeta me-2"></i>
                                                <span class="text-muted"><?= htmlspecialchars($miembro['telefono']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="text-center p-5">
                <i class="bi bi-people fs-1 text-muted mb-3"></i>
                <h5>No se encontró personal docente</h5>
                <p class="text-muted">
                    <?= !empty($filtroMateria) ? 'No hay docentes con ese filtro. ' : '' ?>
                    Ajustá los filtros o volvé más tarde.
                </p>
                <?php if (!empty($filtroMateria)): ?>
                    <a href="personal.php" class="btn btn-violeta mt-2">Ver todo el personal</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/footer.php';

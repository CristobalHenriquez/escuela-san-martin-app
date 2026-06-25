<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title = ESCUELA_NOMBRE_CORTO . ' - Logros Estudiantiles';
$page_description = 'Conocé todos los logros y reconocimientos de nuestros estudiantes';
require_once __DIR__ . '/header.php';

// Función helper para truncar texto
if (!function_exists('truncarTexto')) {
    function truncarTexto($texto, $limite = 120) {
        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }
        return mb_substr($texto, 0, $limite) . '...';
    }
}

// Obtener logros estudiantiles
$mysqli = get_mysqli();
if (!$mysqli) {
    die('<div class="alert alert-danger">Error de conexión a la base de datos</div>');
}

$stmt = $mysqli->prepare("SELECT * FROM logros_estudiantiles WHERE visible = 1 ORDER BY fecha_logro DESC, fecha_creacion DESC");
$stmt->execute();
$logros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!-- Sección de Logros Estudiantiles -->
<section id="logros-estudiantiles" class="py-5">
    <div class="container" data-aos="fade-up">

        <!-- Botón volver al inicio -->
        <div class="mb-3">
            <a href="index.php" class="btn btn-outline-violeta">
                <i class="bi bi-arrow-left me-2"></i>Volver al inicio
            </a>
        </div>

        <div class="section-header text-center mb-4">
            <h1 style="color: var(--color-violeta); font-family: 'Montserrat', sans-serif;">Logros Estudiantiles</h1>
            <p class="lead">Celebramos los éxitos y reconocimientos alcanzados por nuestros estudiantes.</p>
            
            <div class="logros-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-trophy-fill text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    Cada logro representa el esfuerzo, la dedicación y el talento de nuestros estudiantes. Estos reconocimientos son el resultado del trabajo conjunto entre docentes, familias y alumnos, reflejando la calidad educativa que caracteriza a la E.E.S.O. Nº 225 "La San Martín". Celebramos no solo los resultados, sino también el proceso de crecimiento y aprendizaje que los hace posibles.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($logros)): ?>
            <!-- Grid de logros -->
            <div class="row gy-4">
                <?php foreach ($logros as $index => $logro): ?>
                <div class="col-xl-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="<?= 50 * ($index + 1) ?>">
                    <div class="achievement-item h-100" data-bs-toggle="modal" data-bs-target="#modalLogro<?= $logro['id'] ?>" role="button">
                        
                        <?php if (!empty($logro['foto'])): ?>
                        <div class="achievement-img">
                            <img src="<?= htmlspecialchars($logro['foto']) ?>" class="img-fluid"
                                 alt="<?= htmlspecialchars($logro['logro']) ?>">
                        </div>
                        <?php endif; ?>

                        <div class="achievement-content">
                            <div class="achievement-category">
                                <span class="badge bg-primary mb-2">
                                    <i class="bi bi-award"></i> Logro Estudiantil
                                </span>
                            </div>
                            
                            <h4 class="achievement-title">
                                <?= htmlspecialchars($logro['nombre_estudiante']) ?>
                            </h4>
                            
                            <h6 class="text-muted">
                                <?= htmlspecialchars($logro['curso_division']) ?>
                            </h6>
                            
                            <div class="logro-type mb-3">
                                <span class="badge bg-success">
                                    <i class="bi bi-trophy"></i> <?= htmlspecialchars($logro['logro']) ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($logro['descripcion'])): ?>
                                <p class="achievement-description">
                                    <?= truncarTexto(strip_tags($logro['descripcion']), 100) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($logro['fecha_logro'])): ?>
                            <div class="achievement-meta">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-check text-primary"></i>
                                    <?= date('d/m/Y', strtotime($logro['fecha_logro'])) ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="achievement-footer mt-auto">
                                <span class="text-violeta fw-semibold"><i class="bi bi-info-circle me-1"></i>Ver más</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Modales de detalle para cada logro -->
            <?php foreach ($logros as $logro): ?>
            <div class="modal fade" id="modalLogro<?= $logro['id'] ?>" tabindex="-1" aria-labelledby="modalLogroLabel<?= $logro['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    <?php if (!empty($logro['foto'])): ?>
                                        <img src="<?= htmlspecialchars($logro['foto']) ?>" class="img-fluid rounded-3 shadow-sm mb-3"
                                             alt="<?= htmlspecialchars($logro['logro']) ?>">
                                    <?php else: ?>
                                        <div class="placeholder-img d-flex align-items-center justify-content-center bg-light rounded-3 shadow-sm mb-3" style="height: 280px;">
                                            <i class="bi bi-trophy text-muted" style="font-size: 5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-8">
                                    <h3 class="mb-1" style="color: var(--color-violeta); font-family: 'Montserrat', sans-serif;">
                                        <?= htmlspecialchars($logro['nombre_estudiante']) ?>
                                    </h3>
                                    <p class="text-muted mb-3"><strong><?= htmlspecialchars($logro['curso_division']) ?></strong></p>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-success fs-6">
                                            <i class="bi bi-trophy me-1"></i><?= htmlspecialchars($logro['logro']) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($logro['descripcion'])): ?>
                                        <div class="mb-3">
                                            <h6 class="fw-bold mb-2" style="color: var(--color-gris);">Descripción del logro</h6>
                                            <p class="text-muted" style="text-align: justify; line-height: 1.6;">
                                                <?= nl2br(htmlspecialchars($logro['descripcion'])) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($logro['fecha_logro'])): ?>
                                        <div class="achievement-info">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-calendar-check text-violeta me-2"></i>
                                                <span class="text-muted">
                                                    <strong>Fecha del logro:</strong> <?= date('d/m/Y', strtotime($logro['fecha_logro'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="text-center p-5">
                <i class="bi bi-trophy fs-1 text-warning mb-3"></i>
                <h5>Próximos logros en camino</h5>
                <p class="text-muted">
                    Nuestros estudiantes están trabajando para nuevos éxitos que pronto aparecerán aquí.
                </p>
                <a href="index.php" class="btn btn-violeta mt-3">
                    <i class="bi bi-house me-2"></i>Volver al inicio
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/footer.php';

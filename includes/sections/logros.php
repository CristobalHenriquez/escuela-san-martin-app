<?php
/**
 * Sección de Logros - Frontend
 * EESO 225 San Martín
 */

// Obtener logros estudiantiles destacados
$logros_destacados = [];
if (function_exists('get_mysqli')) {
    $mysqli = get_mysqli();
    if ($mysqli) {
        $stmt = $mysqli->prepare("SELECT * FROM logros_estudiantiles WHERE visible = 1 ORDER BY fecha_creacion DESC LIMIT 6");
        if ($stmt) {
            $stmt->execute();
            $logros_destacados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>

<!-- ======= Logros Section ======= -->
<section id="logros" class="section-bg">
    <div class="container" data-aos="fade-up">

        <div class="section-header text-center">
            <h2>Nuestros Logros</h2>
            <p class="lead mb-4">Conocé los éxitos y reconocimientos que han alcanzado nuestros estudiantes y nuestra institución.</p>
            
            <div class="logros-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-trophy-fill text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    Cada logro representa el esfuerzo, la dedicación y el talento de nuestros estudiantes. Estos reconocimientos son el resultado del trabajo conjunto entre docentes, familias y alumnos, reflejando la calidad educativa que caracteriza a la E.E.S.O. Nº 225. Celebramos no solo los resultados, sino también el proceso de crecimiento y aprendizaje que los hace posibles.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($logros_destacados)): ?>
        <div class="row gy-4">

            <?php foreach ($logros_destacados as $index => $logro): ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= 100 * ($index + 1) ?>">
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
                        
                        <div class="logro-type mb-2">
                            <span class="badge bg-success">
                                <i class="bi bi-trophy"></i> <?= htmlspecialchars($logro['logro']) ?>
                            </span>
                        </div>
                        
                        <?php 
                        // Función para truncar texto
                        $descripcion = $logro['descripcion'];
                        $descripcion_truncada = strlen($descripcion) > 150 ? substr($descripcion, 0, 150) . '...' : $descripcion;
                        ?>
                        
                        <p class="achievement-description">
                            <?= htmlspecialchars($descripcion_truncada) ?>
                        </p>
                        
                        <?php if (!empty($logro['fecha_logro'])): ?>
                        <div class="achievement-meta">
                            <small class="text-muted">
                                <i class="bi bi-calendar-check text-primary"></i>
                                <?= date('d/m/Y', strtotime($logro['fecha_logro'])) ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Indicador de click -->
                    <div class="achievement-overlay">
                        <i class="bi bi-eye text-white fs-4"></i>
                    </div>
                </div>
            </div><!-- End Achievement Item -->
            <?php endforeach; ?>

        </div>

        <div class="text-center mt-5">
            <a href="logros.php" class="btn-get-started">Ver todos los logros</a>
        </div>

        <!-- Modales para cada logro -->
        <?php foreach ($logros_destacados as $logro): ?>
        <div class="modal fade" id="modalLogro<?= $logro['id'] ?>" tabindex="-1" aria-labelledby="modalLogro<?= $logro['id'] ?>Label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background: var(--color-violeta); color: white;">
                        <h5 class="modal-title" id="modalLogro<?= $logro['id'] ?>Label">
                            <i class="bi bi-trophy me-2"></i><?= htmlspecialchars($logro['logro']) ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <?php if (!empty($logro['foto'])): ?>
                            <div class="col-md-6">
                                <img src="<?= htmlspecialchars($logro['foto']) ?>" class="img-fluid rounded shadow-sm mb-3" 
                                     alt="<?= htmlspecialchars($logro['logro']) ?>"
                                     style="width: 100%; object-fit: contain; max-height: 300px;">
                            </div>
                            <div class="col-md-6">
                            <?php else: ?>
                            <div class="col-12">
                            <?php endif; ?>
                                <div class="logro-details">
                                    <h4 class="text-violeta mb-3">
                                        <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($logro['nombre_estudiante']) ?>
                                    </h4>
                                    
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="bi bi-mortarboard me-2"></i>Curso:
                                        </h6>
                                        <div class="badge bg-primary p-2" style="font-size: 0.9rem; white-space: normal; text-align: left; line-height: 1.4; word-wrap: break-word; max-width: 100%; display: inline-block;">
                                            <?= htmlspecialchars($logro['curso_division']) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($logro['fecha_logro'])): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="bi bi-calendar-check me-2"></i>Fecha del logro:
                                        </h6>
                                        <span class="text-primary fw-bold">
                                            <?= date('d/m/Y', strtotime($logro['fecha_logro'])) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="logro-type-modal">
                                        <h6 class="text-muted mb-2">
                                            <i class="bi bi-award me-2"></i>Tipo de logro:
                                        </h6>
                                        <div class="badge bg-success p-2" style="font-size: 0.9rem; white-space: normal; text-align: left; line-height: 1.4; word-wrap: break-word; max-width: 100%; display: inline-block;">
                                            <?= htmlspecialchars($logro['logro']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="text-violeta">
                                    <i class="bi bi-file-text me-2"></i>Descripción:
                                </h6>
                                <div class="descripcion-completa p-3 rounded" style="background: #f8f9fa; border-left: 4px solid var(--color-violeta);">
                                    <?= nl2br(htmlspecialchars($logro['descripcion'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="logros.php" class="btn btn-outline-violeta">
                            <i class="bi bi-trophy me-2"></i>Ver todos los logros
                        </a>
                        <button type="button" class="btn btn-violeta" data-bs-dismiss="modal">
                            <i class="bi bi-check me-2"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php else: ?>
        <div class="no-logros-container text-center p-5">
            <div class="mx-auto" style="max-width: 600px;">
                <i class="bi bi-trophy fs-1 text-muted mb-4" style="color: var(--color-violeta) !important; opacity: 0.6;"></i>
                <h5 class="mb-3" style="color: var(--color-violeta);">Construyendo Éxitos Juntos</h5>
                <p class="text-muted mb-4" style="line-height: 1.6;">
                    Nuestros estudiantes están trabajando día a día para alcanzar nuevos logros que pronto aparecerán aquí. Cada proyecto, cada esfuerzo y cada desafío superado es un paso hacia el reconocimiento del talento y la dedicación que caracteriza a nuestra comunidad educativa.
                </p>
                <div class="logros-preview row g-3 mt-4">
                    <div class="col-md-4">
                        <div class="logro-feature p-3 rounded-3" style="background: rgba(106, 27, 154, 0.05);">
                            <i class="bi bi-award-fill text-primary mb-2" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <h6 class="mb-1">Académicos</h6>
                            <small class="text-muted">Excelencia en estudios</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="logro-feature p-3 rounded-3" style="background: rgba(106, 27, 154, 0.05);">
                            <i class="bi bi-palette-fill text-success mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Artísticos</h6>
                            <small class="text-muted">Creatividad y expresión</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="logro-feature p-3 rounded-3" style="background: rgba(106, 27, 154, 0.05);">
                            <i class="bi bi-people-fill text-info mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Sociales</h6>
                            <small class="text-muted">Compromiso comunitario</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section><!-- End Logros Section -->
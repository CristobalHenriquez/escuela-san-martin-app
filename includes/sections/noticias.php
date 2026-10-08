<?php
/**
 * Sección de Noticias Recientes - Frontend
 * EESO 225 San Martín
 */

// Obtener noticias recientes
$noticias_recientes = [];
$total_noticias = 0;
if (function_exists('get_mysqli')) {
    $mysqli = get_mysqli();
    if ($mysqli) {
        // Traer sólo las 3 más recientes para el home
        $stmt = $mysqli->prepare("SELECT * FROM posts WHERE visible = 1 AND categoria = 'noticia' ORDER BY fecha_publicacion DESC LIMIT 3");
        if ($stmt) {
            $stmt->execute();
            $noticias_recientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // Contar cuántas noticias hay en total para decidir si mostramos el CTA "Ver todas"
        if ($countStmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM posts WHERE visible = 1 AND categoria = 'noticia'")) {
            $countStmt->execute();
            $res = $countStmt->get_result()->fetch_assoc();
            $total_noticias = (int)($res['total'] ?? 0);
        }
    }
}

// Definir clases de columna según cantidad de noticias para un layout más equilibrado
$colClass = 'col-xl-4 col-md-6';
$count = is_array($noticias_recientes) ? count($noticias_recientes) : 0;
if ($count === 1) {
    $colClass = 'col-lg-8 col-xl-7 mx-auto';
} elseif ($count === 2) {
    $colClass = 'col-md-6 col-lg-6';
}
$cardSizeClass = ($count === 1) ? ' news-card-lg' : '';
?>

<!-- ======= Recent Blog Posts Section ======= -->
<section id="recent-posts" class="recent-posts sections-bg">
    <div class="container" data-aos="fade-up">

        <div class="section-header text-center">
            <h2>Noticias Recientes</h2>
            <p class="lead mb-4">Enterate de las últimas novedades y eventos de nuestra comunidad educativa.</p>
            
            <div class="noticias-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-newspaper text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    En esta sección podrás encontrar las últimas novedades, proyectos y eventos que se desarrollan en la institución. Es un espacio para mantenerte al día con las actividades de la comunidad educativa y conocer los logros y avances de la escuela.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($noticias_recientes)): ?>
        <div class="row gy-4">

            <?php foreach ($noticias_recientes as $noticia): ?>
            <div class="<?= $colClass ?>">
                <article class="news-card h-100<?= $cardSizeClass ?>">
                    <div class="post-img">
                        <?php if (!empty($noticia['imagen'])): ?>
                            <img src="<?= htmlspecialchars($noticia['imagen']) ?>" 
                                 alt="Imagen de la noticia: <?= htmlspecialchars($noticia['titulo']) ?>" 
                                 class="img-fluid news-thumb">
                        <?php else: ?>
                            <div class="placeholder-img d-flex align-items-center justify-content-center bg-light news-thumb">
                                <i class="bi bi-newspaper text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <span class="badge bg-light text-dark border fw-semibold">Noticia</span>
                        <time class="text-muted small" datetime="<?= htmlspecialchars($noticia['fecha_publicacion']) ?>">
                            <?= date('d M Y', strtotime($noticia['fecha_publicacion'])) ?>
                        </time>
                    </div>

                    <h3 class="news-title mt-2">
                        <a href="noticia.php?id=<?= $noticia['id'] ?>" class="stretched-link"><?= htmlspecialchars($noticia['titulo']) ?></a>
                    </h3>

                    <?php 
                        $resumen = isset($noticia['contenido']) ? html_entity_decode(strip_tags($noticia['contenido']), ENT_QUOTES, 'UTF-8') : '';
                        if (function_exists('truncarTexto')) {
                            $resumen = truncarTexto($resumen, 160);
                        } else {
                            $resumen = mb_substr($resumen, 0, 160) . (mb_strlen($resumen) > 160 ? '…' : '');
                        }
                    ?>
                    <p class="news-excerpt text-muted mb-0"><?= htmlspecialchars($resumen) ?></p>

                </article>
            </div><!-- End post list item -->
            <?php endforeach; ?>

        </div>

        <?php if ($total_noticias > 3): ?>
            <div class="text-center mt-4">
                <a href="noticias.php" class="btn-get-started">Ver todas las noticias</a>
            </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="no-news-container text-center p-5">
            <div class="mx-auto" style="max-width: 600px;">
                <i class="bi bi-newspaper fs-1 text-muted mb-4" style="color: var(--color-violeta) !important; opacity: 0.6;"></i>
                <h5 class="mb-3" style="color: var(--color-violeta);">Próximamente: Nuestras Novedades</h5>
                <p class="text-muted mb-4" style="line-height: 1.6;">
                    Estamos preparando contenido sobre las actividades, proyectos y eventos más importantes de nuestra comunidad educativa. Pronto podrás mantenerte al día con todos los avances y logros de la E.E.S.O. Nº 225.
                </p>
                <div class="features-preview row g-3 mt-4">
                    <div class="col-md-4">
                        <div class="feature-item p-3 rounded-3" style="background: rgba(106, 27, 154, 0.05);">
                            <i class="bi bi-calendar-event text-primary mb-2" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <h6 class="mb-1">Eventos</h6>
                            <small class="text-muted">Actividades y celebraciones</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-item p-3 rounded-3" style="background: rgba(106, 27, 154, 0.05);">
                            <i class="bi bi-trophy text-success mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Logros</h6>
                            <small class="text-muted">Reconocimientos y éxitos</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-item p-3 rounded-3" style="background: rgba(106, 27, 154, 0.05);">
                            <i class="bi bi-lightbulb text-warning mb-2" style="font-size: 1.5rem;"></i>
                            <h6 class="mb-1">Proyectos</h6>
                            <small class="text-muted">Iniciativas educativas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section><!-- End Recent Blog Posts Section -->
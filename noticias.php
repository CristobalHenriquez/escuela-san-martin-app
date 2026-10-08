<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title = ESCUELA_NOMBRE_CORTO . ' - Noticias';
require_once __DIR__ . '/header.php';

$pdo = conectarDB();
$stmt = $pdo->query("SELECT id, titulo, imagen, fecha_publicacion, contenido, categoria FROM posts WHERE visible = 1 AND categoria = 'noticia' ORDER BY fecha_publicacion DESC LIMIT 30");
$posts = $stmt->fetchAll();

?>

<section id="news-list" class="news-list my-4">
    <!-- Botón volver al inicio -->
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-violeta">
            <i class="bi bi-arrow-left me-2"></i>Volver al inicio
        </a>
    </div>
    
    <div class="row">
        <div class="col-12 mb-2">
            <h2>Noticias</h2>
            <p class="text-muted">Enterate de las últimas novedades y eventos de nuestra comunidad educativa.</p>
            
            <div class="noticias-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-newspaper text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    En esta sección podrás encontrar las últimas novedades, proyectos y eventos que se desarrollan en la E.E.S.O. Nº 225 General José de San Martín. Es un espacio para mantenerte al día con las actividades de la comunidad educativa y conocer los logros y avances de nuestra institución.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4">
        <?php foreach ($posts as $post): ?>
            <?php 
                $fecha = htmlspecialchars(date('d/m/Y', strtotime($post['fecha_publicacion'] ?? $post['created_at'] ?? date('Y-m-d'))));
                $resumen = html_entity_decode(strip_tags($post['contenido'] ?? ''), ENT_QUOTES, 'UTF-8');
                if (function_exists('truncarTexto')) {
                    $resumen = truncarTexto($resumen, 160);
                } else {
                    $resumen = mb_substr($resumen, 0, 160) . (mb_strlen($resumen) > 160 ? '…' : '');
                }
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <article class="card card-news h-100">
                    <?php if (!empty($post['imagen'])): ?>
                        <img src="<?= htmlspecialchars($post['imagen']) ?>" class="card-img-top news-list-thumb" alt="<?= htmlspecialchars($post['titulo']) ?>">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light news-list-thumb">
                            <i class="bi bi-newspaper text-muted" style="font-size: 2.5rem;"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-light text-dark border fw-semibold">Noticia</span>
                            <small class="text-muted"><?= $fecha ?></small>
                        </div>
                        <h3 class="card-title news-title mb-2">
                            <a class="stretched-link" href="noticia.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['titulo']) ?></a>
                        </h3>
                        <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($resumen) ?></p>
                        <div class="mt-3">
                            <a href="noticia.php?id=<?= $post['id'] ?>" class="btn btn-outline-violeta">Leer más</a>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/footer.php';

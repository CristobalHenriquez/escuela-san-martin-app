<?php
/**
 * Página de Noticias - EESO 225 "La San Martín"
 * 
 * Muestra un listado paginado de todas las noticias visibles.
 */

// Configuración y conexión
require_once '../includes/conexion.php';
require_once '../config/config.php';

// --- LÓGICA DE PAGINACIÓN ---

// 1. Definir cuántos posts por página (desde config.php)
$por_pagina = POSTS_POR_PAGINA;

// 2. Obtener la página actual desde la URL, o usar la página 1 por defecto
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

// 3. Calcular el inicio para la consulta SQL
$inicio = ($pagina_actual - 1) * $por_pagina;

// 4. Contar el total de noticias visibles para calcular el total de páginas
$stmt_total = $db->prepare("SELECT COUNT(id) as total FROM posts WHERE visible = 1 AND categoria = 'noticia'");
$stmt_total->execute();
$total_noticias = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_noticias / $por_pagina);

// 5. Obtener las noticias para la página actual
$stmt_noticias = $db->prepare("SELECT * FROM posts WHERE visible = 1 AND categoria = 'noticia' ORDER BY fecha_publicacion DESC LIMIT ?, ?");
$stmt_noticias->bind_param("ii", $inicio, $por_pagina);
$stmt_noticias->execute();
$noticias = $stmt_noticias->get_result()->fetch_all(MYSQLI_ASSOC);

// --- FIN LÓGICA DE PAGINACIÓN ---

// Definir la página actual para el menú de navegación
$page_type = 'noticias';

// Incluir el encabezado común del sitio público
include_once '../includes/header.php';
?>

<!-- ======= Breadcrumbs ======= -->
<div class="breadcrumbs">
    <div class="container" data-aos="fade-in">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Noticias</h2>
            <ol>
                <li><a href="index.php">Inicio</a></li>
                <li>Noticias</li>
            </ol>
        </div>
    </div>
</div><!-- End Breadcrumbs -->

<!-- ======= Blog Section ======= -->
<section id="blog" class="blog">
    <div class="container" data-aos="fade-up">

        <div class="row gy-4 posts-list">

            <?php if (!empty($noticias)): ?>
            <?php foreach ($noticias as $noticia): ?>
            <div class="col-xl-4 col-md-6">
                <article>
                    <div class="post-img">
                        <?php 
                                    $imagen_url = !empty($noticia['imagen']) ? '/' . htmlspecialchars($noticia['imagen']) : '../assets/images/logo/logo-escuela-placeholder.jpg';
                                ?>
                        <img src="<?= $imagen_url ?>"
                            alt="Imagen de la noticia: <?= htmlspecialchars($noticia['titulo']) ?>" class="img-fluid">
                    </div>
                    <p class="post-category"><?= htmlspecialchars(ucfirst($noticia['categoria'])) ?></p>
                    <h2 class="title">
                        <a href="noticia.php?id=<?= $noticia['id'] ?>"><?= htmlspecialchars($noticia['titulo']) ?></a>
                    </h2>
                    <div class="d-flex align-items-center">
                        <div class="post-meta">
                            <p class="post-date">
                                <time
                                    datetime="<?= $noticia['fecha_publicacion'] ?>"><?= formatearFecha($noticia['fecha_publicacion']) ?></time>
                            </p>
                        </div>
                    </div>
                </article>
            </div><!-- End post list item -->
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-12 text-center p-5">
                <i class="bi bi-newspaper fs-1 text-muted mb-3"></i>
                <h5>No hay noticias para mostrar</h5>
                <p class="text-muted">Aún no se han publicado noticias. Vuelve a visitar esta sección más tarde.</p>
            </div>
            <?php endif; ?>

        </div><!-- End blog posts list -->

        <?php if ($total_paginas > 1): ?>
        <div class="blog-pagination">
            <ul class="justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="<?= ($i == $pagina_actual) ? 'active' : '' ?>">
                    <a href="noticias.php?pagina=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </div><!-- End blog pagination -->
        <?php endif; ?>

    </div>
</section><!-- End Blog Section -->

<?php
// Incluir el pie de página común del sitio público
include_once '../includes/footer.php';
?>
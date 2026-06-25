<?php
/**
 * Página de Logros Estudiantiles - EESO 225 "La San Martín"
 * 
 * Muestra un listado paginado de todos los logros estudiantiles visibles.
 */

// Configuración y conexión
require_once '../includes/conexion.php';
require_once '../config/config.php';

// --- LÓGICA DE PAGINACIÓN ---

// 1. Definir cuántos logros por página (desde config.php)
$por_pagina = LOGROS_POR_PAGINA;

// 2. Obtener la página actual desde la URL, o usar la página 1 por defecto
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

// 3. Calcular el inicio para la consulta SQL
$inicio = ($pagina_actual - 1) * $por_pagina;

// 4. Contar el total de logros visibles para calcular el total de páginas
$stmt_total = $db->prepare("SELECT COUNT(id) as total FROM logros_estudiantiles WHERE visible = 1");
$stmt_total->execute();
$total_logros = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_logros / $por_pagina);

// 5. Obtener los logros para la página actual, ordenados por fecha
$stmt_logros = $db->prepare("SELECT * FROM logros_estudiantiles WHERE visible = 1 ORDER BY fecha_logro DESC, fecha_creacion DESC LIMIT ?, ?");
$stmt_logros->bind_param("ii", $inicio, $por_pagina);
$stmt_logros->execute();
$logros = $stmt_logros->get_result()->fetch_all(MYSQLI_ASSOC);

// --- FIN LÓGICA DE PAGINACIÓN ---

// Definir la página actual para el menú de navegación
$page_type = 'logros';

// Incluir el encabezado común del sitio público
include_once '../includes/header.php';
?>

<!-- ======= Breadcrumbs ======= -->
<div class="breadcrumbs">
    <div class="container" data-aos="fade-in">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Logros Estudiantiles</h2>
            <ol>
                <li><a href="index.php">Inicio</a></li>
                <li>Logros</li>
            </ol>
        </div>
    </div>
</div><!-- End Breadcrumbs -->

<!-- ======= Logros Section ======= -->
<section id="logros-list" class="testimonials sections-bg">
    <div class="container" data-aos="fade-up">

        <div class="section-header">
            <h2>Nuestros Talentos</h2>
            <p>Celebramos el esfuerzo, la dedicación y el éxito de nuestros estudiantes en distintas disciplinas.</p>
        </div>

        <div class="row gy-4">

            <?php if (!empty($logros)): ?>
            <?php foreach ($logros as $logro): ?>
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="testimonial-wrap">
                    <div class="testimonial-item">
                        <div class="d-flex align-items-center">
                            <?php 
                                        $foto_url = !empty($logro['foto']) ? '/' . htmlspecialchars($logro['foto']) : '../assets/images/general/avatar-placeholder.png';
                                    ?>
                            <img src="<?= $foto_url ?>" class="testimonial-img flex-shrink-0"
                                alt="Foto de <?= htmlspecialchars($logro['nombre_estudiante']) ?>">
                            <div>
                                <h3><?= htmlspecialchars($logro['nombre_estudiante']) ?></h3>
                                <h4><?= htmlspecialchars($logro['curso_division']) ?></h4>
                            </div>
                        </div>
                        <p>
                            <i class="bi bi-quote quote-icon-left"></i>
                            <strong><?= htmlspecialchars($logro['logro']) ?></strong> -
                            <?= htmlspecialchars($logro['descripcion']) ?>
                            <i class="bi bi-quote quote-icon-right"></i>
                        </p>
                        <small
                            class="text-muted"><?= !empty($logro['fecha_logro']) ? 'Fecha: ' . formatearFecha($logro['fecha_logro']) : '' ?></small>
                    </div>
                </div>
            </div><!-- End testimonial item -->
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-12 text-center p-5">
                <i class="bi bi-trophy fs-1 text-muted mb-3"></i>
                <h5>Aún no hay logros para mostrar</h5>
                <p class="text-muted">Los logros y reconocimientos de nuestros estudiantes aparecerán aquí.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="blog-pagination mt-5">
            <ul class="justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="<?= ($i == $pagina_actual) ? 'active' : '' ?>"><a
                        href="logros.php?pagina=<?= $i ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </div><!-- End blog pagination -->
        <?php endif; ?>

    </div>
</section><!-- End Logros Section -->

<?php
// Incluir el pie de página común del sitio público
include_once '../includes/footer.php';
?>
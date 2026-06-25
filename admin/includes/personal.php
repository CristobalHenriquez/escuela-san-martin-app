<?php
/**
 * Página de Personal Docente - EESO 225 "La San Martín"
 * 
 * Muestra un listado paginado de todo el personal docente visible.
 */

// Configuración y conexión
require_once '../includes/conexion.php';
require_once '../config/config.php';

// --- LÓGICA DE PAGINACIÓN ---

// 1. Definir cuántos miembros del personal por página (desde config.php)
$por_pagina = PERSONAL_POR_PAGINA;

// 2. Obtener la página actual desde la URL, o usar la página 1 por defecto
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

// 3. Calcular el inicio para la consulta SQL
$inicio = ($pagina_actual - 1) * $por_pagina;

// 4. Contar el total de personal visible para calcular el total de páginas
$stmt_total = $db->prepare("SELECT COUNT(id) as total FROM personal_docente WHERE visible = 1");
$stmt_total->execute();
$total_personal = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_personal / $por_pagina);

// 5. Obtener el personal para la página actual, ordenado por apellido y nombre
$stmt_personal = $db->prepare("SELECT * FROM personal_docente WHERE visible = 1 ORDER BY apellido, nombre LIMIT ?, ?");
$stmt_personal->bind_param("ii", $inicio, $por_pagina);
$stmt_personal->execute();
$personal = $stmt_personal->get_result()->fetch_all(MYSQLI_ASSOC);

// --- FIN LÓGICA DE PAGINACIÓN ---

// Definir la página actual para el menú de navegación
$page_type = 'personal';

// Incluir el encabezado común del sitio público
include_once '../includes/header.php';
?>

<!-- ======= Breadcrumbs ======= -->
<div class="breadcrumbs">
    <div class="container" data-aos="fade-in">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Personal Docente</h2>
            <ol>
                <li><a href="index.php">Inicio</a></li>
                <li>Personal Docente</li>
            </ol>
        </div>
    </div>
</div><!-- End Breadcrumbs -->

<!-- ======= Our Team Section ======= -->
<section id="team" class="team">
    <div class="container" data-aos="fade-up">

        <div class="section-header">
            <h2>Nuestro Equipo</h2>
            <p>Conocé a los profesionales dedicados a la formación de nuestros estudiantes.</p>
        </div>

        <div class="row gy-4">

            <?php if (!empty($personal)): ?>
            <?php foreach ($personal as $miembro): ?>
            <div class="col-xl-3 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="100">
                <div class="member">
                    <?php 
                                $foto_url = !empty($miembro['foto']) ? '/' . htmlspecialchars($miembro['foto']) : '../assets/images/general/avatar-placeholder.png';
                            ?>
                    <img src="<?= $foto_url ?>" class="img-fluid"
                        alt="Foto de <?= htmlspecialchars($miembro['nombre'] . ' ' . $miembro['apellido']) ?>">
                    <h4><?= htmlspecialchars($miembro['nombre'] . ' ' . $miembro['apellido']) ?></h4>
                    <span><?= htmlspecialchars($miembro['materia']) ?></span>
                    <div class="social">
                        <?php if (!empty($miembro['linkedin'])): ?>
                        <a href="<?= htmlspecialchars($miembro['linkedin']) ?>" target="_blank"><i
                                class="bi bi-linkedin"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($miembro['email_institucional'])): ?>
                        <a href="mailto:<?= htmlspecialchars($miembro['email_institucional']) ?>"><i
                                class="bi bi-envelope-fill"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div><!-- End Team Member -->
            <?php endforeach; ?>
            <?php else: ?>
            <div class="col-12 text-center p-5">
                <i class="bi bi-people fs-1 text-muted mb-3"></i>
                <h5>No hay personal docente para mostrar</h5>
                <p class="text-muted">La información sobre nuestro equipo docente aparecerá aquí pronto.</p>
            </div>
            <?php endif; ?>

        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="blog-pagination mt-5">
            <ul class="justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="<?= ($i == $pagina_actual) ? 'active' : '' ?>"><a
                        href="personal.php?pagina=<?= $i ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </div><!-- End blog pagination -->
        <?php endif; ?>

    </div>
</section><!-- End Our Team Section -->

<?php
// Incluir el pie de página común del sitio público
include_once '../includes/footer.php';
?>
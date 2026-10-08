<?php
/**
 * Sección de Personal Docente - Frontend
 * EESO 225 San Martín
 */

// Obtener personal destacado desde la tabla correcta
$personal_destacado = [];
if (function_exists('get_mysqli')) {
    $mysqli = get_mysqli();
    if ($mysqli) {
        $stmt = $mysqli->prepare("SELECT * FROM personal_docente WHERE visible = 1 ORDER BY RAND() LIMIT 4");
        if ($stmt) {
            $stmt->execute();
            $personal_destacado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Función helper para truncar texto
if (!function_exists('truncarTexto')) {
    function truncarTexto($texto, $limite = 120) {
        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }
        return mb_substr($texto, 0, $limite) . '...';
    }
}
?>

<!-- ======= Our Team Section ======= -->
<section id="team" class="team">
    <div class="container" data-aos="fade-up">

        <div class="section-header text-center">
            <h2>Nuestro Equipo Docente</h2>
            <p class="lead mb-4">Conocé a algunos de los profesionales que guían a nuestros estudiantes.</p>
            
            <div class="personal-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-people-fill text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    Nuestro equipo docente está conformado por profesionales comprometidos con la enseñanza y el acompañamiento pedagógico. A través de su experiencia y dedicación, garantizan una educación de calidad, promoviendo el aprendizaje significativo y el crecimiento personal de cada estudiante.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($personal_destacado)): ?>
        <div class="row gy-4 align-items-center">
            <!-- Columna del contenido -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="personal-content">
                    <h3 class="mb-4" style="color: var(--color-violeta); font-family: 'Montserrat', sans-serif;">
                        Profesionales Comprometidos
                    </h3>
                    
                    <div class="team-stats mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="stat-item text-center p-3 rounded-3" style="background: rgba(106, 27, 154, 0.08);">
                                    <i class="bi bi-mortarboard-fill mb-2" style="font-size: 1.8rem; color: var(--color-violeta);"></i>
                                    <h6 class="mb-1">Experiencia</h6>
                                    <small class="text-muted">Profesionales capacitados</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item text-center p-3 rounded-3" style="background: rgba(106, 27, 154, 0.08);">
                                    <i class="bi bi-heart-fill mb-2" style="font-size: 1.8rem; color: #dc3545;"></i>
                                    <h6 class="mb-1">Compromiso</h6>
                                    <small class="text-muted">Dedicación pedagógica</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item text-center p-3 rounded-3" style="background: rgba(106, 27, 154, 0.08);">
                                    <i class="bi bi-star-fill mb-2" style="font-size: 1.8rem; color: #ffc107;"></i>
                                    <h6 class="mb-1">Calidad</h6>
                                    <small class="text-muted">Educación de excelencia</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-muted mb-4" style="text-align: justify; line-height: 1.7; font-size: 1.05rem;">
                        Nuestro equipo docente está conformado por profesionales especializados en diferentes áreas del conocimiento, 
                        comprometidos con la formación integral de nuestros estudiantes. Con años de experiencia y formación continua, 
                        cada docente aporta su expertise para crear un ambiente de aprendizaje enriquecedor.
                    </p>
                    
                    <div class="personal-cta">
                        <a href="personal.php" class="btn btn-outline-violeta">
                            <i class="bi bi-people me-2"></i>
                            Conocer todo el equipo docente
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Columna de la imagen -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="personal-image-container">
                    <img src="assets/images/profesores.jpg" 
                         class="img-fluid rounded-3 shadow" 
                         alt="Equipo Docente EESO 225 San Martín"
                         style="border-radius: 15px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="row gy-4 align-items-center">
            <!-- Columna del contenido -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="personal-content">
                    <h3 class="mb-4" style="color: var(--color-violeta); font-family: 'Montserrat', sans-serif;">
                        Próximamente: Conocé a Nuestro Equipo
                    </h3>
                    
                    <div class="team-stats mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="stat-item text-center p-3 rounded-3" style="background: rgba(106, 27, 154, 0.08);">
                                    <i class="bi bi-mortarboard-fill mb-2" style="font-size: 1.8rem; color: var(--color-violeta);"></i>
                                    <h6 class="mb-1">Experiencia</h6>
                                    <small class="text-muted">Profesionales capacitados</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item text-center p-3 rounded-3" style="background: rgba(106, 27, 154, 0.08);">
                                    <i class="bi bi-heart-fill mb-2" style="font-size: 1.8rem; color: #dc3545;"></i>
                                    <h6 class="mb-1">Compromiso</h6>
                                    <small class="text-muted">Dedicación pedagógica</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-item text-center p-3 rounded-3" style="background: rgba(106, 27, 154, 0.08);">
                                    <i class="bi bi-star-fill mb-2" style="font-size: 1.8rem; color: #ffc107;"></i>
                                    <h6 class="mb-1">Calidad</h6>
                                    <small class="text-muted">Educación de excelencia</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-muted mb-4" style="text-align: justify; line-height: 1.7; font-size: 1.05rem;">
                        Estamos preparando la presentación de nuestro dedicado equipo docente. Pronto podrás conocer a los 
                        profesionales que trabajan día a día para brindar una educación de calidad y acompañar el crecimiento 
                        de nuestros estudiantes.
                    </p>
                    
                    <div class="personal-cta">
                        <a href="personal.php" class="btn btn-outline-violeta">
                            <i class="bi bi-people me-2"></i>
                            Conocer todo el equipo docente
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Columna de la imagen -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="personal-image-container">
                    <img src="assets/images/profesores.jpg" 
                         class="img-fluid rounded-3 shadow" 
                         alt="Equipo Docente EESO 225 San Martín"
                         style="border-radius: 15px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;">
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section><!-- End Our Team Section -->
<?php
/**
 * Sección Hero (Banner Principal) - Frontend
 * EESO 225 "La San Martín"
 */
?>

<!-- ======= Hero Section ======= -->
<section id="hero" class="hero">
    <div class="container position-relative">
        <div class="row gy-5" data-aos="fade-in">
            <div class="col-lg-7 order-2 order-lg-1 d-flex flex-column justify-content-center text-center text-lg-start">
                <h2>Bienvenidos a la</h2>
                <h1 style="font-family: 'Montserrat', sans-serif;"><?= htmlspecialchars(ESCUELA_NOMBRE) ?></h1>
                
                <div class="hero-description">
                    <p class="lead mb-4">Formando ciudadanos críticos y comprometidos con la sociedad.</p>
                    
                    <div class="institutional-info">
                        <div class="info-item mb-3">
                            <i class="bi bi-mortarboard-fill text-primary me-2"></i>
                            <span>La E.E.S. Orientada N.º 225 "General José de San Martín" es una institución comprometida con la formación integral de sus estudiantes. Buscamos brindar una educación de calidad que promueva valores, habilidades y pensamiento crítico.</span>
                        </div>
                        
                        <div class="info-item mb-4">
                            <i class="bi bi-people-fill text-success me-2"></i>
                            <span>Trabajamos diariamente para acompañar el crecimiento académico y personal de nuestros alumnos, ofreciendo propuestas educativas innovadoras y un entorno institucional cercano, respetuoso y participativo.</span>
                        </div>
                    </div>
                    
                    <p class="call-to-action text-muted mb-4">
                        <i class="bi bi-arrow-down-circle me-2"></i>
                        Descubrí nuestra propuesta educativa, noticias y logros.
                    </p>
                </div>
                
                <div class="d-flex gap-3 justify-content-center justify-content-lg-start flex-wrap">
                    <a href="noticias.php" class="btn-get-started">
                        <i class="bi bi-newspaper me-2"></i>Últimas Noticias
                    </a>
                    <a href="#contact" class="btn-contacto d-flex align-items-center gap-2">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Contactános</span>
                    </a>
                </div>
            </div>
            <div class="col-lg-5 order-1 order-lg-2 d-flex justify-content-center align-items-center">
                <img src="assets/images/hero_escuela.jpeg" class="img-fluid" alt="EESO 225 La San Martín"
                    data-aos="zoom-out" data-aos-delay="100"
                    style="max-height: 400px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            </div>
        </div>
    </div>
</section><!-- End Hero Section -->
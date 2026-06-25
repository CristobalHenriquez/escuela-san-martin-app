<?php
/**
 * Sección de Contacto - Frontend
 * EESO 225 "La San Martín"
 */
?>

<!-- ======= Contact Section ======= -->
<section id="contact" class="contact">
    <div class="container" data-aos="fade-up">

        <div class="section-header text-center">
            <h2>Contactanos</h2>
            <p class="lead">¿Tenés alguna consulta? Estamos aquí para ayudarte.</p>
            
            <div class="contacto-description">
                <div class="description-content mx-auto" style="max-width: 800px;">
                    <div class="info-box p-4 rounded-3 mb-4" style="background: rgba(106, 27, 154, 0.08); border-left: 4px solid var(--color-violeta);">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-envelope-fill text-primary me-3 mt-1" style="font-size: 1.5rem; color: var(--color-violeta) !important;"></i>
                            <div>
                                <p class="mb-0" style="text-align: justify; line-height: 1.6; color: #444;">
                                    Mantené contacto con la E.E.S.O. Nº 225 "La San Martín". Estamos disponibles para responder tus consultas sobre inscripciones, programas educativos, actividades institucionales y cualquier información que necesites. Tu comunicación es importante para nosotros.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">

            <div class="col-lg-4 col-md-5">
                <div class="info-container d-flex flex-column align-items-start text-start">
                    <div class="info-item d-flex align-items-start gap-3">
                        <i class="bi bi-geo-alt flex-shrink-0"></i>
                        <div>
                            <h4>Ubicación:</h4>
                            <p>Sarmiento 949<br>
                               Pérez, Santa Fe, Argentina</p>
                        </div>
                    </div><!-- End Info Item -->

                    <div class="info-item d-flex align-items-start gap-3">
                        <i class="bi bi-envelope flex-shrink-0"></i>
                        <div>
                            <h4>Email:</h4>
                            <p>contacto@eeso225-lasanmartin.edu.ar</p>
                        </div>
                    </div><!-- End Info Item -->

                    <div class="info-item d-flex align-items-start gap-3">
                        <i class="bi bi-phone flex-shrink-0"></i>
                        <div>
                            <h4>Teléfono:</h4>
                            <p>3414951255 - 3413547139</p>
                        </div>
                    </div><!-- End Info Item -->

                    <div class="info-item d-flex align-items-start gap-3">
                        <i class="bi bi-clock flex-shrink-0"></i>
                        <div>
                            <h4>Horarios:</h4>
                            <p>Lunes a Viernes: 7:30 - 18:00</p>
                        </div>
                    </div><!-- End Info Item -->
                </div>

            </div>

            <div class="col-lg-7 col-md-7">
                <?php 
                // Mostrar mensajes de estado si vienen por GET
                if (isset($_GET['status'])) {
                    if ($_GET['status'] === 'success') {
                        echo '<div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i>Tu mensaje ha sido enviado correctamente. ¡Gracias!</div>';
                    } elseif ($_GET['status'] === 'error') {
                        $error_msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Error al enviar el mensaje';
                        echo '<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i>' . $error_msg . '</div>';
                    }
                }
                ?>
                
                <form action="includes/enviar-contacto.php" method="post" role="form" class="contact-form" id="contactForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre completo</label>
                            <input type="text" name="name" class="form-control form-control-lg" id="name" 
                                   placeholder="Tu Nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control form-control-lg" name="email" id="email" 
                                   placeholder="tu@email.com" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="subject" class="form-label">Asunto</label>
                        <input type="text" class="form-control form-control-lg" name="subject" id="subject" 
                               placeholder="¿En qué podemos ayudarte?" required>
                    </div>
                    <div class="mt-3">
                        <label for="message" class="form-label">Mensaje</label>
                        <textarea class="form-control form-control-lg" name="message" id="message" rows="6" 
                                  placeholder="Escribí tu consulta aquí..." required></textarea>
                    </div>
                    
                    <!-- Mensajes de estado AJAX -->
                    <div class="mt-3">
                        <div class="alert alert-info d-none contact-loading" role="alert">
                            <i class="bi bi-hourglass-split me-2"></i>Enviando tu mensaje...
                        </div>
                        <div class="alert alert-danger d-none contact-error" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><span class="error-text"></span>
                        </div>
                        <div class="alert alert-success d-none contact-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i>Tu mensaje ha sido enviado. ¡Gracias!
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-violeta btn-lg px-5" id="submitBtn">
                            <i class="bi bi-send me-2"></i>Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div><!-- End Contact Form -->

        </div>

        <!-- Mapa de ubicación -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="map-container">
                    <h5 class="text-center mb-4" style="color: var(--color-violeta); font-family: 'Montserrat', sans-serif;">
                        <i class="bi bi-geo-alt me-2"></i>Nuestra Ubicación
                    </h5>
                    <div class="map-wrapper">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3346.189800660124!2d-60.77792252429796!3d-32.99877117357168!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95b7b28755403007%3A0x2afa4f5aee1d6f59!2sEscuela%20San%20Martin!5e0!3m2!1ses!2sar!4v1764183687922!5m2!1ses!2sar" 
                            class="google-map"
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Ubicación de EESO 225 La San Martín">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Links -->
        <div class="row mt-5">
            <div class="col-12">
                <!-- Logo centrado arriba -->
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <div class="logo-section">
                            <img src="assets/images/escuela_footer.png" 
                                 alt="<?= htmlspecialchars(ESCUELA_NOMBRE_CORTO) ?>" 
                                 class="img-fluid"
                                 style="max-width: 280px; height: auto; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.12); transition: all 0.3s ease; filter: brightness(1.05);">
                        </div>
                    </div>
                </div>
                
                <!-- Redes Sociales centradas -->
                <div class="row">
                    <div class="col-12 text-center">
                        <div class="social-links">
                            <h5 class="mb-4" style="color: var(--color-violeta); font-weight: 600;">Seguinos en nuestras redes</h5>
                            <div class="d-flex justify-content-center gap-4 mb-4">
                                <a href="<?= FACEBOOK_URL ?>" target="_blank" class="facebook" title="Facebook" rel="noopener" 
                                   style="font-size: 2.5rem; transition: all 0.3s ease; color: #1877f2;">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="<?= INSTAGRAM_URL ?>" target="_blank" class="instagram" title="Instagram" rel="noopener" 
                                   style="font-size: 2.5rem; transition: all 0.3s ease; color: #E4405F;">
                                    <i class="bi bi-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cierre del Home -->
                <div class="row mt-4">
                    <div class="col-12">
                        <hr style="border-color: rgba(106, 27, 154, 0.3); margin: 2.5rem 0 2rem 0; border-width: 2px;">
                        <div class="row align-items-center text-center">
                            <div class="col-md-8 text-center text-md-start">
                                <p class="mb-2 mb-md-0" style="color: #666; font-size: 0.95rem; font-weight: 500;">
                                    &copy; <?= date('Y') ?> <?= htmlspecialchars(ESCUELA_NOMBRE_CORTO) ?>. 
                                    Todos los derechos reservados.
                                </p>
                            </div>
                            <div class="col-md-4 text-center text-md-end">
                                <p class="mb-0" style="color: var(--color-violeta); font-size: 0.95rem; font-weight: 600;">
                                    <i class="bi bi-heart-fill text-danger me-2" style="animation: pulse 2s infinite;"></i>
                                    Educando con pasión
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estilos adicionales -->
        <style>
        .social-links a:hover {
            transform: translateY(-3px) scale(1.1);
            filter: brightness(1.2);
        }
        
        .logo-section img:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 35px rgba(0,0,0,0.18);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        </style>

    </div>
</section><!-- End Contact Section -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const loadingAlert = document.querySelector('.contact-loading');
    const errorAlert = document.querySelector('.contact-error');
    const successAlert = document.querySelector('.contact-success');
    const errorText = document.querySelector('.error-text');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Ocultar mensajes anteriores
            [loadingAlert, errorAlert, successAlert].forEach(alert => {
                alert.classList.add('d-none');
            });
            
            // Mostrar loading
            loadingAlert.classList.remove('d-none');
            submitBtn.disabled = true;
            
            // Preparar datos
            const formData = new FormData(contactForm);
            
            // Envío AJAX
            fetch('includes/enviar-contacto.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loadingAlert.classList.add('d-none');
                submitBtn.disabled = false;
                
                if (data.success) {
                    successAlert.classList.remove('d-none');
                    contactForm.reset();
                    
                    // Scroll al mensaje de éxito
                    successAlert.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    
                    // Ocultar mensaje de éxito después de 5 segundos
                    setTimeout(() => {
                        successAlert.classList.add('d-none');
                    }, 5000);
                } else {
                    errorText.textContent = data.error || 'Error al enviar el mensaje';
                    errorAlert.classList.remove('d-none');
                    
                    // Scroll al mensaje de error
                    errorAlert.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingAlert.classList.add('d-none');
                submitBtn.disabled = false;
                
                errorText.textContent = 'Error de conexión. Intentá de nuevo.';
                errorAlert.classList.remove('d-none');
                
                errorAlert.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            });
        });
    }
});
</script>
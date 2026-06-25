    </div>
</main>

<!-- Footer -->
<footer class="bg-light pt-5 pb-4 mt-5">
    <div class="container">
        <!-- Social Media Links -->
        <div class="row">
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
                                <?php if (defined('FACEBOOK_URL') && FACEBOOK_URL): ?>
                                <a href="<?= FACEBOOK_URL ?>" target="_blank" class="facebook" title="Facebook" rel="noopener" 
                                   style="font-size: 2.5rem; transition: all 0.3s ease; color: #1877f2;">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (defined('INSTAGRAM_URL') && INSTAGRAM_URL): ?>
                                <a href="<?= INSTAGRAM_URL ?>" target="_blank" class="instagram" title="Instagram" rel="noopener" 
                                   style="font-size: 2.5rem; transition: all 0.3s ease; color: #E4405F;">
                                    <i class="bi bi-instagram"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Cierre -->
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
    </div>
</footer>

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

<!-- Vendor JS (local) -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Optional: SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>

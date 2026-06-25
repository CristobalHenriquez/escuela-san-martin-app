        </div>
    </main><!-- End main content -->

    <!-- ======= Footer Admin ======= -->
    <footer class="admin-footer mt-5">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-5 col-md-12">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-mortarboard-fill me-2 fs-4" style="color: var(--color-blanco);"></i>
                        <span class="fw-bold"><?= htmlspecialchars(ESCUELA_NOMBRE_CORTO) ?></span>
                    </div>
                    <p class="mb-3"><?= htmlspecialchars(ESCUELA_NOMBRE) ?></p>
                    <p class="small mb-4">Panel de Administración - Sistema de Gestión Escolar</p>
                    
                    <div class="social-links d-flex">
                        <?php if (defined('FACEBOOK_URL') && FACEBOOK_URL): ?>
                        <a href="<?= FACEBOOK_URL ?>" target="_blank" class="me-3" title="Facebook" rel="noopener">
                            <i class="bi bi-facebook fs-5"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (defined('INSTAGRAM_URL') && INSTAGRAM_URL): ?>
                        <a href="<?= INSTAGRAM_URL ?>" target="_blank" class="me-3" title="Instagram" rel="noopener">
                            <i class="bi bi-instagram fs-5"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Logo de la Escuela -->
                    <div class="logo-footer mt-4">
                        <img src="../assets/images/logo/logo-escuela.jpg" 
                             alt="<?= htmlspecialchars(ESCUELA_NOMBRE_CORTO) ?>" 
                             class="img-fluid"
                             style="max-width: 120px; height: auto; border-radius: 8px; opacity: 0.9;">
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <h5 class="mb-3" style="color: var(--color-blanco);">Enlaces Útiles</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="dashboard.php"><i class="bi bi-house me-2"></i>Panel Principal</a></li>
                        <li class="mb-2"><a href="noticias.php"><i class="bi bi-newspaper me-2"></i>Noticias</a></li>
                        <li class="mb-2"><a href="personal.php"><i class="bi bi-people me-2"></i>Personal Docente</a></li>
                        <li class="mb-2"><a href="logros.php"><i class="bi bi-trophy me-2"></i>Logros</a></li>
                        <li class="mb-2"><a href="../index.php" target="_blank"><i class="bi bi-globe me-2"></i>Ver Sitio Web</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6">
                    <h5 class="mb-3" style="color: var(--color-blanco);">Información de Contacto</h5>
                    <div class="contact-info">
                        <p class="mb-2">
                            <i class="bi bi-geo-alt me-2"></i>
                            <?= htmlspecialchars(ESCUELA_DIRECCION) ?>
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-pin-map me-2"></i>
                            <?= htmlspecialchars(ESCUELA_LOCALIDAD) ?>, <?= htmlspecialchars(ESCUELA_PROVINCIA) ?>
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-telephone me-2"></i>
                            <strong>Teléfono:</strong> <?= htmlspecialchars(ESCUELA_TELEFONO) ?>
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-envelope me-2"></i>
                            <strong>Email:</strong> 
                            <a href="mailto:<?= htmlspecialchars(ESCUELA_EMAIL) ?>"><?= htmlspecialchars(ESCUELA_EMAIL) ?></a>
                        </p>
                    </div>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 small">
                        &copy; <?= date('Y') ?> <?= htmlspecialchars(ESCUELA_NOMBRE_CORTO) ?>. 
                        Todos los derechos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 small">
                        <i class="bi bi-code-slash me-1"></i>
                        Sistema desarrollado con 
                        <i class="bi bi-heart-fill text-danger mx-1"></i>
                        para la educación
                    </p>
                </div>
            </div>
        </div>
    </footer><!-- End Footer Admin -->

    <!-- Scroll to top button -->
    <a href="#" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files (local) -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <!-- Template Main JS File (local) -->
    <script src="../assets/js/main.js"></script>

    </body>

    </html>
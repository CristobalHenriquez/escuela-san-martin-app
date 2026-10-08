<?php
// Conexion a la bd
require_once '../includes/conexion.php';
// Verificar autenticación
require_once '../includes/auth.php';
verificarAutenticacion();

// Configuración por defecto
$admin_page_title = $admin_page_title ?? 'Panel de Administración | EESO 225 San Martín';
$admin_page_description = $admin_page_description ?? 'Panel de administración para gestionar el contenido de EESO 225 San Martín';

// Determinar la página actual para resaltar el menú correspondiente
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- SEO Básico -->
    <title><?= $admin_page_title ?></title>
    <meta name="description" content="<?= $admin_page_description ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="EESO 225 San Martín">

    <!-- Favicons -->
    <link href="../assets/images/logo/favicon.svg" rel="icon" type="image/svg+xml">
    <link href="../assets/images/logo/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files (local) -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Estilos personalizados para el panel de administración -->
    <link href="../assets/css/admin-styles.css" rel="stylesheet">

    <!-- TinyMCE (cargado individualmente en cada página que lo necesita) -->
    <!-- <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script> -->

</head>
<!-- Session Timeout Monitoring -->
<script>
    // Session timeout settings
    const sessionTimeoutMinutes = 30; // Regular session timeout in minutes
    const warningBeforeTimeoutSeconds = 60; // Show warning this many seconds before timeout
    const checkInterval = 5000; // Check interval in milliseconds (5 seconds)

    // Variables for tracking
    let lastActivity = Date.now();
    let sessionTimeoutTimer;
    let warningTimer;
    let isWarningDisplayed = false;

    // Convert minutes to milliseconds
    const sessionTimeout = sessionTimeoutMinutes * 60 * 1000;

    // Function to reset the timer
    function resetSessionTimer() {
        lastActivity = Date.now();

        // Clear existing timers
        if (sessionTimeoutTimer) clearTimeout(sessionTimeoutTimer);
        if (warningTimer) clearTimeout(warningTimer);

        // If a warning is displayed, close it
        if (isWarningDisplayed) {
            Swal.close();
            isWarningDisplayed = false;
        }

        // Set new timers
        warningTimer = setTimeout(showTimeoutWarning, sessionTimeout - warningBeforeTimeoutSeconds * 1000);
        sessionTimeoutTimer = setTimeout(handleSessionTimeout, sessionTimeout);
    }

    // Function to show timeout warning
    function showTimeoutWarning() {
        isWarningDisplayed = true;

        Swal.fire({
            title: '¡Advertencia de inactividad!',
            html: `Tu sesión está a punto de expirar por inactividad.<br>Se cerrará en <strong><span id="countdown">${warningBeforeTimeoutSeconds}</span></strong> segundos.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6A1B9A',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Mantener sesión',
            cancelButtonText: 'Cerrar sesión',
            allowOutsideClick: false,
            timer: warningBeforeTimeoutSeconds * 1000,
            timerProgressBar: true,
            didOpen: () => {
                const content = Swal.getHtmlContainer();
                const countdownElement = content.querySelector('#countdown');

                if (countdownElement) {
                    const countdownInterval = setInterval(() => {
                        const secondsLeft = Math.ceil(Swal.getTimerLeft() / 1000);
                        countdownElement.textContent = secondsLeft;

                        if (secondsLeft <= 0) {
                            clearInterval(countdownInterval);
                        }
                    }, 1000);
                }

                // Reset timer on any activity during warning
                Swal.showLoading(Swal.getCancelButton());
            }
        }).then((result) => {
            isWarningDisplayed = false;

            if (result.isConfirmed) {
                // User clicked "Mantener sesión"
                resetSessionTimer();
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // User clicked "Cerrar sesión"
                window.location.href = 'logout.php';
            } else if (result.dismiss === Swal.DismissReason.timer) {
                // Timer expired
                handleSessionTimeout();
            }
        });
    }

    // Function to handle session timeout
    function handleSessionTimeout() {
        // Close any open SweetAlert
        Swal.close();

        // Show session expired alert
        Swal.fire({
            title: '¡Sesión expirada!',
            text: 'Tu sesión ha expirado por inactividad.',
            icon: 'error',
            confirmButtonColor: '#6A1B9A',
            confirmButtonText: 'Iniciar sesión nuevamente',
            allowOutsideClick: false
        }).then(() => {
            // Redirect to login page
            window.location.href = 'logout.php?expired=1';
        });
    }

    // Function to check if session is still valid
    function checkSession() {
        const inactiveTime = Date.now() - lastActivity;

        if (inactiveTime >= sessionTimeout) {
            handleSessionTimeout();
        }
    }

    // Initialize session monitoring
    document.addEventListener('DOMContentLoaded', function() {
        // Reset timer on page load
        resetSessionTimer();

        // Set up activity listeners
        const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];

        activityEvents.forEach(event => {
            document.addEventListener(event, function() {
                resetSessionTimer();
            }, true);
        });

        // Periodically check session status
        setInterval(checkSession, checkInterval);

        // Check for test parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('test_timeout')) {
            setTimeout(() => {
                handleSessionTimeout();
            }, 1000);
        } else if (urlParams.has('test_warning')) {
            setTimeout(() => {
                showTimeoutWarning();
            }, 1000);
        }
    });

    // Function to simulate session timeout (for testing)
    function simulateSessionTimeout() {
        handleSessionTimeout();
    }

    // Function to simulate timeout warning (for testing)
    function simulateTimeoutWarning() {
        showTimeoutWarning();
    }
</script>

<body>
    <!-- Navbar de administración -->
    <nav class="navbar navbar-expand-lg admin-navbar sticky-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/images/logo/logo-escuela-negativo.svg" alt="EESO 225 San Martín" style="height: 52px;">
            </a>

            <!-- Menú de navegación -->
            <div class="collapse navbar-collapse mx-5" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ml-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'dashboard.php' || $current_page === 'admin.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'noticias.php' || $current_page === 'cargar-noticia.php' || $current_page === 'editar-noticia.php' ? 'active' : '' ?>" href="noticias.php">
                            <i class="bi bi-newspaper me-1"></i> Noticias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'personal.php' || $current_page === 'cargar-personal.php' || $current_page === 'editar-personal.php' ? 'active' : '' ?>" href="personal.php">
                            <i class="bi bi-people me-1"></i> Personal Docente
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'logros.php' || $current_page === 'cargar-logro.php' || $current_page === 'editar-logro.php' ? 'active' : '' ?>" href="logros.php">
                            <i class="bi bi-trophy me-1"></i> Logros Estudiantiles
                        </a>
                    </li>
                </ul>

                <!-- Usuario y menú desplegable -->
                <div class="dropdown">
                    <div class="user-menu d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?= strtoupper(substr(obtenerNombreAdmin(), 0, 1)) ?>
                        </div>
                        <span class="mx-2"><?= obtenerNombreAdmin() ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item logout-link" href="logout.php" style="color: var(--dark); text-decoration: none; display: flex; align-items: center;"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="admin-content">
        <div class="container">
            <!-- Aquí comienza el contenido específico de cada página -->
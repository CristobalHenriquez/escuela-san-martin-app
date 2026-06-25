<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
// Iniciar sesión si es necesario para mensajes u otras funciones
if (session_status() === PHP_SESSION_NONE) session_start();

// Valores meta por defecto
$page_title = $page_title ?? ESCUELA_NOMBRE_CORTO;
$page_description = $page_description ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="assets/images/logo/logo-escuela.jpg" type="image/jpeg">

    <!-- Vendor CSS (local) -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/admin-styles.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

</head>
<body>

<!-- Navbar público simple -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/images/logo/logo-escuela.jpg" alt="Logo" style="height:48px; margin-right:10px;">
            <span class="fw-bold"><?= htmlspecialchars(ESCUELA_NOMBRE_CORTO) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="noticias.php">Noticias</a></li>
                <li class="nav-item"><a class="nav-link" href="personal.php">Personal</a></li>
                <li class="nav-item"><a class="nav-link" href="logros.php">Logros</a></li>
                <li class="nav-item"><a class="nav-link" href="contacto.php">Contacto</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">

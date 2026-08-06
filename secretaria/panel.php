<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
secretaria_require_auth();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentación | Secretaría</title>
    <link rel="icon" href="../assets/images/logo/logo-escuela.jpg" type="image/jpeg">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/secretaria.css" rel="stylesheet">
</head>
<body class="secretaria-page">
    <main class="workspace-shell">
        <header class="workspace-header">
            <div class="brand-line">
                <img src="../assets/images/logo/logo-escuela.jpg" alt="Logo EESO 225">
                <div>
                    <span class="eyebrow">E.E.S.O. N° 225 General José de San Martín</span>
                    <h1>Documentación de Secretaría</h1>
                </div>
            </div>
            <a class="btn btn-outline-secondary" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
        </header>

        <section class="workspace-intro">
            <span class="section-kicker">Emisión de documentos</span>
            <h2>Seleccione el informe que desea completar</h2>
            <p>Complete los datos en el formulario y descargue un PDF institucional listo para imprimir.</p>
        </section>

        <section class="document-grid" aria-label="Formularios disponibles">
            <a class="document-option" href="acta.php">
                <span class="document-icon"><i class="bi bi-file-earmark-text"></i></span>
                <span class="document-copy"><strong>Crear acta</strong><small>Acta de reunión, acuerdos y observaciones.</small></span>
                <i class="bi bi-arrow-right document-arrow"></i>
            </a>
            <a class="document-option" href="reincorporacion.php">
                <span class="document-icon"><i class="bi bi-person-check"></i></span>
                <span class="document-copy"><strong>Solicitud de reincorporación</strong><small>Solicitud institucional de reincorporación del estudiante.</small></span>
                <i class="bi bi-arrow-right document-arrow"></i>
            </a>
        </section>

        <footer class="workspace-footer">Área Secretaría · EESO 225 “La San Martín” · Pérez, Santa Fe</footer>
    </main>
</body>
</html>

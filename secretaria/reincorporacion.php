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
    <title>Solicitud de reincorporación | Secretaría</title>
    <link rel="icon" href="../assets/images/logo/favicon.svg" type="image/svg+xml">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/secretaria.css" rel="stylesheet">
</head>
<body class="secretaria-page">
<main class="form-shell">
    <header class="form-header">
        <div class="brand-line">
            <img src="../assets/images/logo/logo-escuela-icono.svg" alt="Logo EESO 225">
            <div><span class="eyebrow">Secretaría · Documento institucional</span><h1>Solicitud de reincorporación</h1></div>
        </div>
        <a class="btn btn-outline-secondary" href="panel.php"><i class="bi bi-arrow-left me-2"></i>Volver</a>
    </header>
    <form class="form-card" method="post" action="generar-pdf.php" target="_blank">
        <input type="hidden" name="documento" value="reincorporacion">
        <input type="hidden" name="csrf_token" value="<?= secretaria_h(secretaria_csrf_token()) ?>">
        <h2 class="form-section-title"><i class="bi bi-calendar3"></i>Datos de la solicitud</h2>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="fecha">Fecha <span class="text-danger">*</span></label><input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="anio_div">Año / curso <span class="text-danger">*</span></label><input class="form-control" id="anio_div" name="anio_div" placeholder="Ej.: 2.º año" required maxlength="50"></div>
            <div class="col-md-4"><label class="form-label" for="inasistencias">Inasistencias registradas <span class="text-danger">*</span></label><input type="number" min="0" class="form-control" id="inasistencias" name="inasistencias" required></div>
            <div class="col-md-7"><label class="form-label" for="adulto_responsable">Adulto responsable <span class="text-danger">*</span></label><input class="form-control" id="adulto_responsable" name="adulto_responsable" required maxlength="150"></div>
            <div class="col-md-5"><label class="form-label" for="vinculo">Vínculo con el estudiante <span class="text-danger">*</span></label><select class="form-select" id="vinculo" name="vinculo" required><option value="">Seleccionar...</option><option>Padre</option><option>Madre</option><option>Tutor/a</option><option>Adulto responsable</option></select></div>
            <div class="col-md-8"><label class="form-label" for="estudiante">Estudiante <span class="text-danger">*</span></label><input class="form-control" id="estudiante" name="estudiante" required maxlength="150"></div>
            <div class="col-md-4"><label class="form-label" for="fecha_consta">Fecha desde la que constan las inasistencias</label><input type="date" class="form-control" id="fecha_consta" name="fecha_consta"></div>
        </div>
        <h2 class="form-section-title"><i class="bi bi-chat-left-text"></i>Motivo de la solicitud</h2>
        <div class="mb-3"><label class="form-label" for="motivo">Motivo <span class="text-danger">*</span></label><textarea class="form-control" id="motivo" name="motivo" rows="6" required maxlength="4000" placeholder="Describa brevemente el motivo de las inasistencias y la solicitud de reincorporación."></textarea></div>
        <div class="alert alert-light border small"><i class="bi bi-info-circle me-2 text-secondary"></i>El PDF no incluye el apartado de acompañamiento ni la resolución de Dirección señalados en el modelo.</div>
        <div class="form-actions"><span class="required-note"><span class="text-danger">*</span> Campos obligatorios</span><button class="btn btn-secretaria btn-lg" type="submit"><i class="bi bi-file-earmark-pdf me-2"></i>Generar y descargar PDF</button></div>
    </form>
</main>
</body>
</html>

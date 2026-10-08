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
    <title>Crear acta | Secretaría</title>
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
            <div><span class="eyebrow">Secretaría · Documento institucional</span><h1>Crear acta</h1></div>
        </div>
        <a class="btn btn-outline-secondary" href="panel.php"><i class="bi bi-arrow-left me-2"></i>Volver</a>
    </header>
    <form class="form-card" method="post" action="generar-pdf.php" target="_blank">
        <input type="hidden" name="documento" value="acta">
        <input type="hidden" name="csrf_token" value="<?= secretaria_h(secretaria_csrf_token()) ?>">
        <h2 class="form-section-title"><i class="bi bi-card-heading"></i>Identificación del acta</h2>
        <div class="row g-3">
            <div class="col-sm-4"><label class="form-label" for="acta_nro">Acta N.º</label><input class="form-control" id="acta_nro" name="acta_nro" maxlength="30"></div>
            <div class="col-sm-4"><label class="form-label" for="ciclo_lectivo">Ciclo lectivo <span class="text-danger">*</span></label><input class="form-control" id="ciclo_lectivo" name="ciclo_lectivo" value="2026" required maxlength="10"></div>
            <div class="col-sm-4"><label class="form-label" for="fecha">Fecha <span class="text-danger">*</span></label><input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-8"><label class="form-label" for="estudiante">Nombre del alumno/a <span class="text-danger">*</span></label><input class="form-control" id="estudiante" name="estudiante" required maxlength="150"></div>
            <div class="col-md-4"><label class="form-label" for="curso_div">Curso / división <span class="text-danger">*</span></label><input class="form-control" id="curso_div" name="curso_div" required maxlength="50" placeholder="Ej.: 5.º C"></div>
            <div class="col-md-6"><label class="form-label" for="concepto">Concepto <span class="text-danger">*</span></label><input class="form-control" id="concepto" name="concepto" required maxlength="180" placeholder="Ej.: Reunión de seguimiento"></div>
            <div class="col-md-6"><label class="form-label" for="adulto_responsable">Adulto responsable <span class="text-danger">*</span></label><input class="form-control" id="adulto_responsable" name="adulto_responsable" required maxlength="150"></div>
            <div class="col-md-6"><label class="form-label" for="docente_administrativo">Docente / administrativo</label><input class="form-control" id="docente_administrativo" name="docente_administrativo" maxlength="150"></div>
        </div>
        <h2 class="form-section-title"><i class="bi bi-journal-text"></i>Contenido del acta</h2>
        <div class="mb-3"><label class="form-label" for="motivo">Motivo <span class="text-danger">*</span></label><textarea class="form-control" id="motivo" name="motivo" rows="5" required maxlength="4000"></textarea></div>
        <div class="mb-3"><label class="form-label" for="acuerdos_logrados">Acuerdos logrados</label><textarea class="form-control" id="acuerdos_logrados" name="acuerdos_logrados" rows="5" maxlength="4000"></textarea></div>
        <div class="mb-3"><label class="form-label" for="observaciones">Observaciones / aclaraciones</label><textarea class="form-control" id="observaciones" name="observaciones" rows="4" maxlength="4000"></textarea></div>
        <div class="form-actions"><span class="required-note"><span class="text-danger">*</span> Campos obligatorios</span><button class="btn btn-secretaria btn-lg" type="submit"><i class="bi bi-file-earmark-pdf me-2"></i>Generar y descargar PDF</button></div>
    </form>
</main>
</body>
</html>

<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title = ESCUELA_NOMBRE_CORTO . ' - Contacto';
$page_description = 'Contactanos para consultas, inscripciones o información sobre nuestra institución educativa';
require_once __DIR__ . '/header.php';
?>

<!-- Botón volver al inicio -->
<div class="container mt-4">
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-violeta">
            <i class="bi bi-arrow-left me-2"></i>Volver al inicio
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/sections/contacto.php'; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
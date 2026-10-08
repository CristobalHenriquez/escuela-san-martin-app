<?php
/**
 * Página de Contacto - EESO 225 San Martín
 * 
 * Muestra la información de contacto y un formulario.
 */

// Configuración y conexión
require_once '../includes/conexion.php';
require_once '../config/config.php';

// Definir la página actual para el menú de navegación
$page_type = 'contacto';

// Incluir el encabezado común del sitio público
include_once '../includes/header.php';
?>

<!-- ======= Breadcrumbs ======= -->
<div class="breadcrumbs">
    <div class="container" data-aos="fade-in">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Contacto</h2>
            <ol>
                <li><a href="index.php">Inicio</a></li>
                <li>Contacto</li>
            </ol>
        </div>
    </div>
</div><!-- End Breadcrumbs -->

<?php
// Incluir la sección de contacto que contiene la información y el formulario
include_once 'includes/seccion-contacto.php';

// Incluir el pie de página común del sitio público
include_once '../includes/footer.php';
?>
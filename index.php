<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title = ESCUELA_NOMBRE_CORTO . ' - Inicio';
require_once __DIR__ . '/header.php';

// Inicializar la conexión MySQLi de forma centralizada usando get_mysqli()
// get_mysqli() está definida en config/database.php y guarda la conexión en $GLOBALS['db']
$db = null;
if (function_exists('get_mysqli')) {
	$db = get_mysqli();
}
if (!$db) {
	echo '<div class="alert alert-danger">No se pudo establecer la conexión a la base de datos. Verifique la configuración en <code>config/config.php</code>.</div>';
}

// Mostrar secciones del frontend
include __DIR__ . '/includes/sections/hero.php';
include __DIR__ . '/includes/sections/galeria.php';
include __DIR__ . '/includes/sections/noticias.php';
include __DIR__ . '/includes/sections/personal.php';
include __DIR__ . '/includes/sections/logros.php';
include __DIR__ . '/includes/sections/contacto.php';




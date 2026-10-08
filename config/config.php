<?php
/**
 * Configuración General - EESO 225 San Martín
 * Archivo de configuración principal del sitio
 */

// Incluir configuración de base de datos
require_once __DIR__ . '/database.php';

// Configuración del entorno (se define en local.php según detección automática)
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Fallback por defecto
}
define('DEBUG', ENVIRONMENT === 'development');

// Configuración de errores
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuración de zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Configuración de idioma
setlocale(LC_TIME, 'es_AR.UTF-8', 'es_ES.UTF-8', 'spanish');

// Configuración de la escuela
define('ESCUELA_NOMBRE', 'E.E.S. ORIENTADA NRO 225 GENERAL JOSÉ DE SAN MARTÍN');
define('ESCUELA_NOMBRE_CORTO', 'EESO 225 San Martín');
define('ESCUELA_DIRECCION', 'Sarmiento 949');
define('ESCUELA_LOCALIDAD', 'Pérez');
define('ESCUELA_PROVINCIA', 'Santa Fe');
define('ESCUELA_CODIGO_POSTAL', 'S2121');
define('ESCUELA_TELEFONO', '3414951255 - 3413547139');
define('ESCUELA_EMAIL', 'sec225_perez@santafe.edu.ar');
define('ESCUELA_CUE', '8202817');
define('ESCUELA_CUE_ANEXO', '820281700');
define('ESCUELA_JURISDICCION', 'Santa Fe');
define('ESCUELA_DEPARTAMENTO', 'Rosario');
define('ESCUELA_DEPARTAMENTO_CODIGO', '82084');
define('ESCUELA_LOCALIDAD_CODIGO', '82084032');

// Configuración de redes sociales
define('FACEBOOK_URL', 'https://www.facebook.com/profile.php?id=100035366126426');
define('INSTAGRAM_URL', 'https://www.instagram.com/225lasanmartinenfotos?igsh=MXNobzQ1dzYycWlrcw==');
define('TWITTER_URL', '');
define('YOUTUBE_URL', '');

// Configuración de paginación
define('POSTS_POR_PAGINA', 6);
define('PERSONAL_POR_PAGINA', 12);
define('LOGROS_POR_PAGINA', 8);

// Configuración de imágenes
define('IMAGEN_NOTICIA_ANCHO', 800);
define('IMAGEN_NOTICIA_ALTO', 600);
define('IMAGEN_PERSONAL_ANCHO', 400);
define('IMAGEN_PERSONAL_ALTO', 400);
define('IMAGEN_LOGRO_ANCHO', 600);
define('IMAGEN_LOGRO_ALTO', 400);

// Configuración de cache
define('CACHE_DURACION', 3600); // 1 hora

// Función para obtener URL base
function obtenerUrlBase() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    if ($path === '/' || $path === '\\') {
        $path = '';
    }
    
    return $protocol . '://' . $host . $path;
}

// Función para obtener URL completa
function obtenerUrlCompleta($ruta = '') {
    $base = obtenerUrlBase();
    $ruta = ltrim($ruta, '/');
    return $base . ($ruta ? '/' . $ruta : '');
}

// Función para obtener ruta de archivo
function obtenerRutaArchivo($archivo) {
    return __DIR__ . '/../' . ltrim($archivo, '/');
}

// Función para verificar si es admin
function esAdmin() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['es_admin']) && $_SESSION['es_admin'];
}

// Función para requerir login de admin
function requerirAdmin() {
    if (!esAdmin()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

// Función para obtener datos del usuario actual
function obtenerUsuarioActual() {
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    
    $db = obtenerConexion();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    return $resultado->fetch_assoc();
}

// Función para log de actividad
function logActividad($accion, $detalles = '') {
    if (!DEBUG) return;
    
    $usuario = obtenerUsuarioActual();
    $usuario_nombre = $usuario ? $usuario['nombreyapellido'] : 'Sistema';
    
    $log = date('Y-m-d H:i:s') . " - {$usuario_nombre} - {$accion}";
    if ($detalles) {
        $log .= " - {$detalles}";
    }
    
    error_log($log);
}

// Función para limpiar cache
function limpiarCache() {
    $cache_dir = __DIR__ . '/../cache/';
    if (is_dir($cache_dir)) {
        $archivos = glob($cache_dir . '*');
        foreach ($archivos as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
    }
}

// Función para generar slug
function generarSlug($texto) {
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    return trim($texto, '-');
}

// Función para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para validar teléfono argentino
function validarTelefono($telefono) {
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    return preg_match('/^(11|2[0-9]{3}|3[0-9]{3})[0-9]{7,8}$/', $telefono);
}

// Configuración de headers de seguridad
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    if (ENVIRONMENT === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Cargar helper de configuración de sitio (clase SiteConfig)
if (file_exists(__DIR__ . '/SiteConfig.php')) {
    require_once __DIR__ . '/SiteConfig.php';
}
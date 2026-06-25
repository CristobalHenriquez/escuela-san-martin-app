<?php
/**
 * Configuración de Base de Datos - EESO 225 "La San Martín"
 * Archivo de conexión a la base de datos
 */

// Permitir overrides locales sin afectar otros entornos
// Crea "config/local.php" con tus credenciales locales (ignoradas por git)
if (file_exists(__DIR__ . '/local.php')) {
    require_once __DIR__ . '/local.php';
}

function env_config(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }
    return $default;
}

// Configuración de la base de datos (valores por defecto; se respetan si ya están definidos)
if (!defined('DB_HOST'))    define('DB_HOST', env_config('ESCUELA_DB_HOST', 'localhost'));
if (!defined('DB_PORT'))    define('DB_PORT', (int) env_config('ESCUELA_DB_PORT', '3306'));
if (!defined('DB_NAME'))    define('DB_NAME', env_config('ESCUELA_DB_NAME', 'escuela_san_martin'));
if (!defined('DB_USER'))    define('DB_USER', env_config('ESCUELA_DB_USER', 'root'));
if (!defined('DB_PASS'))    define('DB_PASS', env_config('ESCUELA_DB_PASS', ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', env_config('ESCUELA_DB_CHARSET', 'utf8mb4'));

// Configuración del sitio
if (!defined('SITE_NAME')) define('SITE_NAME', 'EESO 225 "La San Martín"');
// Ajusta SITE_URL en config/local.php si tu puerto MAMP es 8888 (ej: http://localhost:8888/Proyecto)
if (!defined('SITE_URL'))  define('SITE_URL', env_config('ESCUELA_SITE_URL', 'http://localhost/proyecto-web-escuela'));
if (!defined('ADMIN_URL')) define('ADMIN_URL', SITE_URL . '/admin');

// Configuración de archivos
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', 'uploads/');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Configuración de seguridad
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 3600); // 1 hora
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 8);

// Configuración de colores institucionales
if (!defined('COLOR_VIOLETA')) define('COLOR_VIOLETA', '#6A1B9A');
if (!defined('COLOR_GRIS'))    define('COLOR_GRIS', '#616161');
if (!defined('COLOR_BLANCO'))  define('COLOR_BLANCO', '#FFFFFF');

// Función de conexión a la base de datos
function conectarDB() {
    try {
        // Incluir puerto en el DSN (necesario para MAMP: 8889)
        // Para MAMP en Mac, usar socket directo si localhost no funciona
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Intentar con socket de MAMP si localhost falla
        if (DB_HOST === 'localhost' && file_exists('/Applications/MAMP/tmp/mysql/mysql.sock')) {
            $dsn = "mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        }
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        die("Error de conexión a la base de datos");
    }
}

// Función para obtener conexión MySQLi (compatible con admin existente)
function obtenerConexion() {
    // Pasar el puerto explícitamente (MAMP usa 8889 por defecto)
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    
    if ($conexion->connect_error) {
        error_log("Error de conexión MySQLi: " . $conexion->connect_error);
        die("Error de conexión a la base de datos");
    }
    
    $conexion->set_charset(DB_CHARSET);
    return $conexion;
}

// Wrapper para obtener la conexión MySQLi (centralizada)
function get_mysqli() {
    // Si ya existe una conexión global $db, retornarla
    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli) {
        return $GLOBALS['db'];
    }

    // Intentar crear una nueva conexión
    try {
        $conn = obtenerConexion();
        // Guardar en global para compatibilidad con código legado
        $GLOBALS['db'] = $conn;
        return $conn;
    } catch (Throwable $t) {
        error_log('get_mysqli: ' . $t->getMessage());
        return null;
    }
}

// Función para sanitizar datos
function sanitizar($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función para generar token CSRF
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para redimensionar imagen
function redimensionarImagen($archivo_origen, $archivo_destino, $ancho_max, $alto_max, $calidad = 80) {
    $info = getimagesize($archivo_origen);
    if (!$info) return false;
    
    $ancho_orig = $info[0];
    $alto_orig = $info[1];
    $tipo = $info[2];
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($ancho_max / $ancho_orig, $alto_max / $alto_orig);
    $ancho_nuevo = intval($ancho_orig * $ratio);
    $alto_nuevo = intval($alto_orig * $ratio);
    
    // Crear imagen según tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagen_orig = imagecreatefromjpeg($archivo_origen);
            break;
        case IMAGETYPE_PNG:
            $imagen_orig = imagecreatefrompng($archivo_origen);
            break;
        case IMAGETYPE_GIF:
            $imagen_orig = imagecreatefromgif($archivo_origen);
            break;
        case IMAGETYPE_WEBP:
            $imagen_orig = imagecreatefromwebp($archivo_origen);
            break;
        default:
            return false;
    }
    
    // Crear nueva imagen
    $imagen_nueva = imagecreatetruecolor($ancho_nuevo, $alto_nuevo);
    
    // Mantener transparencia para PNG y GIF
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($imagen_nueva, false);
        imagesavealpha($imagen_nueva, true);
        $transparente = imagecolorallocatealpha($imagen_nueva, 255, 255, 255, 127);
        imagefilledrectangle($imagen_nueva, 0, 0, $ancho_nuevo, $alto_nuevo, $transparente);
    }
    
    // Redimensionar
    imagecopyresampled($imagen_nueva, $imagen_orig, 0, 0, 0, 0, $ancho_nuevo, $alto_nuevo, $ancho_orig, $alto_orig);
    
    // Guardar como WebP
    $resultado = imagewebp($imagen_nueva, $archivo_destino, $calidad);
    
    // Liberar memoria
    imagedestroy($imagen_orig);
    imagedestroy($imagen_nueva);
    
    return $resultado;
}

// Función para formatear fecha
function formatearFecha($fecha, $formato = 'd/m/Y') {
    return date($formato, strtotime($fecha));
}

// Función para truncar texto
function truncarTexto($texto, $longitud = 100, $sufijo = '...') {
    if (strlen($texto) <= $longitud) {
        return $texto;
    }
    return substr($texto, 0, $longitud) . $sufijo;
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

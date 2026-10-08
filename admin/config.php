<?php
/**
 * Configuración del Admin - EESO 225 San Martín
 * Archivo de configuración específico para el panel de administración
 */

// Incluir configuración principal
require_once __DIR__ . '/../config/config.php';

// Configuración específica del admin
define('ADMIN_TITLE', 'Panel de Administración - ' . ESCUELA_NOMBRE);
define('ADMIN_VERSION', '1.0.0');

// Configuración de paginación del admin
define('ADMIN_POSTS_POR_PAGINA', 10);
define('ADMIN_PERSONAL_POR_PAGINA', 15);
define('ADMIN_LOGROS_POR_PAGINA', 12);

// Configuración de uploads del admin
define('ADMIN_MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ADMIN_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx']);

// Configuración de TinyMCE
define('TINYMCE_API_KEY', ''); // Si usas TinyMCE Cloud
define('TINYMCE_HEIGHT', 500);
define('TINYMCE_PLUGINS', 'link image lists media table code');
define('TINYMCE_TOOLBAR', 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media | code');

// Configuración de colores del admin
define('ADMIN_COLOR_PRIMARY', COLOR_VIOLETA);
define('ADMIN_COLOR_SECONDARY', COLOR_GRIS);

// Configuración de seguridad para creación de usuarios administradores
define('ADMIN_CREATE_USER_SECRET_KEY', 'EESO225_ADMIN_ACCESS_2025'); // Cambiar esta clave por seguridad
define('ADMIN_CREATE_USER_SESSION_TIME', 1800); // 30 minutos en segundos
define('ADMIN_COLOR_SUCCESS', '#28a745');
define('ADMIN_COLOR_WARNING', '#ffc107');
define('ADMIN_COLOR_DANGER', '#dc3545');
define('ADMIN_COLOR_INFO', '#17a2b8');

// Función para obtener configuración de TinyMCE
function obtenerConfiguracionTinyMCE() {
    return [
        'height' => TINYMCE_HEIGHT,
        'plugins' => TINYMCE_PLUGINS,
        'toolbar' => TINYMCE_TOOLBAR,
        'menubar' => false,
        'automatic_uploads' => true,
        'images_upload_credentials' => true,
        'skin' => 'oxide',
        'content_css' => 'default',
        'convert_urls' => false,
        'relative_urls' => true,
        'remove_script_host' => true,
        'document_base_url' => obtenerUrlCompleta() . '/',
        'images_upload_url' => obtenerUrlCompleta('admin/upload.php'),
        'paste_data_images' => true,
        'image_advtab' => true,
        'image_caption' => true,
        'file_picker_types' => 'image'
    ];
}

// Función para obtener configuración de SweetAlert2
function obtenerConfiguracionSweetAlert() {
    return [
        'confirmButtonColor' => ADMIN_COLOR_PRIMARY,
        'cancelButtonColor' => ADMIN_COLOR_SECONDARY,
        'dangerMode' => true,
        'reverseButtons' => true,
        'focusCancel' => true
    ];
}

// Función para obtener breadcrumbs
function obtenerBreadcrumbs($pagina_actual) {
    $breadcrumbs = [
        'admin.php' => 'Panel',
        'noticias.php' => 'Noticias',
        'personal.php' => 'Personal Docente',
        'logros.php' => 'Logros Estudiantiles',
        'cargar-noticia.php' => 'Nueva Noticia',
        'cargar-personal.php' => 'Nuevo Personal',
        'cargar-logro.php' => 'Nuevo Logro',
        'editar-noticia.php' => 'Editar Noticia',
        'editar-personal.php' => 'Editar Personal',
        'editar-logro.php' => 'Editar Logro'
    ];
    
    return $breadcrumbs[$pagina_actual] ?? 'Página';
}

// Función para obtener menú de navegación
function obtenerMenuNavegacion() {
    return [
        [
            'titulo' => 'Dashboard',
            'icono' => 'bi-speedometer2',
            'url' => 'admin.php',
            'activo' => basename($_SERVER['PHP_SELF']) === 'admin.php'
        ],
        [
            'titulo' => 'Noticias',
            'icono' => 'bi-newspaper',
            'url' => 'noticias.php',
            'activo' => basename($_SERVER['PHP_SELF']) === 'noticias.php'
        ],
        [
            'titulo' => 'Personal Docente',
            'icono' => 'bi-people',
            'url' => 'personal.php',
            'activo' => basename($_SERVER['PHP_SELF']) === 'personal.php'
        ],
        [
            'titulo' => 'Logros Estudiantiles',
            'icono' => 'bi-trophy',
            'url' => 'logros.php',
            'activo' => basename($_SERVER['PHP_SELF']) === 'logros.php'
        ]
    ];
}

// Función para obtener estadísticas del dashboard
function obtenerEstadisticasDashboard() {
    global $db;
    
    $estadisticas = [];
    
    // Noticias
    $resultado = $db->query("SELECT COUNT(*) as total FROM posts");
    $estadisticas['noticias'] = $resultado->fetch_assoc()['total'];
    
    // Noticias publicadas
    $resultado = $db->query("SELECT COUNT(*) as total FROM posts WHERE visible = 1");
    $estadisticas['noticias_publicadas'] = $resultado->fetch_assoc()['total'];
    
    // Personal docente
    $resultado = $db->query("SELECT COUNT(*) as total FROM personal_docente");
    $estadisticas['personal'] = $resultado->fetch_assoc()['total'];
    
    // Personal visible
    $resultado = $db->query("SELECT COUNT(*) as total FROM personal_docente WHERE visible = 1");
    $estadisticas['personal_visible'] = $resultado->fetch_assoc()['total'];
    
    // Logros estudiantiles
    $resultado = $db->query("SELECT COUNT(*) as total FROM logros_estudiantiles");
    $estadisticas['logros'] = $resultado->fetch_assoc()['total'];
    
    // Logros visibles
    $resultado = $db->query("SELECT COUNT(*) as total FROM logros_estudiantiles WHERE visible = 1");
    $estadisticas['logros_visibles'] = $resultado->fetch_assoc()['total'];
    
    return $estadisticas;
}

// Función para obtener actividad reciente
function obtenerActividadReciente($limite = 10) {
    global $db;
    
    $actividad = [];
    
    // Noticias recientes
    $resultado = $db->query("SELECT 'noticia' as tipo, titulo, created_at FROM posts ORDER BY created_at DESC LIMIT $limite");
    while ($row = $resultado->fetch_assoc()) {
        $actividad[] = $row;
    }
    
    // Personal reciente
    $resultado = $db->query("SELECT 'personal' as tipo, CONCAT(nombre, ' ', apellido) as titulo, fecha_creacion as created_at FROM personal_docente ORDER BY fecha_creacion DESC LIMIT $limite");
    while ($row = $resultado->fetch_assoc()) {
        $actividad[] = $row;
    }
    
    // Logros recientes
    $resultado = $db->query("SELECT 'logro' as tipo, nombre_estudiante as titulo, fecha_creacion as created_at FROM logros_estudiantiles ORDER BY fecha_creacion DESC LIMIT $limite");
    while ($row = $resultado->fetch_assoc()) {
        $actividad[] = $row;
    }
    
    // Ordenar por fecha
    usort($actividad, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($actividad, 0, $limite);
}

// Función para generar token CSRF
function generarTokenCSRFAdmin() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verificarTokenCSRFAdmin($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Función para formatear tamaño de archivo
function formatearTamanoArchivo($bytes) {
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $potencia = floor(($bytes ? log($bytes) : 0) / log(1024));
    $potencia = min($potencia, count($unidades) - 1);
    $bytes /= pow(1024, $potencia);
    return round($bytes, 2) . ' ' . $unidades[$potencia];
}

// Función para obtener información del sistema
function obtenerInformacionSistema() {
    return [
        'php_version' => PHP_VERSION,
        'mysql_version' => obtenerConexion()->server_info,
        'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
        'espacio_disco' => formatearTamanoArchivo(disk_free_space(__DIR__)),
        'memoria_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
}
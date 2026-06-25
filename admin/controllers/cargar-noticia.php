<?php
// Controlador para crear nuevas noticias escolares
require_once '../../includes/conexion.php';
require_once '../config.php';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../noticias.php');
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Error de seguridad: token CSRF inválido.";
    header('Location: ../noticias.php?error=csrf');
    exit;
}

// Verificar autenticación
verificarAutenticacion();

// Obtener y validar datos del formulario
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$contenido = isset($_POST['contenido']) ? $_POST['contenido'] : '';
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : 'noticia';
$visible = !empty($_POST['visible']) ? 1 : 0;
$orden = intval($_POST['orden'] ?? 0);
$fecha_publicacion = !empty($_POST['fecha_publicacion']) ? $_POST['fecha_publicacion'] : date('Y-m-d');

// Validar título (obligatorio)
if (empty($titulo)) {
    $_SESSION['error_message'] = "El título es obligatorio.";
    header('Location: ../noticias.php?error=titulo');
    exit;
}

// Validar contenido (obligatorio)
if (empty($contenido)) {
    $_SESSION['error_message'] = "El contenido es obligatorio.";
    header('Location: ../noticias.php?error=contenido');
    exit;
}

// Validar categoría
$categorias_validas = ['noticia', 'evento', 'curso'];
if (!in_array($categoria, $categorias_validas)) {
    $_SESSION['error_message'] = "La categoría seleccionada no es válida.";
    header('Location: ../noticias.php?error=categoria');
    exit;
}

// Procesar imagen si se ha subido
$imagen_path = '';
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    // Validar tipo de imagen
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($_FILES['imagen']['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error_message'] = "Formato de imagen no válido. Solo se permiten JPG, PNG, GIF y WebP.";
        header('Location: ../noticias.php?error=formato_imagen');
        exit;
    }

    // Crear directorio para noticias si no existe
    $upload_dir = "../../uploads/noticias";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generar nombre único para la imagen
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['imagen']['name'], PATHINFO_FILENAME));
    $webp_filename = $filename . '.webp';
    $webp_path = $upload_dir . '/' . $webp_filename;
    $imagen_path = "uploads/noticias/" . $webp_filename;

    // Redimensionar y convertir imagen a WebP
    if (!redimensionarImagen($_FILES['imagen']['tmp_name'], $webp_path, IMAGEN_NOTICIA_ANCHO, IMAGEN_NOTICIA_ALTO)) {
        $_SESSION['error_message'] = "Error al procesar la imagen.";
        header('Location: ../noticias.php?error=imagen');
        exit;
    }
}

// Insertar en la base de datos
try {
    $stmt = $db->prepare("INSERT INTO posts (titulo, contenido, imagen, categoria, visible, orden, fecha_publicacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiis", $titulo, $contenido, $imagen_path, $categoria, $visible, $orden, $fecha_publicacion);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Noticia guardada correctamente.";
        header('Location: ../noticias.php?success=1');
    } else {
        $_SESSION['error_message'] = "Error al guardar en la base de datos: " . $db->error;
        header('Location: ../noticias.php?error=db');
    }
} catch (Exception $e) {
    error_log("Error al guardar noticia: " . $e->getMessage());
    $_SESSION['error_message'] = "Error al guardar la noticia: " . $e->getMessage();
    header('Location: ../noticias.php?error=db');
}

exit;


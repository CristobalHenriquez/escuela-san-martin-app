<?php
// Controlador para editar logros estudiantiles existentes
require_once '../../includes/conexion.php';
require_once '../config.php';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../logros.php');
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Error de seguridad: token CSRF inválido.";
    header('Location: ../logros.php?error=csrf');
    exit;
}

// Verificar autenticación
verificarAutenticacion();

// Obtener y validar datos del formulario
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nombre_estudiante = isset($_POST['nombre_estudiante']) ? trim($_POST['nombre_estudiante']) : '';
$curso_division = isset($_POST['curso_division']) ? trim($_POST['curso_division']) : '';
$logro = isset($_POST['logro']) ? trim($_POST['logro']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$fecha_logro = isset($_POST['fecha_logro']) ? $_POST['fecha_logro'] : null;
$visible = !empty($_POST['visible']) ? 1 : 0;
$foto_actual = isset($_POST['foto_actual']) ? $_POST['foto_actual'] : '';

// Validar ID
if ($id <= 0) {
    $_SESSION['error_message'] = "ID de logro estudiantil inválido.";
    header('Location: ../logros.php?error=id');
    exit;
}

// Validar nombre del estudiante (obligatorio)
if (empty($nombre_estudiante)) {
    $_SESSION['error_message'] = "El nombre del estudiante es obligatorio.";
    header('Location: ../logros.php?error=nombre');
    exit;
}

// Validar curso y división (obligatorio)
if (empty($curso_division)) {
    $_SESSION['error_message'] = "El curso y división son obligatorios.";
    header('Location: ../logros.php?error=curso');
    exit;
}

// Validar logro (obligatorio)
if (empty($logro)) {
    $_SESSION['error_message'] = "El tipo de logro es obligatorio.";
    header('Location: ../logros.php?error=logro');
    exit;
}

// Validar descripción (obligatorio)
if (empty($descripcion)) {
    $_SESSION['error_message'] = "La descripción es obligatoria.";
    header('Location: ../logros.php?error=descripcion');
    exit;
}

// Validar fecha si se proporciona
if (!empty($fecha_logro) && !strtotime($fecha_logro)) {
    $_SESSION['error_message'] = "La fecha del logro no es válida.";
    header('Location: ../logros.php?error=fecha');
    exit;
}

// Procesar imagen si se ha subido una nueva (opcional en edición)
$imagen_path = $foto_actual; // Mantener la imagen actual por defecto

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    // Validar tipo de imagen
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($_FILES['foto']['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error_message'] = "Formato de imagen no válido. Solo se permiten JPG, PNG, GIF y WebP.";
        header('Location: ../logros.php?error=formato_imagen');
        exit;
    }

    // Crear directorio para logros si no existe
    $upload_dir = "../../uploads/logros";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generar nombre único para la imagen
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['foto']['name'], PATHINFO_FILENAME));
    $webp_filename = $filename . '.webp';
    $webp_path = $upload_dir . '/' . $webp_filename;
    $imagen_path = "uploads/logros/" . $webp_filename;

    // Redimensionar y convertir imagen a WebP
    if (!redimensionarImagen($_FILES['foto']['tmp_name'], $webp_path, IMAGEN_LOGRO_ANCHO, IMAGEN_LOGRO_ALTO)) {
        $_SESSION['error_message'] = "Error al procesar la imagen.";
        header('Location: ../logros.php?error=imagen');
        exit;
    }

    // Eliminar imagen anterior si existe
    if (!empty($foto_actual) && file_exists('../../' . $foto_actual)) {
        unlink('../../' . $foto_actual);
    }
}

// Actualizar en la base de datos
try {
    $stmt = $db->prepare("UPDATE logros_estudiantiles SET nombre_estudiante = ?, curso_division = ?, logro = ?, descripcion = ?, foto = ?, fecha_logro = ?, visible = ? WHERE id = ?");
    $stmt->bind_param("ssssssii", $nombre_estudiante, $curso_division, $logro, $descripcion, $imagen_path, $fecha_logro, $visible, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Logro estudiantil actualizado correctamente.";
        header('Location: ../logros.php?update=ok');
    } else {
        $_SESSION['error_message'] = "Error al actualizar en la base de datos: " . $db->error;
        header('Location: ../logros.php?error=db');
    }
} catch (Exception $e) {
    error_log("Error al actualizar logro estudiantil: " . $e->getMessage());
    $_SESSION['error_message'] = "Error al actualizar el logro estudiantil: " . $e->getMessage();
    header('Location: ../logros.php?error=db');
}

exit;


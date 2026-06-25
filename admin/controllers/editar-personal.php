<?php
// Controlador para editar personal docente existente
require_once '../../includes/conexion.php';
require_once '../config.php';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../personal.php');
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Error de seguridad: token CSRF inválido.";
    header('Location: ../personal.php?error=csrf');
    exit;
}

// Verificar autenticación
verificarAutenticacion();

// Obtener y validar datos del formulario
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$materia = isset($_POST['materia']) ? trim($_POST['materia']) : '';
$especialidad = isset($_POST['especialidad']) ? trim($_POST['especialidad']) : '';
$email_institucional = isset($_POST['email_institucional']) ? trim($_POST['email_institucional']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$biografia = isset($_POST['biografia']) ? trim($_POST['biografia']) : '';
$linkedin = isset($_POST['linkedin']) ? trim($_POST['linkedin']) : '';
$visible = !empty($_POST['visible']) ? 1 : 0;
$foto_actual = isset($_POST['foto_actual']) ? $_POST['foto_actual'] : '';

// Validar ID
if ($id <= 0) {
    $_SESSION['error_message'] = "ID de personal docente inválido.";
    header('Location: ../personal.php?error=id');
    exit;
}

// Validar nombre (obligatorio)
if (empty($nombre)) {
    $_SESSION['error_message'] = "El nombre es obligatorio.";
    header('Location: ../personal.php?error=nombre');
    exit;
}

// Validar apellido (obligatorio)
if (empty($apellido)) {
    $_SESSION['error_message'] = "El apellido es obligatorio.";
    header('Location: ../personal.php?error=apellido');
    exit;
}

// Validar materia (obligatorio)
if (empty($materia)) {
    $_SESSION['error_message'] = "La materia es obligatoria.";
    header('Location: ../personal.php?error=materia');
    exit;
}

// Validar biografía (obligatorio)
if (empty($biografia)) {
    $_SESSION['error_message'] = "La biografía es obligatoria.";
    header('Location: ../personal.php?error=biografia');
    exit;
}

// Validar email si se proporciona
if (!empty($email_institucional) && !validarEmail($email_institucional)) {
    $_SESSION['error_message'] = "El email institucional no es válido.";
    header('Location: ../personal.php?error=email');
    exit;
}

// Validar LinkedIn si se proporciona
if (!empty($linkedin) && !filter_var($linkedin, FILTER_VALIDATE_URL)) {
    $_SESSION['error_message'] = "La URL de LinkedIn no es válida.";
    header('Location: ../personal.php?error=linkedin');
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
        header('Location: ../personal.php?error=formato_imagen');
        exit;
    }

    // Crear directorio para personal si no existe
    $upload_dir = "../../uploads/personal";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generar nombre único para la imagen
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['foto']['name'], PATHINFO_FILENAME));
    $webp_filename = $filename . '.webp';
    $webp_path = $upload_dir . '/' . $webp_filename;
    $imagen_path = "uploads/personal/" . $webp_filename;

    // Redimensionar y convertir imagen a WebP (cuadrada para personal docente)
    if (!redimensionarImagen($_FILES['foto']['tmp_name'], $webp_path, IMAGEN_PERSONAL_ANCHO, IMAGEN_PERSONAL_ALTO)) {
        $_SESSION['error_message'] = "Error al procesar la imagen.";
        header('Location: ../personal.php?error=imagen');
        exit;
    }

    // Eliminar imagen anterior si existe
    if (!empty($foto_actual) && file_exists('../../' . $foto_actual)) {
        unlink('../../' . $foto_actual);
    }
}

// Actualizar en la base de datos
try {
    $stmt = $db->prepare("UPDATE personal_docente SET nombre = ?, apellido = ?, materia = ?, especialidad = ?, email_institucional = ?, telefono = ?, biografia = ?, foto = ?, linkedin = ?, visible = ? WHERE id = ?");
    $stmt->bind_param("sssssssssii", $nombre, $apellido, $materia, $especialidad, $email_institucional, $telefono, $biografia, $imagen_path, $linkedin, $visible, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Personal docente actualizado correctamente.";
        header('Location: ../personal.php?update=ok');
    } else {
        $_SESSION['error_message'] = "Error al actualizar en la base de datos: " . $db->error;
        header('Location: ../personal.php?error=db');
    }
} catch (Exception $e) {
    error_log("Error al actualizar personal docente: " . $e->getMessage());
    $_SESSION['error_message'] = "Error al actualizar el personal docente: " . $e->getMessage();
    header('Location: ../personal.php?error=db');
}

exit;


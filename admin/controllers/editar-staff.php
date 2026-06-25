<?php
// Este archivo procesa la edición de miembros del staff existentes
require_once '../../includes/conexion.php';
include_once '../includes/sweetalert.php';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff.php');
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Error de seguridad: token CSRF inválido.";
    header('Location: ../staff.php?error=csrf');
    exit;
}

// Obtener y validar datos del formulario
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$cargo = isset($_POST['cargo']) ? trim($_POST['cargo']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$linkedin = isset($_POST['linkedin']) ? trim($_POST['linkedin']) : '';
$visible = !empty($_POST['visible']) ? 1 : 0;
$imagen_actual = isset($_POST['imagen_actual']) ? $_POST['imagen_actual'] : '';

// Validar ID
if ($id <= 0) {
    $_SESSION['error_message'] = "ID de miembro del staff inválido.";
    header('Location: ../staff.php?error=id');
    exit;
}

// Validar nombre (obligatorio)
if (empty($nombre)) {
    $_SESSION['error_message'] = "El nombre es obligatorio.";
    header('Location: ../staff.php?error=nombre');
    exit;
}

// Validar cargo (obligatorio)
if (empty($cargo)) {
    $_SESSION['error_message'] = "El cargo es obligatorio.";
    header('Location: ../staff.php?error=cargo');
    exit;
}

// Validar descripción (obligatoria)
if (empty($descripcion)) {
    $_SESSION['error_message'] = "La descripción es obligatoria.";
    header('Location: ../staff.php?error=descripcion');
    exit;
}

// Validar LinkedIn (opcional, pero debe ser una URL válida si se proporciona)
if (!empty($linkedin) && !filter_var($linkedin, FILTER_VALIDATE_URL)) {
    $_SESSION['error_message'] = "La URL de LinkedIn no es válida.";
    header('Location: ../staff.php?error=linkedin');
    exit;
}

// Procesar imagen si se ha subido una nueva (opcional en edición)
$imagen_path = $imagen_actual; // Mantener la imagen actual por defecto
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    // Validar tipo de imagen
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($_FILES['imagen']['tmp_name']);

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error_message'] = "Formato de imagen no válido. Solo se permiten JPG, PNG, GIF y WebP.";
        header('Location: ../staff.php?error=formato_imagen');
        exit;
    }

    // Crear directorio para staff si no existe
    $upload_dir = "../../uploads/staff";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generar nombre único para la imagen
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['imagen']['name'], PATHINFO_FILENAME));
    $webp_filename = $filename . '.webp';
    $webp_path = $upload_dir . '/' . $webp_filename;
    $imagen_path = "uploads/staff/" . $webp_filename;

    // Convertir imagen a WebP
    $info = getimagesize($_FILES['imagen']['tmp_name']);
    $isAlpha = false;

    if ($info !== false) {
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($_FILES['imagen']['tmp_name']);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($_FILES['imagen']['tmp_name']);
                if (imagecolortransparent($image) >= 0 || (imagecolorstotal($image) == 0)) {
                    $isAlpha = true;
                }
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($_FILES['imagen']['tmp_name']);
                $isAlpha = true;
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($_FILES['imagen']['tmp_name']);
                $isAlpha = true;
                break;
            default:
                // Formato no soportado
                $_SESSION['error_message'] = "Formato de imagen no válido. Solo se permiten JPG, PNG, GIF y WebP.";
                header('Location: ../staff.php?error=formato_imagen');
                exit;
        }

        if ($image !== null) {
            // Preparar para mantener la transparencia si es necesario
            if ($isAlpha) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }

            // Guardar como WebP
            if (!imagewebp($image, $webp_path, 80)) {
                $_SESSION['error_message'] = "Error al convertir la imagen a formato WebP.";
                header('Location: ../staff.php?error=conversion_imagen');
                exit;
            }

            // Liberar memoria
            imagedestroy($image);

            // Eliminar imagen anterior si existe
            if (!empty($imagen_actual) && file_exists('../../' . $imagen_actual)) {
                unlink('../../' . $imagen_actual);
            }
        } else {
            // Error al procesar la imagen
            $_SESSION['error_message'] = "No se pudo procesar la imagen. Verifique que sea una imagen válida.";
            header('Location: ../staff.php?error=imagen');
            exit;
        }
    } else {
        // Error al procesar la imagen
        $_SESSION['error_message'] = "No se pudo procesar la imagen. Verifique que sea una imagen válida.";
        header('Location: ../staff.php?error=imagen');
        exit;
    }
}

// Actualizar en la base de datos
try {
    $stmt = $db->prepare("UPDATE staff SET nombre = ?, cargo = ?, descripcion = ?, linkedin = ?, imagen = ?, visible = ? WHERE id = ?");
    $stmt->bind_param("sssssii", $nombre, $cargo, $descripcion, $linkedin, $imagen_path, $visible, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Miembro del staff actualizado correctamente.";
        header('Location: ../staff.php?update=ok');
    } else {
        $_SESSION['error_message'] = "Error al actualizar en la base de datos: " . $db->error;
        header('Location: ../staff.php?error=db');
    }
} catch (Exception $e) {
    error_log("Error al actualizar miembro del staff: " . $e->getMessage());
    $_SESSION['error_message'] = "Error al actualizar el miembro del staff: " . $e->getMessage();
    header('Location: ../staff.php?error=db');
}

exit;

<?php
// Controlador para crear nuevas publicaciones (posts)
require_once '../../includes/conexion.php';
require_once '../config.php';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../posts.php');
    exit;
}

// Verificar CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Error de seguridad: token CSRF inválido.";
    header('Location: ../posts.php?error=csrf');
    exit;
}

// Verificar autenticación
verificarAutenticacion();

// Obtener datos
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$contenido = $_POST['contenido'] ?? '';
$categoria = $_POST['categoria'] ?? 'post';
$visible = !empty($_POST['visible']) ? 1 : 0;
$orden = intval($_POST['orden'] ?? 0);
$fecha = !empty($_POST['fecha_publicacion']) ? $_POST['fecha_publicacion'] : date('Y-m-d');

if (empty($titulo)) {
    $_SESSION['error_message'] = 'El título es obligatorio';
    header('Location: ../posts.php?error=titulo');
    exit;
}

try {
    // Insert preliminar para obtener ID
    $stmt = $db->prepare("INSERT INTO posts (titulo, contenido, imagen, categoria, visible, orden, fecha_publicacion) VALUES (?, ?, '', ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $titulo, $contenido, $categoria, $visible, $orden, $fecha);

    if (!$stmt->execute()) {
        $_SESSION['error_message'] = "Error al guardar: " . $stmt->error;
        header('Location: ../posts.php?error=db');
        exit;
    }

    $post_id = $db->insert_id;

    // Crear directorio del post
    $post_dir = "../../uploads/posts/{$post_id}";
    if (!is_dir($post_dir)) {
        mkdir($post_dir, 0755, true);
    }

    // Manejo imagen principal
    $imagen = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $tmp = $_FILES['imagen']['tmp_name'];
        $final = "uploads/posts/{$post_id}/portada.webp";

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($tmp);

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = 'Solo se permiten imágenes (JPG, PNG, GIF, WEBP)';
            header('Location: ../posts.php?error=formato_imagen');
            exit;
        }

        if (!function_exists('imagewebp')) {
            error_log("La función imagewebp no está disponible.");
            // Intentar mover sin conversión
            if (move_uploaded_file($tmp, '../../' . $final)) {
                $imagen = $final;
            } else {
                $_SESSION['error_message'] = 'Error al subir la imagen.';
                header('Location: ../posts.php?error=imagen');
                exit;
            }
        } else {
            // Convertir a WebP usando GD
            $info = getimagesize($tmp);
            $image = null;
            switch ($info[2]) {
                case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($tmp); break;
                case IMAGETYPE_PNG: $image = imagecreatefrompng($tmp); break;
                case IMAGETYPE_GIF: $image = imagecreatefromgif($tmp); break;
                case IMAGETYPE_WEBP: copy($tmp, '../../' . $final); $imagen = $final; break;
            }

            if ($image) {
                // Guardar como WebP
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                imagewebp($image, '../../' . $final, 80);
                imagedestroy($image);
                $imagen = $final;
            }
        }
    }

    // Procesar imágenes embebidas del editor (uploads/temp -> uploads/posts/{id})
    $contenido_actualizado = $contenido;
    preg_match_all('/<img[^>]+src="([^"]+)"/i', $contenido, $matches);
    $imagenes = $matches[1] ?? [];

    foreach ($imagenes as $img_src) {
        if (strpos($img_src, 'uploads/temp/') !== false) {
            // Obtener ruta de archivo respecto al proyecto
            $img_path = '../../' . str_replace(SiteConfig::getBaseDir() . '/', '', $img_src);
            if (file_exists($img_path)) {
                $basename = basename($img_path);
                $new_rel = "uploads/posts/{$post_id}/{$basename}";
                if (copy($img_path, '../../' . $new_rel)) {
                    $new_url = SiteConfig::getBaseDir() . '/' . $new_rel;
                    $contenido_actualizado = str_replace($img_src, $new_url, $contenido_actualizado);
                    unlink($img_path);
                }
            }
        }
    }

    // Actualizar post con imagen y contenido
    $stmt = $db->prepare("UPDATE posts SET imagen = ?, contenido = ? WHERE id = ?");
    $stmt->bind_param("ssi", $imagen, $contenido_actualizado, $post_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Publicación creada correctamente';
        header('Location: ../posts.php?success=1');
        exit;
    } else {
        $_SESSION['error_message'] = 'Error al actualizar: ' . $stmt->error;
        header('Location: ../posts.php?error=db');
        exit;
    }

} catch (Exception $e) {
    error_log('Error al guardar post: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Error al guardar la publicación: ' . $e->getMessage();
    header('Location: ../posts.php?error=exception');
    exit;
}

?>

<?php
// Incluir el archivo de configuración
require_once 'config.php';
require_once '../includes/conexion.php';

// Función para registrar errores
function logError($message)
{
    $logFile = '../logs/upload_errors.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . "\n", 3, $logFile);
}

// Función para depurar información
function debug($message)
{
    logError("DEBUG: " . $message);
}

// Función para convertir imagen a WebP
function convertToWebP($source, $destination, $quality = 80)
{
    $info = getimagesize($source);
    $isAlpha = false;

    if ($info === false) {
        return false;
    }

    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            // Verificar si la imagen PNG tiene canal alfa
            if (imagecolortransparent($image) >= 0) {
                $isAlpha = true;
            }
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            $isAlpha = true;
            break;
        case IMAGETYPE_WEBP:
            // Ya es WebP, simplemente copiar el archivo
            return copy($source, $destination);
        default:
            return false;
    }

    if ($isAlpha) {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }

    $result = imagewebp($image, $destination, $quality);
    imagedestroy($image);

    return $result;
}

// Procesar la subida de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registrar información de la solicitud para depuración
    debug("Solicitud recibida: " . print_r($_POST, true));
    debug("Archivos recibidos: " . print_r($_FILES, true));

    // Verificar si se ha enviado un archivo
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        // Usar mime_content_type si está disponible, de lo contrario usar el tipo proporcionado
        if (function_exists('mime_content_type')) {
            $fileType = mime_content_type($_FILES['file']['tmp_name']);
            debug("Tipo MIME detectado con mime_content_type: $fileType");
        } else {
            $fileType = $_FILES['file']['type'];
            debug("Tipo MIME proporcionado por el navegador: $fileType");
        }

        // Verificación adicional para WebP
        $fileContent = file_get_contents($_FILES['file']['tmp_name'], false, null, 0, 12);
        $isWebP = (substr($fileContent, 8, 4) === 'WEBP');
        if ($isWebP) {
            debug("Archivo identificado como WebP por contenido");
            $fileType = 'image/webp';
        }

        if (!in_array($fileType, $allowedTypes)) {
            logError("Tipo de archivo no permitido: $fileType");
            http_response_code(400);
            echo json_encode(['error' => 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.']);
            exit;
        }

        // Generar nombre de archivo seguro
        $baseName = pathinfo(basename($_FILES['file']['name']), PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '', $baseName);

        // Determinar la ubicación de guardado
        $source = isset($_POST['source']) ? $_POST['source'] : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        // Siempre usar extensión WebP para el archivo final
        $filename = time() . '_' . $baseName . '.webp';

        // Si estamos editando un post existente y tenemos su ID
        if ($post_id > 0) {
            $uploadDir = "../uploads/posts/{$post_id}";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filepath = $uploadDir . '/' . $filename;
            $relativePath = "uploads/posts/{$post_id}/{$filename}";
        }
        // Si estamos creando un nuevo post (TinyMCE temporal)
        else {
            // Crear directorio temporal para imágenes de TinyMCE
            $uploadDir = "../uploads/temp";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filepath = $uploadDir . '/' . $filename;
            $relativePath = "uploads/temp/{$filename}";
        }

        debug("Intentando guardar archivo en: $filepath");
        debug("Permisos del directorio: " . substr(sprintf('%o', fileperms($uploadDir)), -4));

        // Convertir a WebP y guardar
        if (convertToWebP($_FILES['file']['tmp_name'], $filepath)) {
            // Verificar que el archivo se haya guardado correctamente
            if (file_exists($filepath)) {
                // Generar URL adaptada al entorno
                $imageUrl = SiteConfig::getUploadUrl($relativePath);

                // Registrar éxito
                debug("Imagen convertida y guardada exitosamente como WebP: $imageUrl");

                header('Content-Type: application/json');
                echo json_encode(['location' => $imageUrl]);
                exit;
            } else {
                $errorMsg = "El archivo se convirtió pero no se encuentra en $filepath";
                logError($errorMsg);
                http_response_code(500);
                echo json_encode(['error' => $errorMsg]);
                exit;
            }
        } else {
            // Si la conversión falla, intentar el método tradicional
            debug("La conversión a WebP falló, intentando método tradicional");
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
                $imageUrl = SiteConfig::getUploadUrl($relativePath);
                debug("Imagen subida exitosamente (sin conversión): $imageUrl");

                header('Content-Type: application/json');
                echo json_encode(['location' => $imageUrl]);
                exit;
            } else {
                $errorMsg = 'Error al guardar la imagen. Permisos: ' . substr(sprintf('%o', fileperms($uploadDir)), -4);
                logError($errorMsg);
                logError("Último error PHP: " . print_r(error_get_last(), true));
                http_response_code(500);
                echo json_encode(['error' => $errorMsg]);
                exit;
            }
        }
    } else {
        // Error en la subida del archivo
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario.',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión PHP detuvo la subida.'
        ];

        $errorCode = isset($_FILES['file']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
        $errorMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Error desconocido al subir el archivo.';

        logError("Error de subida: $errorMessage (Código: $errorCode)");
        http_response_code(400);
        echo json_encode(['error' => $errorMessage]);
        exit;
    }
}

// Si llegamos aquí, algo salió mal
logError("Solicitud inválida a upload.php. Método: " . $_SERVER['REQUEST_METHOD']);
http_response_code(400);
echo json_encode(['error' => 'Solicitud inválida. Debe enviar un archivo.']);

<?php
// Configuración de la página
$admin_page_title = 'Editar Publicación | ALPA Servicios Ambientales';
$admin_page_description = 'Formulario para editar una publicación existente en el sitio web';

// Procesar la lógica antes de cualquier salida HTML
require_once '../includes/conexion.php';
require_once 'config.php';

// Activar registro de errores
error_log("Método: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  error_log("POST recibido: " . print_r($_POST, true));
  if (isset($_FILES['imagen'])) {
    error_log("Archivo recibido: " . print_r($_FILES['imagen'], true));
  }
}

// Función para depurar subida de archivos
function debug_file_upload($file)
{
  $upload_errors = array(
    UPLOAD_ERR_OK => 'No hay error, el archivo se subió con éxito',
    UPLOAD_ERR_INI_SIZE => 'El archivo subido excede la directiva upload_max_filesize en php.ini',
    UPLOAD_ERR_FORM_SIZE => 'El archivo subido excede la directiva MAX_FILE_SIZE especificada en el formulario HTML',
    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
    UPLOAD_ERR_NO_TMP_DIR => 'Falta una carpeta temporal',
    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
    UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
  );

  if (isset($file['error'])) {
    $error_code = $file['error'];
    $error_message = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'Error desconocido';
    error_log("Error de subida: " . $error_message);
  }
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

// Función para extraer URLs de imágenes del contenido HTML
function extractImageUrls($html)
{
  $pattern = '/<img[^>]+src="([^"]+)"/i';
  preg_match_all($pattern, $html, $matches);
  return $matches[1] ?? [];
}

// Función para obtener la ruta del sistema de archivos a partir de una URL
function getFilePathFromUrl($url, $baseDir)
{
  // Eliminar el directorio base y cualquier parámetro de consulta
  $url = explode('?', $url)[0];
  $relativePath = str_replace($baseDir . '/', '', $url);
  return '../' . $relativePath;
}

// Inicializar variables
$error = '';
$success = '';
$post = null;

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: posts.php?error=id_invalido");
  exit;
}

$id = intval($_GET['id']);

// Obtener datos del post
$stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar si el post existe
if ($resultado->num_rows === 0) {
  header("Location: posts.php?error=no_existe");
  exit;
}

$post = $resultado->fetch_assoc();

// Corregir formato de fecha si es inválido
if ($post['fecha_publicacion'] == '0000-00-00' || empty($post['fecha_publicacion'])) {
  $post['fecha_publicacion'] = date('Y-m-d');
} else {
  // Convertir la fecha al formato correcto para el input type="date"
  $timestamp = strtotime($post['fecha_publicacion']);
  if ($timestamp !== false) {
    $post['fecha_publicacion'] = date('Y-m-d', $timestamp);
  } else {
    $post['fecha_publicacion'] = date('Y-m-d');
  }
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validar y sanitizar datos
  $titulo = trim($_POST['titulo'] ?? '');
  $contenido_nuevo = $_POST['contenido'] ?? '';
  $categoria = $_POST['categoria'] ?? 'noticia';
  $visible = !empty($_POST['visible']) ? 1 : 0;
  $orden = intval($_POST['orden'] ?? 0);
  $fecha = !empty($_POST['fecha_publicacion']) ? $_POST['fecha_publicacion'] : date('Y-m-d');

  // Validación básica
  if (empty($titulo)) {
    $error = "El título es obligatorio";
  } else {
    // Asegurarse de que exista el directorio para este post
    $post_dir = "../uploads/posts/{$id}";
    if (!is_dir($post_dir)) {
      mkdir($post_dir, 0755, true);
    }

    // Mantener la imagen actual por defecto
    $imagen = $post['imagen'];

    // Procesar nueva imagen si se subió
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
      // Depurar información de subida
      debug_file_upload($_FILES['imagen']);

      $nombre_tmp = $_FILES['imagen']['tmp_name'];

      // Siempre usar extensión WebP
      $nombre_final = "uploads/posts/{$id}/portada.webp";

      // Verificar tipo de archivo usando mime_content_type en lugar de confiar en $_FILES
      $file_type = '';
      if (function_exists('mime_content_type')) {
        $file_type = mime_content_type($nombre_tmp);
      } else {
        $file_type = $_FILES['imagen']['type'];
      }

      $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

      if (!in_array($file_type, $allowed_types)) {
        $error = "Solo se permiten imágenes (JPG, PNG, GIF, WEBP). Tipo detectado: " . $file_type;
        error_log("Tipo de archivo no permitido: " . $file_type);
      } else {
        // Verificar si la función de conversión a WebP está disponible
        if (!function_exists('imagewebp')) {
          error_log("La función imagewebp no está disponible. Asegúrate de tener instalada la extensión GD con soporte WebP.");
          $error = "El servidor no tiene soporte para WebP. Contacta al administrador.";
        } else {
          // Convertir la imagen a WebP y guardarla
          if (convertToWebP($nombre_tmp, '../' . $nombre_final)) {
            $imagen = $nombre_final;
            error_log("Imagen convertida y guardada exitosamente como WebP: " . $nombre_final);
          } else {
            // Si la conversión falla, intentar el método tradicional pero forzando extensión WebP
            error_log("La conversión a WebP falló, intentando método tradicional");
            if (move_uploaded_file($nombre_tmp, '../' . $nombre_final)) {
              $imagen = $nombre_final;
              error_log("Imagen subida exitosamente (sin conversión): " . $nombre_final);
            } else {
              $error = "Error al subir la imagen. Verifica los permisos de la carpeta uploads.";
              error_log("Error al subir imagen. Último error: " . print_r(error_get_last(), true));
            }
          }
        }
      }
    }

    // Procesar el contenido para buscar imágenes temporales y moverlas al directorio del post
    $contenido_actualizado = $contenido_nuevo;

    // Buscar imágenes en el contenido nuevo
    $nuevas_imagenes = extractImageUrls($contenido_nuevo);
    $nuevas_imagenes_paths = [];

    // Procesar imágenes temporales en el contenido nuevo
    foreach ($nuevas_imagenes as $img_src) {
      // Verificar si es una imagen temporal (subida durante la edición)
      if (strpos($img_src, 'uploads/temp/') !== false) {
        // Obtener la ruta del archivo original
        $img_path = getFilePathFromUrl($img_src, SiteConfig::getBaseDir());

        if (file_exists($img_path)) {
          // Crear un nuevo nombre para la imagen
          $img_basename = basename($img_path);
          $new_img_path = "uploads/posts/{$id}/{$img_basename}";

          // Mover la imagen al directorio del post
          if (copy($img_path, '../' . $new_img_path)) {
            // Actualizar la URL en el contenido
            $new_img_url = SiteConfig::getBaseDir() . '/' . $new_img_path;
            $contenido_actualizado = str_replace($img_src, $new_img_url, $contenido_actualizado);

            // Eliminar el archivo original
            unlink($img_path);

            // Agregar a la lista de nuevas imágenes
            $nuevas_imagenes_paths[] = '../' . $new_img_path;
          }
        }
      } else if (strpos($img_src, "uploads/posts/{$id}/") !== false) {
        // Es una imagen existente del post, guardar su ruta
        $nuevas_imagenes_paths[] = getFilePathFromUrl($img_src, SiteConfig::getBaseDir());
      }
    }

    // Buscar imágenes en el contenido anterior para detectar eliminaciones
    $viejas_imagenes = extractImageUrls($post['contenido']);

    // Verificar qué imágenes se eliminaron
    foreach ($viejas_imagenes as $img_src) {
      if (strpos($img_src, "uploads/posts/{$id}/") !== false) {
        $img_path = getFilePathFromUrl($img_src, SiteConfig::getBaseDir());

        // Si la imagen ya no está en el contenido nuevo y existe en el sistema de archivos
        if (!in_array($img_path, $nuevas_imagenes_paths) && file_exists($img_path) && $img_path != '../' . $imagen) {
          // Eliminar la imagen del sistema de archivos
          unlink($img_path);
          error_log("Imagen eliminada del sistema de archivos: " . $img_path);
        }
      }
    }

    if (empty($error)) {
      // Actualizar el post con la imagen y el contenido actualizado
      $stmt = $db->prepare("UPDATE posts SET titulo = ?, contenido = ?, imagen = ?, categoria = ?, visible = ?, orden = ?, fecha_publicacion = ? WHERE id = ?");
      $stmt->bind_param("ssssiisi", $titulo, $contenido_actualizado, $imagen, $categoria, $visible, $orden, $fecha, $id);

      if ($stmt->execute()) {
        $success = "Publicación actualizada correctamente";

        // Limpiar todos los buffers de salida
        while (ob_get_level()) {
          ob_end_clean();
        }

        // Redirigir a la lista de posts
        header("Location: posts.php?update=ok");
        exit;
      } else {
        $error = "Error al actualizar: " . $db->error;
        error_log("Error SQL: " . $db->error);
      }
    }
  }
}

// Ahora que hemos procesado la lógica, incluimos el encabezado
include_once 'includes/head.php';
?>

<!-- SweetAlert2 CSS y JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<?php if (!empty($error)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: 'error',
        title: '¡Error!',
        text: '<?= addslashes($error) ?>',
        confirmButtonColor: '#dc3545'
      });
    });
  </script>
<?php endif; ?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">Editar Publicación <i class="bi bi-pencil-square"></i><i class="bi bi-file-earmark-text"></i></h1>
    <nav aria-label="breadcrumb">
      <ol class="admin-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
        <li class="breadcrumb-item"><a href="posts.php">Publicaciones</a></li>
        <li class="breadcrumb-item active" aria-current="page">Editar publicación</li>
      </ol>
    </nav>
  </div>
  <a href="posts.php" class="btn btn-admin-secondary">
    <i class="bi bi-arrow-left me-1"></i> Volver a la lista
  </a>
</div>

<!-- Formulario de publicación -->
<div class="admin-card">
  <div class="admin-card-header">
    <h5 class="admin-card-title">
      <i class="bi bi-file-earmark-text me-2"></i>
      Datos de la publicación
    </h5>
  </div>
  <div class="admin-card-body">
    <form action="editar-post.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" id="formEditarPost">
      <div class="row">
        <div class="col-md-8">
          <!-- Información principal -->
          <div class="mb-4">
            <label class="form-label fw-medium">Título <span class="text-danger">*</span></label>
            <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($post['titulo']) ?>" required>
            <div class="form-text">El título debe ser descriptivo y conciso.</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-medium">Contenido <span class="text-danger">*</span></label>
            <textarea id="contenido" name="contenido" rows="10" class="form-control"><?= htmlspecialchars($post['contenido']) ?></textarea>
          </div>
        </div>

        <div class="col-md-4">
          <!-- Panel lateral -->
          <div class="admin-card mb-4">
            <div class="admin-card-header">
              <h6 class="admin-card-title mb-0">Configuración</h6>
            </div>
            <div class="admin-card-body">
              <div class="mb-3">
                <label class="form-label fw-medium">Categoría</label>
                <select name="categoria" class="form-select">
                  <option value="noticia" <?= $post['categoria'] == 'noticia' ? 'selected' : '' ?>>Noticia</option>
                  <option value="evento" <?= $post['categoria'] == 'evento' ? 'selected' : '' ?>>Evento</option>
                  <option value="curso" <?= $post['categoria'] == 'curso' ? 'selected' : '' ?>>Curso</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-medium">Fecha de publicación</label>
                <input type="date" name="fecha_publicacion" value="<?= $post['fecha_publicacion'] ?>" class="form-control">
              </div>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="visible" id="visible" <?= $post['visible'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="visible">Publicación visible</label>
              </div>
            </div>
          </div>

          <!-- Imagen destacada -->
          <div class="admin-card mb-4">
            <div class="admin-card-header">
              <h6 class="admin-card-title mb-0">Imagen destacada</h6>
            </div>
            <div class="admin-card-body">
              <?php if (!empty($post['imagen'])): ?>
                <div class="mb-3 text-center">
                  <img src="../<?= htmlspecialchars($post['imagen']) ?>" alt="Imagen actual" class="img-fluid img-thumbnail" style="max-height: 200px;">
                  <p class="form-text mt-2">Imagen actual: <?= htmlspecialchars($post['imagen']) ?></p>
                </div>
              <?php endif; ?>
              <div class="mb-3">
                <input type="file" name="imagen" class="form-control" id="imageInput" accept="image/*">
                <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WEBP. Se convertirán automáticamente a WebP.</div>
              </div>
              <div id="imagePreview" class="mt-3 text-center d-none">
                <img src="#" alt="Vista previa" class="img-fluid img-thumbnail" style="max-height: 200px;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="border-top pt-4 mt-4 d-flex justify-content-end">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-admin-success" id="btnGuardar">
            <i class="bi bi-save me-1"></i> Guardar cambios
          </button>
          <a href="posts.php" class="btn btn-admin-secondary">Cancelar</a>
        </div>
      </div>
    </form>
  </div>
</div>

<!--<script src="https://cdn.tiny.cloud/1/1qeus874swfh4awwzgk7ybxmvaoilseiz0xvmxu9nmql0sl9/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>-->
<!-- TinyMCE Self-Hosted -->
<script src="../assets/vendor/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  // Cargar la configuración del sitio
  var siteConfig = <?php echo SiteConfig::getJsConfig(); ?>;

  // Inicializar TinyMCE con configuración adaptativa
  tinymce.init({
    selector: '#contenido',
    apiKey: siteConfig.tinyMCE.apiKey,
    plugins: 'link image lists media table code',
    toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media | code',
    menubar: false,
    height: 500,
    automatic_uploads: true,
    images_upload_credentials: true,
    skin: 'oxide',
    content_css: 'default',

    // Usar configuración adaptada al entorno
    convert_urls: siteConfig.tinyMCE.convert_urls,
    relative_urls: siteConfig.tinyMCE.relative_urls,
    remove_script_host: siteConfig.tinyMCE.remove_script_host,
    document_base_url: siteConfig.tinyMCE.document_base_url,

    images_upload_url: siteConfig.uploadUrl,
    images_upload_handler: function(blobInfo, progress) {
      return new Promise(function(resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', siteConfig.uploadUrl);

        xhr.upload.onprogress = function(e) {
          progress(e.loaded / e.total * 100);
        };

        xhr.onload = function() {
          if (xhr.status !== 200) {
            console.error('HTTP Error:', xhr.status, xhr.responseText);
            reject('HTTP Error: ' + xhr.status);
            return;
          }

          try {
            var json = JSON.parse(xhr.responseText);
            if (!json || typeof json.location != 'string') {
              console.error('Invalid JSON:', xhr.responseText);
              reject('Invalid JSON: ' + xhr.responseText);
              return;
            }

            console.log('Image URL:', json.location);
            resolve(json.location);
          } catch (e) {
            console.error('Error parsing JSON:', e, xhr.responseText);
            reject('Invalid JSON: ' + xhr.responseText);
          }
        };

        xhr.onerror = function() {
          console.error('Image upload failed due to a network error.');
          reject('Image upload failed due to a network error.');
        };

        var formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        // Añadir el ID del post para guardar en el directorio correcto
        formData.append('post_id', <?= $id ?>);
        // Añadir un campo para indicar que es una imagen de TinyMCE
        formData.append('source', 'tinymce');
        xhr.send(formData);
      });
    },
    paste_data_images: true,
    image_advtab: true,
    image_caption: true,
    image_dimensions: false,
    file_picker_types: 'image',

    // Configuración para depuración y corrección de URLs
    init_instance_callback: function(editor) {
      console.log('Editor inicializado. Entorno local:', siteConfig.isLocal);
      console.log('Base URL:', siteConfig.baseUrl);
      console.log('Base Dir:', siteConfig.baseDir);

      editor.on('Error', function(e) {
        console.error('TinyMCE error:', e);
      });
    },

    // Evento para asegurar que los cambios se guarden
    setup: function(editor) {
      editor.on('change', function() {
        editor.save(); // Guardar cambios al textarea
      });
    }
  });

  // Vista previa de imagen
  document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      const preview = document.getElementById('imagePreview');
      const previewImg = preview.querySelector('img');

      reader.onload = function(e) {
        previewImg.src = e.target.result;
        preview.classList.remove('d-none');
      }

      reader.readAsDataURL(file);
    }
  });

  // Mostrar confirmación al guardar
  document.getElementById('formEditarPost').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
      title: '¿Guardar cambios?',
      text: 'Se actualizará la publicación con los datos ingresados',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar indicador de carga
        Swal.fire({
          title: 'Guardando...',
          html: 'Por favor espera mientras se guardan los cambios',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Enviar el formulario
        this.submit();
      }
    });
  });
</script>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>
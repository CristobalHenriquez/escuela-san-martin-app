<?php
// Configuración de la página
$admin_page_title = 'Crear Nueva Publicación | ALPA Servicios Ambientales';
$admin_page_description = 'Formulario para crear una nueva publicación en el sitio web';

require_once '../includes/conexion.php';
require_once 'config.php';

// Iniciar sesión y token CSRF
if (!isset($_SESSION)) {
  session_start();
}

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Verificar si el directorio de uploads existe, si no, crearlo
if (!is_dir('../uploads')) {
  mkdir('../uploads', 0755, true);
}

if (!is_dir('../uploads/posts')) {
  mkdir('../uploads/posts', 0755, true);
}

if (!is_dir('../uploads/temp')) {
  mkdir('../uploads/temp', 0755, true);
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
$titulo = '';
$contenido = '';
$categoria = 'noticia';
$post_id = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Validar y sanitizar datos
  $titulo = trim($_POST['titulo'] ?? '');
  $contenido = $_POST['contenido'] ?? '';
  $categoria = $_POST['categoria'] ?? 'noticia';
  $visible = isset($_POST['visible']) ? 1 : 0;
  $orden = intval($_POST['orden'] ?? 0);
  $fecha = !empty($_POST['fecha_publicacion']) ? $_POST['fecha_publicacion'] : date('Y-m-d');

  // Validación básica
  if (empty($titulo)) {
    $error = "El título es obligatorio";
  } else {
    // Primero insertamos el post sin imagen para obtener el ID
    $stmt = $db->prepare("INSERT INTO posts (titulo, contenido, imagen, categoria, visible, orden, fecha_publicacion) VALUES (?, ?, '', ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $titulo, $contenido, $categoria, $visible, $orden, $fecha);

    if ($stmt->execute()) {
      // Obtener el ID del post recién insertado
      $post_id = $db->insert_id;

      // Crear directorio específico para este post
      $post_dir = "../uploads/posts/{$post_id}";
      if (!is_dir($post_dir)) {
        mkdir($post_dir, 0755, true);
      }

      // Manejo de imagen principal
      $imagen = '';
      if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $nombre_tmp = $_FILES['imagen']['tmp_name'];

        // Siempre usar extensión WebP
        $nombre_final = "uploads/posts/{$post_id}/portada.webp";

        // Verificar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($nombre_tmp);

        if (!in_array($file_type, $allowed_types)) {
          $error = "Solo se permiten imágenes (JPG, PNG, GIF, WEBP)";
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
      $contenido_actualizado = $contenido;

      // Buscar imágenes en el contenido usando expresiones regulares
      $imagenes = extractImageUrls($contenido);

      if (!empty($imagenes)) {
        foreach ($imagenes as $img_src) {
          // Verificar si es una imagen temporal (subida durante la edición)
          if (strpos($img_src, 'uploads/temp/') !== false) {
            // Obtener la ruta del archivo original
            $img_path = getFilePathFromUrl($img_src, SiteConfig::getBaseDir());

            if (file_exists($img_path)) {
              // Crear un nuevo nombre para la imagen
              $img_basename = basename($img_path);
              $new_img_path = "uploads/posts/{$post_id}/{$img_basename}";

              // Mover la imagen al directorio del post
              if (copy($img_path, '../' . $new_img_path)) {
                // Actualizar la URL en el contenido
                $new_img_url = SiteConfig::getBaseDir() . '/' . $new_img_path;
                $contenido_actualizado = str_replace($img_src, $new_img_url, $contenido_actualizado);

                // Eliminar el archivo original
                unlink($img_path);
              }
            }
          }
        }
      }

      // Actualizar el post con la imagen y el contenido actualizado
      $stmt = $db->prepare("UPDATE posts SET imagen = ?, contenido = ? WHERE id = ?");
      $stmt->bind_param("ssi", $imagen, $contenido_actualizado, $post_id);

      if ($stmt->execute()) {
        $success = "Publicación creada correctamente";
        
        // Limpiar todos los buffers de salida
        while (ob_get_level()) {
          ob_end_clean();
        }
        
        // Redirigir con mensaje de éxito
        header("Location: posts.php?success=1");
        exit;
      } else {
        $error = "Error al actualizar: " . $stmt->error;
      }
    } else {
      $error = "Error al guardar: " . $stmt->error;
    }
  }
}

// Incluimos el encabezado
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

<?php if (!empty($success)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: '<?= addslashes($success) ?>',
      confirmButtonColor: '#28a745',
      timer: 3000,
      timerProgressBar: true
    }).then(() => {
      window.location.href = 'posts.php?success=1';
    });
  });
</script>
<?php endif; ?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">Nueva Publicación <i class="bi bi-plus-circle"></i><i class="bi bi-file-earmark-text"></i></h1>
    <nav aria-label="breadcrumb">
      <ol class="admin-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><a href="admin.php">Panel</a></li>
        <li class="breadcrumb-item"><a href="posts.php">Publicaciones</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nueva publicación</li>
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
      <i class="bi bi-file-earmark-plus me-2"></i>
      Datos de la publicación
    </h5>
  </div>
  <div class="admin-card-body">
    <form action="controllers/cargar-post.php" method="POST" enctype="multipart/form-data" id="formCrearPost">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <div class="row">
        <div class="col-md-8">
          <!-- Información principal -->
          <div class="mb-4">
            <label class="form-label fw-medium">Título <span class="text-danger">*</span></label>
            <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($titulo) ?>" required>
            <div class="form-text">El título debe ser descriptivo y conciso.</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-medium">Contenido <span class="text-danger">*</span></label>
            <!-- Editor Quill -->
            <div id="contenido" style="height: 300px;"><?= $contenido ?></div>
            <!-- Campo oculto para enviar el contenido -->
            <textarea id="contenido_hidden" name="contenido" style="display: none;"><?= htmlspecialchars($contenido) ?></textarea>
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
                  <option value="noticia" <?= $categoria == 'noticia' ? 'selected' : '' ?>>Noticia</option>
                  <option value="evento" <?= $categoria == 'evento' ? 'selected' : '' ?>>Evento</option>
                  <option value="curso" <?= $categoria == 'curso' ? 'selected' : '' ?>>Curso</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-medium">Fecha de publicación</label>
                <input type="date" name="fecha_publicacion" value="<?= date('Y-m-d'); ?>" class="form-control">
              </div>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="visible" id="visible" checked>
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
            <i class="bi bi-save me-1"></i> Guardar publicación
          </button>
          <a href="posts.php" class="btn btn-admin-secondary">Cancelar</a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Quill Editor (alternativa sin API key) -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Quill Editor
    var quill = new Quill('#contenido', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ]
        },
        placeholder: 'Escribe aquí el contenido del post...'
    });

    // Sincronizar contenido con el textarea oculto al enviar el formulario
    document.querySelector('form').addEventListener('submit', function() {
        var contenidoTextarea = document.querySelector('#contenido_hidden');
        if (contenidoTextarea) {
            contenidoTextarea.value = quill.root.innerHTML;
        }
    });
});
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
        // Añadir un campo para indicar que es una imagen de TinyMCE
        formData.append('source', 'tinymce');
        xhr.send(formData);
      });
    },
    paste_data_images: true,
    image_advtab: true,
    image_caption: true,
    image_dimensions: true,
    file_picker_types: 'image',

    // Configuración para depuración y corrección de URLs
    init_instance_callback: function(editor) {
      console.log('Editor inicializado. Entorno local:', siteConfig.isLocal);
      console.log('Base URL:', siteConfig.baseUrl);
      console.log('Base Dir:', siteConfig.baseDir);

      editor.on('Error', function(e) {
        console.error('TinyMCE error:', e);
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
  document.getElementById('formCrearPost').addEventListener('submit', function(e) {
    e.preventDefault();
    
    Swal.fire({
      title: '¿Crear publicación?',
      text: 'Se creará una nueva publicación con los datos ingresados',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, crear',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar indicador de carga
        Swal.fire({
          title: 'Guardando...',
          html: 'Por favor espera mientras se crea la publicación',
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
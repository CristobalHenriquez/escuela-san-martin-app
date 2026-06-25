<?php
// reset_password_local.php
// Uso local para resetear la contraseña de un usuario.
// Seguridad básica: solo accesible desde localhost/CLI. Eliminar después de usar.

$isCli = php_sapi_name() === 'cli';
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!$isCli && !in_array($remote, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Acceso denegado. Este script solo puede ejecutarse en localhost.');
}

require __DIR__ . '/db.php';

function html($s){return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');}

$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $isCli) {
    // Soporte CLI: php reset_password_local.php email nuevo_password
    if ($isCli) {
        $email = $argv[1] ?? '';
        $nuevo = $argv[2] ?? '';
        $id = '';
    } else {
        $email = trim($_POST['email'] ?? '');
        $id = trim($_POST['id'] ?? '');
        $nuevo = $_POST['password'] ?? '';
    }

    if (($email || $id) && $nuevo) {
        $hash = password_hash($nuevo, PASSWORD_DEFAULT);
        try {
            if ($id) {
                $stmt = $db->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
                $stmt->execute([$hash, $id]);
            } else {
                $stmt = $db->prepare('UPDATE usuarios SET password = ? WHERE email = ?');
                $stmt->execute([$hash, $email]);
            }
            if ($stmt->rowCount() > 0) {
                $info = 'Contraseña actualizada correctamente.';
            } else {
                $error = 'No se encontró el usuario o no hubo cambios.';
            }
        } catch (Throwable $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    } else {
        $error = 'Completa email o id y la nueva contraseña.';
    }
}

if ($isCli) {
    if ($error) {fwrite(STDERR, $error . PHP_EOL);} else {fwrite(STDOUT, $info . PHP_EOL);} 
    exit($error ? 1 : 0);
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Resetear contraseña (local)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:520px; margin-top:40px;">
  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Resetear contraseña (solo local)</div>
    <div class="card-body">
      <?php if ($info): ?><div class="alert alert-success"><?php echo html($info); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo html($error); ?></div><?php endif; ?>
      <form method="post">
        <div class="mb-2">
          <label class="form-label">Email (o deja vacío y usa ID)</label>
          <input type="email" name="email" class="form-control" placeholder="usuario@correo.com">
        </div>
        <div class="mb-2">
          <label class="form-label">ID de usuario (alternativa al email)</label>
          <input type="number" name="id" class="form-control" placeholder="1">
        </div>
        <div class="mb-3">
          <label class="form-label">Nueva contraseña</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Actualizar contraseña</button>
      </form>
      <p class="mt-3 small text-muted">Por seguridad, elimina este archivo cuando termines: <code>reset_password_local.php</code></p>
    </div>
  </div>
</div>
</body>
</html>

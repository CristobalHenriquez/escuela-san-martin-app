<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (secretaria_is_authenticated()) {
    header('Location: panel.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    if (password_verify($password, secretaria_password_hash())) {
        session_regenerate_id(true);
        $_SESSION[SECRETARIA_SESSION_KEY] = true;
        $_SESSION['secretaria_login_at'] = time();
        header('Location: panel.php');
        exit;
    }
    $error = 'La contraseña ingresada no es válida.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secretaría | EESO 225</title>
    <link rel="icon" href="../assets/images/logo/favicon.svg" type="image/svg+xml">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/secretaria.css" rel="stylesheet">
</head>
<body class="secretaria-page">
    <main class="access-shell">
        <section class="access-card" aria-labelledby="access-title">
            <div class="access-brand">
                <img src="../assets/images/logo/logo-escuela-icono.svg" alt="Identidad de EESO 225 San Martín">
                <div>
                    <span class="eyebrow">Área institucional</span>
                    <h1 id="access-title">Secretaría</h1>
                    <p>Generación de documentación escolar</p>
                </div>
            </div>
            <div class="access-divider"></div>
            <p class="access-intro">Ingrese la contraseña habilitada para acceder a los formularios de Secretaría.</p>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><i class="bi bi-shield-exclamation me-2"></i><?= secretaria_h($error) ?></div>
            <?php endif; ?>
            <form method="post" class="access-form">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required autofocus>
                </div>
                <button class="btn btn-secretaria btn-lg w-100 mt-4" type="submit"><i class="bi bi-box-arrow-in-right me-2"></i>Ingresar a Secretaría</button>
            </form>
            <p class="access-footnote"><i class="bi bi-info-circle me-1"></i>Acceso reservado para uso institucional.</p>
        </section>
    </main>
</body>
</html>

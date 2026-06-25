<?php
session_start();
require 'db.php';

// Si ya está logueado, redirigir al index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$emailValue = '';
if (!empty($_SESSION['login_notice'])) {
    $error = (string) $_SESSION['login_notice'];
    unset($_SESSION['login_notice']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $emailValue = $email;
    $password = $_POST['password'] ?? '';
    $stmt = $db->prepare("SELECT u.*, g.nombre AS grupo_nombre, g.activo AS grupo_activo
        FROM usuarios u
        LEFT JOIN grupos g ON g.id = u.grupo_id
        WHERE u.email = ?
        LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        if (isset($user['activo']) && $user['activo'] == 0) {
            $error = 'Usuario deshabilitado. Consulte al administrador.';
        } else {
            // Login OK
            try {
                $sessionToken = bin2hex(random_bytes(32));
            } catch (Throwable $e) {
                $sessionToken = hash('sha256', uniqid('sess_', true) . microtime(true));
            }
            $stmtToken = $db->prepare("UPDATE usuarios SET session_token = ?, session_token_created_at = NOW() WHERE id = ?");
            $stmtToken->execute([$sessionToken, (int) $user['id']]);

            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_apellido'] = $user['apellido'];
            $_SESSION['usuario_email'] = $user['email'];
            $_SESSION['usuario_admin'] = $user['es_admin'];
            $_SESSION['usuario_super_admin'] = (int) ($user['super_admin'] ?? 0);
            $_SESSION['usuario_token'] = $sessionToken;
            $_SESSION['usuario_grupo_id'] = (!empty($user['grupo_id']) && (!isset($user['grupo_activo']) || (int) $user['grupo_activo'] === 1))
                ? (int) $user['grupo_id']
                : null;
            $_SESSION['usuario_grupo_nombre'] = (!empty($user['grupo_nombre']) && (!isset($user['grupo_activo']) || (int) $user['grupo_activo'] === 1))
                ? (string) $user['grupo_nombre']
                : null;
            header('Location: index.php');
            exit;
        }
    } else {
        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | POS La San Martin 5°C</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    html,
    body {
        height: 100%;
    }

    body {
        min-height: 100vh;
        height: 100%;
        background:
            radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.18), transparent 45%),
            linear-gradient(135deg, #0f4c75 0%, #1b8a9b 60%, #2f9e44 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-outer {
        min-height: 100vh;
        width: 100vw;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 10px 32px rgba(0, 0, 0, 0.18);
        padding: 2.2rem 1.5rem 1.5rem 1.5rem;
        max-width: 390px;
        width: 100%;
        margin: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .login-logo {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin-bottom: 1.2rem;
    }

    .login-logo img {
        width: 120px;
        height: auto;
        object-fit: contain;
        margin-bottom: 0.7rem;
        border-radius: 0;
        box-shadow: none;
        display: block;
    }

    .login-logo i {
        font-size: 2.2rem;
        color: #0f4c75;
        margin-bottom: 0.5rem;
    }

    .login-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        text-align: center;
    }

    .login-subtitle {
        font-size: 0.95rem;
        color: #6b7280;
        margin-bottom: 1rem;
        text-align: center;
    }

    .form-control {
        font-size: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 10px;
        border: 1px solid #d1d5db;
    }

    .form-control:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.2);
    }

    .btn-login {
        width: 100%;
        padding: 0.8rem;
        font-size: 1.1rem;
        border-radius: 10px;
        background: linear-gradient(135deg, #0f766e 0%, #0f4c75 100%);
        color: #fff;
        font-weight: 600;
        border: none;
        margin-top: 0.5rem;
    }

    .btn-login:active,
    .btn-login:focus {
        background: #0f4c75;
    }

    .error-msg {
        margin-bottom: 1rem;
    }

    @media (max-width: 480px) {
        .login-container {
            padding: 1.2rem 0.5rem 1.2rem 0.5rem;
            max-width: 98vw;
        }
    }
    </style>
</head>

<body>
    <div class="login-outer">
        <div class="login-container">
            <div class="login-logo">
                <img src="img/lsm.jpeg" alt="Logo Escuela">
                <i class="fas fa-store"></i>
            </div>
            <div class="login-title">POS La San Martin 5°C</div>
            <div class="login-subtitle">Ingresa con tu usuario para abrir turno y registrar ventas.</div>
            <?php if ($error): ?>
            <div class="alert alert-danger error-msg py-2 px-3" role="alert" aria-live="polite">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            <form method="post" autocomplete="off" style="width:100%;" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus
                        placeholder="Email" value="<?= htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required
                        placeholder="Contraseña">
                </div>
                <button type="submit" class="btn btn-login" id="loginButton">Iniciar sesión</button>
            </form>
            <div class="text-center mt-3" style="font-size:0.95rem;color:#888;">
                ¿Problemas para ingresar? <br>Email: <a href="mailto:cristobalhb@live.com">cristobalhb@live.com</a>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginButton');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Ingresando...';
    });
    </script>
</body>

</html>

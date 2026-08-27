<?php
// Página de Login - EESO 225 "La San Martín"
require_once '../includes/conexion.php';
require_once '../includes/auth.php';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Por favor, complete todos los campos.';
    } else {
        // Buscar usuario en la base de datos
        $stmt = $db->prepare("SELECT id, nombreyapellido, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($password, $usuario['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombreyapellido'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['es_admin'] = true; // Todos los usuarios del admin son admin
                
                // Log de actividad
                logActividad('Login exitoso', "Usuario: {$usuario['email']}");
                
                // Redirigir al dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = 'Credenciales incorrectas.';
                logActividad('Intento de login fallido', "Email: {$email}");
            }
        } else {
            $error_message = 'Credenciales incorrectas.';
            logActividad('Intento de login fallido', "Email: {$email}");
        }
    }
}

// Mostrar mensajes de sesión
if (isset($_GET['expired']) && $_GET['expired'] == '1') {
    $error_message = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
}

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success_message = 'Ha cerrado sesión correctamente.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | EESO 225 "La San Martín"</title>
    
    <!-- Favicon -->
    <link href="../assets/images/logo/logo-escuela.jpg" rel="icon">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS (local) -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --color-violeta: #6A1B9A;
            --color-gris: #616161;
            --color-blanco: #FFFFFF;
            --color-violeta-claro: #8E24AA;
            --color-gris-claro: #9E9E9E;
            --color-gris-muy-claro: #F5F5F5;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: #f8f9fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(135deg, var(--color-violeta) 0%, var(--color-violeta-claro) 100%);
            z-index: 0;
        }
        
        .login-container {
            background: var(--color-blanco);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--color-violeta) 0%, var(--color-violeta-claro) 100%);
            color: var(--color-blanco);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            text-align: left;
            position: relative;
        }
        
        .login-left::after {
            content: '';
            position: absolute;
            right: -1px;
            top: 0;
            bottom: 0;
            width: 1px;
            background: rgba(255,255,255,0.1);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .login-left img {
            max-height: 100px;
            max-width: 100px;
            object-fit: contain;
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .school-name {
            flex: 1;
        }
        
        .school-name h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 5px;
            line-height: 1.2;
            color: var(--color-blanco);
        }
        
        .school-name h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            opacity: 0.95;
        }
        
        .school-title {
            font-size: 0.95rem;
            font-weight: 500;
            margin: 25px 0;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            border-left: 4px solid var(--color-blanco);
            line-height: 1.6;
        }
        
        .login-right {
            padding: 60px 50px;
            background: var(--color-blanco);
        }
        
        .login-form h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--color-violeta);
            margin-bottom: 10px;
            font-size: 1.75rem;
        }
        
        .login-form .subtitle {
            color: var(--color-gris);
            margin-bottom: 35px;
            font-size: 0.95rem;
        }
        
        .form-floating {
            margin-bottom: 20px;
            position: relative;
        }

        .password-toggle {
            position: absolute;
            z-index: 3;
            top: 50%;
            right: 0.75rem;
            display: grid;
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            place-items: center;
            color: var(--color-violeta);
            background: transparent;
            border: 0;
            border-radius: 50%;
            transform: translateY(-50%);
        }

        .password-toggle:hover,
        .password-toggle:focus-visible {
            color: #4A126E;
            background: rgba(106, 27, 154, 0.09);
            outline: none;
        }

        .password-toggle:focus-visible {
            box-shadow: 0 0 0 3px rgba(106, 27, 154, 0.2);
        }
        
        .form-floating .form-control {
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            padding: 20px 15px 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--color-violeta);
            box-shadow: 0 0 0 0.2rem rgba(106, 27, 154, 0.15);
            background: var(--color-blanco);
        }
        
        .form-floating label {
            color: var(--color-gris-claro);
            font-weight: 500;
            padding-left: 15px;
        }
        
        .btn-login {
            background: var(--color-violeta);
            border: none;
            color: var(--color-blanco);
            font-weight: 600;
            padding: 16px 30px;
            border-radius: 10px;
            font-size: 1.05rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(106, 27, 154, 0.25);
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background: var(--color-violeta-claro);
            color: var(--color-blanco);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(106, 27, 154, 0.35);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            font-weight: 500;
            margin-bottom: 25px;
            padding: 15px 20px;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #c53030;
            border-left: 4px solid #c53030;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
        
        .school-info {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 0.9rem;
        }
        
        .school-info p {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            opacity: 0.9;
        }
        
        .school-info i {
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .login-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e8e8e8;
            text-align: center;
        }
        
        .login-footer small {
            color: var(--color-gris-claro);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        @media (max-width: 991px) {
            .login-left::after {
                display: none;
            }
            
            .logo-container {
                justify-content: center;
                text-align: center;
            }
            
            .login-left {
                align-items: center;
                text-align: center;
                padding: 50px 30px;
            }
            
            .school-name h1 {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .login-right {
                padding: 40px 30px;
            }
            
            .logo-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .login-left img {
                max-height: 80px;
                max-width: 80px;
            }
            
            .school-name h1 {
                font-size: 1.5rem;
            }
            
            .school-name h2 {
                font-size: 0.95rem;
            }
            
            .login-form h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="row g-0">
            <!-- Lado izquierdo con información de la escuela -->
            <div class="col-lg-5 login-left">
                <div class="logo-container">
                    <img src="../assets/images/logo/logo-escuela.jpg" alt="EESO 225 La San Martín">
                    <div class="school-name">
                        <h1>EESO 225</h1>
                        <h2>"La San Martín"</h2>
                    </div>
                </div>
                
                <div class="school-title">
                    <strong>E.E.S. ORIENTADA NRO 225<br>
                    GENERAL JOSÉ DE SAN MARTÍN</strong>
                </div>
                
                <div class="school-info">
                    <p>
                        <i class="bi bi-geo-alt"></i>
                        <span>Sarmiento 949, Pérez, Santa Fe</span>
                    </p>
                    <p>
                        <i class="bi bi-telephone"></i>
                        <span>3414951255 - 3413547139</span>
                    </p>
                    <p>
                        <i class="bi bi-envelope"></i>
                        <span>sec225_perez@santafe.edu.ar</span>
                    </p>
                </div>
            </div>
            
            <!-- Lado derecho con formulario de login -->
            <div class="col-lg-7 login-right">
                <div class="login-form">
                    <h3><i class="bi bi-shield-lock-fill me-2"></i>Panel de Administración</h3>
                    <p class="subtitle">Ingresá con tus credenciales para acceder al sistema</p>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($email ?? '') ?>">
                            <label for="email"><i class="bi bi-envelope-fill me-2"></i>Correo electrónico</label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="password" class="form-control pe-5" id="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
                            <label for="password"><i class="bi bi-lock-fill me-2"></i>Contraseña</label>
                            <button type="button" class="password-toggle" id="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar contraseña">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        
                        <button type="submit" class="btn btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="login-footer">
                        <small>
                            <i class="bi bi-shield-check"></i>
                            Acceso restringido al personal autorizado
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Mostrar mensajes con SweetAlert2 si es necesario
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('password-toggle');

            if (passwordInput && passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    const isVisible = passwordInput.type === 'text';
                    passwordInput.type = isVisible ? 'password' : 'text';
                    passwordToggle.setAttribute('aria-pressed', String(!isVisible));
                    passwordToggle.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                    passwordToggle.setAttribute('title', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                    passwordToggle.querySelector('i').className = isVisible ? 'bi bi-eye' : 'bi bi-eye-slash';
                });
            }

            <?php if (!empty($error_message)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error de acceso',
                    text: '<?= addslashes($error_message) ?>',
                    confirmButtonColor: '#6A1B9A'
                });
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Sesión cerrada',
                    text: '<?= addslashes($success_message) ?>',
                    confirmButtonColor: '#6A1B9A'
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>

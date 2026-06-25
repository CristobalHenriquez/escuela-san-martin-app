<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Usuarios - EESO 225</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="mb-4">Usuarios del Sistema</h1>
                
                <?php
                require_once __DIR__ . '/../config/config.php';
                require_once __DIR__ . '/../config/database.php';

                // Conectar a la base de datos
                $mysqli = get_mysqli();
                if (!$mysqli) {
                    echo '<div class="alert alert-danger">Error: No se pudo conectar a la base de datos.</div>';
                    exit;
                }

                echo '<div class="card">';
                echo '<div class="card-header"><h3>Usuarios Registrados</h3></div>';
                echo '<div class="card-body">';

                // Obtener usuarios
                $result = $mysqli->query("SELECT id, nombreyapellido, email, created_at FROM users ORDER BY id");

                if ($result && $result->num_rows > 0) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped">';
                    echo '<thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Creado</th></tr></thead>';
                    echo '<tbody>';
                    
                    while ($user = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['nombreyapellido']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                        echo '<td>' . date('d/m/Y H:i', strtotime($user['created_at'])) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-warning">No hay usuarios registrados en el sistema.</div>';
                    echo '<p>Puedes crear uno usando el script de administración.</p>';
                }

                echo '</div></div>';

                // Mostrar información de acceso por defecto
                echo '<div class="card mt-4">';
                echo '<div class="card-header"><h3>Datos de Acceso por Defecto</h3></div>';
                echo '<div class="card-body">';
                echo '<p>Si necesitas crear un usuario, los valores por defecto son:</p>';
                echo '<div class="alert alert-info">';
                echo '<strong>Email:</strong> admin@eeso225.edu.ar<br>';
                echo '<strong>Contraseña:</strong> Se genera automáticamente al ejecutar el script<br>';
                echo '<strong>URL de acceso:</strong> <a href="../admin/login.php">http://localhost/Proyecto/admin/login.php</a>';
                echo '</div>';
                echo '</div></div>';

                echo '<div class="mt-4">';
                echo '<a href="../admin/login.php" class="btn btn-primary">Ir al Login</a> ';
                echo '<a href="../admin/" class="btn btn-secondary">Panel Admin</a> ';
                echo '<a href="../index.php" class="btn btn-outline-primary">Sitio Web</a>';
                echo '</div>';

                $mysqli->close();
                ?>
                
            </div>
        </div>
    </div>
</body>
</html>
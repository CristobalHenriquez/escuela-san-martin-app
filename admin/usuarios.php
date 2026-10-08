<?php
// Gestión de Usuarios Administradores - EESO 225 San Martín
$admin_page_title = 'Gestión de Usuarios | EESO 225 San Martín';
$admin_page_description = 'Gestión de usuarios administradores del panel';

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

include_once 'includes/head.php';
include_once 'includes/sweetalert.php';
require_once '../includes/conexion.php';
require_once 'config.php';

// Verificar autenticación
verificarAutenticacion();

// Verificar si el usuario tiene acceso para crear usuarios
$tiene_acceso_crear = isset($_SESSION['admin_user_access']) && 
                     $_SESSION['admin_user_access_expiry'] > time();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verificar_clave'])) {
        $clave_ingresada = trim($_POST['clave_secreta']);
        
        if ($clave_ingresada === ADMIN_CREATE_USER_SECRET_KEY) {
            $_SESSION['admin_user_access'] = true;
            $_SESSION['admin_user_access_expiry'] = time() + ADMIN_CREATE_USER_SESSION_TIME;
            $mensaje_exito = "Acceso autorizado. Ahora puedes gestionar usuarios.";
        } else {
            $mensaje_error = "Clave incorrecta. Acceso denegado.";
        }
    } elseif (isset($_POST['crear_usuario']) && $tiene_acceso_crear) {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirmar_password = trim($_POST['confirmar_password']);
        
        // Validaciones
        if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
            $mensaje_error = "Todos los campos son obligatorios.";
        } elseif ($password !== $confirmar_password) {
            $mensaje_error = "Las contraseñas no coinciden.";
        } elseif (strlen($password) < 6) {
            $mensaje_error = "La contraseña debe tener al menos 6 caracteres.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje_error = "El email no es válido.";
        } else {
            // Verificar si el email ya existe
            $db = conectarDB_PDO();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $mensaje_error = "Ya existe un usuario con ese email.";
            } else {
                // Crear usuario
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $nombreyapellido = $nombre . ' ' . $apellido;
                
                $stmt = $db->prepare("INSERT INTO users (nombreyapellido, email, password, activo, fecha_creacion) VALUES (?, ?, ?, 1, NOW())");
                
                if ($stmt->execute([$nombreyapellido, $email, $password_hash])) {
                    $mensaje_exito = "Usuario creado exitosamente.";
                    // Limpiar campos
                    $nombre = $apellido = $email = '';
                } else {
                    $mensaje_error = "Error al crear el usuario.";
                }
            }
        }
    } elseif (isset($_POST['editar_usuario']) && $tiene_acceso_crear) {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre_editar']);
        $apellido = trim($_POST['apellido_editar']);
        $email = trim($_POST['email_editar']);
        $activo = isset($_POST['activo_editar']) ? 1 : 0;
        $cambiar_password = !empty($_POST['password_nuevo']);
        
        // Validaciones
        if (empty($nombre) || empty($apellido) || empty($email)) {
            $mensaje_error = "Nombre, apellido y email son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje_error = "El email no es válido.";
        } else {
            $db = conectarDB_PDO();
            
            // Verificar si el email ya existe en otro usuario
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            
            if ($stmt->fetch()) {
                $mensaje_error = "Ya existe otro usuario con ese email.";
            } else {
                $nombreyapellido = $nombre . ' ' . $apellido;
                
                if ($cambiar_password) {
                    $password_nuevo = trim($_POST['password_nuevo']);
                    if (strlen($password_nuevo) < 6) {
                        $mensaje_error = "La nueva contraseña debe tener al menos 6 caracteres.";
                    } else {
                        $password_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET nombreyapellido = ?, email = ?, password = ?, activo = ?, fecha_actualizacion = NOW() WHERE id = ?");
                        $resultado = $stmt->execute([$nombreyapellido, $email, $password_hash, $activo, $id]);
                    }
                } else {
                    $stmt = $db->prepare("UPDATE users SET nombreyapellido = ?, email = ?, activo = ?, fecha_actualizacion = NOW() WHERE id = ?");
                    $resultado = $stmt->execute([$nombreyapellido, $email, $activo, $id]);
                }
                
                if (isset($resultado) && $resultado) {
                    $mensaje_exito = "Usuario actualizado exitosamente.";
                } elseif (!isset($mensaje_error)) {
                    $mensaje_error = "Error al actualizar el usuario.";
                }
            }
        }
    } elseif (isset($_POST['eliminar_usuario']) && $tiene_acceso_crear) {
        $id = (int)$_POST['id_eliminar'];
        $confirmacion = trim($_POST['confirmacion_eliminar']);
        
        if ($confirmacion !== 'ELIMINAR') {
            $mensaje_error = "Confirmación incorrecta. Debe escribir exactamente 'ELIMINAR'.";
        } else {
            $db = conectarDB_PDO();
            
            // Verificar que no sea el último usuario activo
            $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE activo = 1");
            $total_activos = $stmt->fetch()['total'];
            
            if ($total_activos <= 1) {
                $mensaje_error = "No se puede eliminar el último usuario administrador activo.";
            } else {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                
                if ($stmt->execute([$id])) {
                    $mensaje_exito = "Usuario eliminado exitosamente.";
                } else {
                    $mensaje_error = "Error al eliminar el usuario.";
                }
            }
        }
    } elseif (isset($_POST['eliminar_acceso'])) {
        unset($_SESSION['admin_user_access']);
        unset($_SESSION['admin_user_access_expiry']);
        $mensaje_info = "Acceso a gestión de usuarios cerrado.";
    }
}

// Obtener lista de usuarios si tiene acceso
$usuarios = [];
if ($tiene_acceso_crear) {
    try {
        $db = conectarDB_PDO();
        $stmt = $db->query("SELECT id, nombreyapellido, email, activo, fecha_creacion FROM users ORDER BY fecha_creacion DESC");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje_error = "Error al cargar usuarios: " . $e->getMessage();
    }
}
?>

<!-- Encabezado de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">Gestión de Usuarios <i class="bi bi-people-fill"></i></h1>
        <nav aria-label="breadcrumb">
            <ol class="admin-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Mensajes -->
<?php if (isset($mensaje_exito)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($mensaje_exito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($mensaje_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($mensaje_info)): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($mensaje_info) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$tiene_acceso_crear): ?>
    <!-- Formulario de verificación de clave -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="admin-card shadow">
                <div class="admin-card-header bg-warning text-white py-3">
                    <h5 class="m-0"><i class="bi bi-shield-lock me-2"></i>Acceso Restringido</h5>
                </div>
                <div class="admin-card-body">
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Área de Seguridad:</strong> Para gestionar usuarios administradores, 
                        necesitas ingresar la clave de seguridad especial.
                    </div>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="clave_secreta" class="form-label">
                                <i class="bi bi-key me-1"></i>Clave de Seguridad
                            </label>
                            <input type="password" class="form-control" id="clave_secreta" 
                                   name="clave_secreta" required 
                                   placeholder="Ingresa la clave de seguridad">
                            <div class="invalid-feedback">
                                La clave de seguridad es requerida.
                            </div>
                            <div class="form-text">
                                Solo usuarios autorizados tienen acceso a esta clave.
                            </div>
                        </div>
                        
                        <button type="submit" name="verificar_clave" class="btn btn-warning btn-lg w-100">
                            <i class="bi bi-unlock me-2"></i>Verificar Acceso
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Panel de gestión de usuarios autorizado -->
    <div class="row">
        <!-- Formulario para crear usuario -->
        <div class="col-md-4">
            <div class="admin-card shadow mb-4">
                <div class="admin-card-header bg-success text-white py-3">
                    <h5 class="m-0"><i class="bi bi-person-plus me-2"></i>Crear Usuario</h5>
                </div>
                <div class="admin-card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($nombre ?? '') ?>" required>
                            <div class="invalid-feedback">El nombre es requerido.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" 
                                   value="<?= htmlspecialchars($apellido ?? '') ?>" required>
                            <div class="invalid-feedback">El apellido es requerido.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($email ?? '') ?>" required>
                            <div class="invalid-feedback">Un email válido es requerido.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="6" required>
                            <div class="invalid-feedback">La contraseña debe tener al menos 6 caracteres.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_password" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirmar_password" 
                                   name="confirmar_password" required>
                            <div class="invalid-feedback">Debe confirmar la contraseña.</div>
                        </div>
                        
                        <button type="submit" name="crear_usuario" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle me-2"></i>Crear Usuario
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Control de acceso -->
            <div class="admin-card shadow">
                <div class="admin-card-header bg-info text-white py-3">
                    <h6 class="m-0"><i class="bi bi-info-circle me-2"></i>Control de Acceso</h6>
                </div>
                <div class="admin-card-body">
                    <p class="mb-2 small">
                        <strong>Acceso autorizado hasta:</strong><br>
                        <?= date('d/m/Y H:i:s', $_SESSION['admin_user_access_expiry']) ?>
                    </p>
                    <form method="POST">
                        <button type="submit" name="eliminar_acceso" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-lock me-1"></i>Cerrar Acceso
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Lista de usuarios -->
        <div class="col-md-8">
            <div class="admin-card shadow">
                <div class="admin-card-header py-3">
                    <h5 class="m-0"><i class="bi bi-people me-2"></i>Usuarios Administradores</h5>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($usuarios)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($usuario['nombreyapellido']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                                            <td>
                                                <?php if ($usuario['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($usuario['fecha_creacion'])) ?></td>
                                            <td>
                                                <button class="btn btn-outline-primary btn-sm me-1" 
                                                        onclick="editarUsuario(<?= $usuario['id'] ?>, '<?= addslashes($usuario['nombreyapellido']) ?>', '<?= addslashes($usuario['email']) ?>', <?= $usuario['activo'] ?>)" 
                                                        title="Editar usuario">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="eliminarUsuario(<?= $usuario['id'] ?>, '<?= addslashes($usuario['nombreyapellido']) ?>')" 
                                                        title="Eliminar usuario">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-people fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay usuarios registrados</h5>
                            <p class="text-muted">Crea el primer usuario administrador.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal para editar usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarUsuarioLabel">
                    <i class="bi bi-pencil me-2"></i>Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="formEditarUsuario">
                <div class="modal-body">
                    <input type="hidden" id="id_editar" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre_editar" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre_editar" name="nombre_editar" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="apellido_editar" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="apellido_editar" name="apellido_editar" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email_editar" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email_editar" name="email_editar" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo_editar" name="activo_editar">
                            <label class="form-check-label" for="activo_editar">
                                Usuario activo
                            </label>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="password_nuevo" class="form-label">Nueva Contraseña (opcional)</label>
                        <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" minlength="6">
                        <div class="form-text">Deja en blanco si no quieres cambiar la contraseña</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_usuario" class="btn btn-primary">
                        <i class="bi bi-check2 me-2"></i>Actualizar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para eliminar usuario -->
<div class="modal fade" id="modalEliminarUsuario" tabindex="-1" aria-labelledby="modalEliminarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminarUsuarioLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="formEliminarUsuario">
                <div class="modal-body">
                    <input type="hidden" id="id_eliminar" name="id_eliminar">
                    
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>¡ATENCIÓN!</strong> Esta acción no se puede deshacer.
                    </div>
                    
                    <p>Estás a punto de eliminar permanentemente el usuario:</p>
                    <p class="fw-bold text-danger" id="nombre_usuario_eliminar"></p>
                    
                    <p>Para confirmar esta acción, escribe exactamente <code>ELIMINAR</code> en el campo de abajo:</p>
                    
                    <div class="mb-3">
                        <label for="confirmacion_eliminar" class="form-label">Confirmación</label>
                        <input type="text" class="form-control" id="confirmacion_eliminar" name="confirmacion_eliminar" 
                               placeholder="Escribe: ELIMINAR" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="eliminar_usuario" class="btn btn-danger" id="btnConfirmarEliminacion" disabled>
                        <i class="bi bi-trash me-2"></i>Eliminar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación de Bootstrap
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Validar que las contraseñas coincidan
document.getElementById('confirmar_password')?.addEventListener('input', function() {
    var password = document.getElementById('password');
    var confirmar = this;
    
    if (password && confirmar.value !== password.value) {
        confirmar.setCustomValidity('Las contraseñas no coinciden');
    } else {
        confirmar.setCustomValidity('');
    }
});

function editarUsuario(id, nombreCompleto, email, activo) {
    // Separar nombre y apellido
    const partes = nombreCompleto.split(' ');
    const nombre = partes[0] || '';
    const apellido = partes.slice(1).join(' ') || '';
    
    // Llenar el formulario de edición
    document.getElementById('id_editar').value = id;
    document.getElementById('nombre_editar').value = nombre;
    document.getElementById('apellido_editar').value = apellido;
    document.getElementById('email_editar').value = email;
    document.getElementById('activo_editar').checked = activo == 1;
    document.getElementById('password_nuevo').value = '';
    
    // Mostrar el modal
    new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
}

function eliminarUsuario(id, nombreCompleto) {
    // Llenar el formulario de eliminación
    document.getElementById('id_eliminar').value = id;
    document.getElementById('nombre_usuario_eliminar').textContent = nombreCompleto;
    document.getElementById('confirmacion_eliminar').value = '';
    document.getElementById('btnConfirmarEliminacion').disabled = true;
    
    // Mostrar el modal
    new bootstrap.Modal(document.getElementById('modalEliminarUsuario')).show();
}

// Habilitar/deshabilitar botón de eliminación según confirmación
document.getElementById('confirmacion_eliminar')?.addEventListener('input', function() {
    const confirmacion = this.value.trim();
    const boton = document.getElementById('btnConfirmarEliminacion');
    
    if (confirmacion === 'ELIMINAR') {
        boton.disabled = false;
        boton.classList.remove('btn-outline-danger');
        boton.classList.add('btn-danger');
    } else {
        boton.disabled = true;
        boton.classList.remove('btn-danger');
        boton.classList.add('btn-outline-danger');
    }
});
</script>

<style>
/* Estilos específicos para gestión de usuarios */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    line-height: 1.2;
}

.btn-outline-primary:hover {
    transform: translateY(-1px);
}

.btn-outline-danger:hover {
    transform: translateY(-1px);
}

.modal-header.bg-danger {
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.alert-danger {
    border-left: 4px solid #dc3545;
}

#confirmacion_eliminar:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-check-input:checked {
    background-color: var(--color-violeta);
    border-color: var(--color-violeta);
}

/* Animación para botones de acción */
.btn-sm {
    transition: all 0.2s ease;
}

.btn-sm:hover {
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* Estado del botón de confirmación de eliminación */
#btnConfirmarEliminacion:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Resaltado para campos obligatorios */
.form-control:required:invalid {
    border-color: #ffc107;
}

.form-control:required:valid {
    border-color: #28a745;
}
</style>

<?php include_once 'includes/footer.php'; ?>
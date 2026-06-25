<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turno Cerrado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '¿Deseas generar el balance del día ahora?',
            text: 'Si falta cerrar algún turno, elige No y cierra los turnos restantes antes de generar el balance diario.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar balance',
            cancelButtonText: 'No, volver al POS',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'generar_balance.php';
            } else {
                window.location.href = 'index.php';
            }
        });
    });
    </script>
    <div class="container text-center mt-5">
        <h2 class="mb-4"><i class="fas fa-door-closed"></i> Turno cerrado correctamente</h2>
        <p>Por favor, espera un momento...</p>
    </div>
</body>
</html> 
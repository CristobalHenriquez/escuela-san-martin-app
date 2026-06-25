<?php

/**
 * Utilidades para SweetAlert2
 * Este archivo contiene funciones para mostrar notificaciones con SweetAlert2
 */

// Función para mostrar una notificación de éxito
function showSweetAlertSuccess($message, $redirect = null)
{
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '{$message}',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#28a745',
                timer: 3000,
                timerProgressBar: true
            }).then((result) => {
                " . ($redirect ? "window.location.href = '{$redirect}';" : "") . "
            });
        });
    </script>";
}

// Función para mostrar una notificación de error
function showSweetAlertError($message)
{
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                text: '{$message}',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#dc3545'
            });
        });
    </script>";
}

// Función para mostrar una notificación de advertencia
function showSweetAlertWarning($message)
{
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: '¡Advertencia!',
                text: '{$message}',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#ffc107'
            });
        });
    </script>";
}

// Función para mostrar una notificación de información
function showSweetAlertInfo($message)
{
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: '{$message}',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#17a2b8'
            });
        });
    </script>";
}

// Función para mostrar un toast de éxito
function showSweetToastSuccess($message)
{
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            Toast.fire({
                icon: 'success',
                title: '{$message}'
            });
        });
    </script>";
}

// Función para mostrar un toast de error
function showSweetToastError($message)
{
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            Toast.fire({
                icon: 'error',
                title: '{$message}'
            });
        });
    </script>";
}

// Función para confirmar eliminación con SweetAlert2
function confirmDeleteWithSweetAlert($id, $title)
{
    echo "
    <script>
        function confirmarEliminar{$id}() {
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Vas a eliminar la publicación \"{$title}\". Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'eliminar-post.php?id={$id}';
                }
            });
        }
    </script>";
}

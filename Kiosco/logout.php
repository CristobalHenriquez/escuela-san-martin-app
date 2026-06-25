<?php
session_start();
require 'db.php';

if (!empty($_SESSION['usuario_id'])) {
    try {
        $db->prepare("UPDATE usuarios SET session_token = NULL, session_token_created_at = NULL WHERE id = ?")
            ->execute([(int) $_SESSION['usuario_id']]);
    } catch (Throwable $e) {
        // Si falla la limpieza de token, igual cerramos la sesión local.
    }
}

session_destroy();
header('Location: login.php');
exit; 

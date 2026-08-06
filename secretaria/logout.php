<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
unset($_SESSION[SECRETARIA_SESSION_KEY], $_SESSION['secretaria_login_at'], $_SESSION['secretaria_csrf']);
session_regenerate_id(true);
header('Location: index.php');
exit;

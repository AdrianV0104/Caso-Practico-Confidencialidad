<?php
require 'config.php';
if (estaLogueado()) {
    registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], 'Cerró sesión');
}
session_destroy();
header('Location: login.php');
exit;

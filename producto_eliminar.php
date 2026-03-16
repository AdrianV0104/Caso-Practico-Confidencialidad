<?php
require 'config.php';
requierePermiso('eliminar');

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("SELECT nombre FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
        $pdo->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
        registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Eliminó producto #$id: {$p['nombre']}");
    }
}
header('Location: index.php');
exit;

<?php
require 'config.php';
requiereLogin();

$error = ''; $exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual    = $_POST['actual'];
    $nueva     = $_POST['nueva'];
    $confirmar = $_POST['confirmar'];

    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!password_verify($actual, $usuario['password'])) {
        $error = 'La contraseña actual es incorrecta.';
    } elseif (strlen($nueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['usuario_id']]);
        registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], 'Cambió su contraseña');
        $exito = 'Contraseña actualizada correctamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.1); width: 380px; }
  h2 { margin-bottom: 6px; color: #1a202c; }
  .sub { color: #718096; font-size: 14px; margin-bottom: 24px; }
  label { display: block; margin-bottom: 6px; font-size: 14px; color: #4a5568; }
  input[type=password], input[type=text] { width: 100%; padding: 10px 42px 10px 14px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 15px; }
  .pass-wrap { position: relative; margin-bottom: 16px; }
  .eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:18px; user-select:none; }
  button { width: 100%; padding: 12px; background: #805ad5; color: #fff; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; margin-top:8px; }
  .error { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .exito { background: #c6f6d5; color: #276749; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .back { text-align: center; margin-top: 16px; font-size: 14px; }
  .back a { color: #3182ce; text-decoration: none; }
  .hint { font-size:12px; color:#a0aec0; margin-top:-10px; margin-bottom:14px; }
</style>
<script>function togglePass(id){const i=document.getElementById(id);i.type=i.type==="password"?"text":"password";}</script>
</head>
<body>
<div class="card">
  <h2>👤 Mi Perfil</h2>
  <p class="sub"><?= htmlspecialchars($_SESSION['usuario_email']) ?> · <?= htmlspecialchars($_SESSION['rol_nombre']) ?></p>
  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <?php if ($exito): ?><div class="exito"><?= $exito ?></div><?php endif; ?>
  <form method="POST">
    <label>Contraseña actual</label>
    <div class="pass-wrap">
      <input type="password" name="actual" id="p1" required placeholder="Ingresa tu contraseña actual">
      <span class="eye" onclick="togglePass('p1')">👁</span>
    </div>
    <label>Nueva contraseña</label>
    <div class="pass-wrap">
      <input type="password" name="nueva" id="p2" required placeholder="Mínimo 6 caracteres">
      <span class="eye" onclick="togglePass('p2')">👁</span>
    </div>
    <label>Confirmar nueva contraseña</label>
    <div class="pass-wrap">
      <input type="password" name="confirmar" id="p3" required placeholder="Repite la nueva contraseña">
      <span class="eye" onclick="togglePass('p3')">👁</span>
    </div>
    <button type="submit">Cambiar contraseña</button>
  </form>
  <div class="back"><a href="index.php">← Volver</a></div>
</div>
</body>
</html>

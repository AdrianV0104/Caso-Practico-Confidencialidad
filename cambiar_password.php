<?php
require 'config.php';

$token = $_GET['token'] ?? '';
$error = ''; $exito = '';

$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacion = ?");
$stmt->execute([$token]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die('<p style="text-align:center;margin-top:60px;color:red;">Token inválido o ya utilizado.</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva     = $_POST['nueva'];
    $confirmar = $_POST['confirmar'];
    if (strlen($nueva) < 6) {
        $error = 'Mínimo 6 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL WHERE id = ?")->execute([$hash, $usuario['id']]);
        $exito = 'Contraseña actualizada. <a href="login.php">Inicia sesión</a>.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar Contraseña</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.1); width: 360px; }
  h2 { text-align: center; margin-bottom: 24px; color: #1a202c; }
  label { display: block; margin-bottom: 6px; font-size: 14px; color: #4a5568; }
  input[type=password], input[type=text] { width: 100%; padding: 10px 42px 10px 14px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 15px; }
  .pass-wrap { position: relative; margin-bottom: 16px; }
  .eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:18px; user-select:none; }
  button { width: 100%; padding: 12px; background: #805ad5; color: #fff; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
  .error { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .exito { background: #c6f6d5; color: #276749; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
</style>
<script>function togglePass(id){const i=document.getElementById(id);i.type=i.type==="password"?"text":"password";}</script>
</head>
<body>
<div class="card">
  <h2>🔒 Nueva contraseña</h2>
  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <?php if ($exito): ?><div class="exito"><?= $exito ?></div><?php endif; ?>
  <?php if (!$exito): ?>
  <form method="POST">
    <label>Nueva contraseña</label>
    <div class="pass-wrap">
      <input type="password" name="nueva" id="p1" required placeholder="Mínimo 6 caracteres">
      <span class="eye" onclick="togglePass('p1')">👁</span>
    </div>
    <label>Confirmar contraseña</label>
    <div class="pass-wrap">
      <input type="password" name="confirmar" id="p2" required placeholder="Repite la contraseña">
      <span class="eye" onclick="togglePass('p2')">👁</span>
    </div>
    <button type="submit">Guardar</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>

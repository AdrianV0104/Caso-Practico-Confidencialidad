<?php
require 'config.php';
if (estaLogueado()) { header('Location: index.php'); exit; }

$error = ''; $exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'El email ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol_id) VALUES (?, ?, ?, 3)");
            $stmt->execute([$nombre, $email, $hash]);
            $exito = 'Cuenta creada. <a href="login.php">Inicia sesión</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.1); width: 360px; }
  h2 { text-align: center; margin-bottom: 24px; color: #1a202c; }
  label { display: block; margin-bottom: 6px; font-size: 14px; color: #4a5568; }
  input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 8px; margin-bottom: 16px; font-size: 15px; }
  button { width: 100%; padding: 12px; background: #38a169; color: #fff; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
  button:hover { background: #2f855a; }
  .pass-wrap { position:relative; margin-bottom:16px; }
  .pass-wrap input { margin-bottom:0; }
  .eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:18px; user-select:none; }
  .error { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .exito { background: #c6f6d5; color: #276749; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .links { text-align: center; margin-top: 16px; font-size: 14px; }
  .links a { color: #3182ce; text-decoration: none; }
</style>
<script>function togglePass(id){const i=document.getElementById(id);i.type=i.type==="password"?"text":"password";}</script>
</head>
<body>
<div class="card">
  <h2>📝 Registro</h2>
  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <?php if ($exito): ?><div class="exito"><?= $exito ?></div><?php endif; ?>
  <form method="POST">
    <label>Nombre</label>
    <input type="text" name="nombre" required placeholder="Tu nombre">
    <label>Email</label>
    <input type="email" name="email" required placeholder="correo@ejemplo.com">
    <label>Contraseña</label>
    <div class="pass-wrap"><input type="password" name="password" id="p1" required placeholder="Mínimo 6 caracteres"><span class="eye" onclick="togglePass('p1')">👁</span></div>
    <button type="submit">Crear cuenta</button>
  </form>
  <div class="links"><a href="login.php">Ya tengo cuenta</a></div>
</div>
</body>
</html>

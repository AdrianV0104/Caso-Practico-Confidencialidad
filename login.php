<?php
require 'config.php';

if (estaLogueado()) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario['password'])) {
        $_SESSION['usuario_id']  = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['rol_id']      = $usuario['rol_id'];
        $_SESSION['rol_nombre']  = $usuario['rol_nombre'];
        $_SESSION['permisos']    = cargarPermisosEnSesion($pdo, $usuario['rol_id']);

        registrarAcceso($pdo, $usuario['id'], $usuario['email'], 'Inicio de sesión');
        header('Location: index.php');
        exit;
    } else {
        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Iniciar Sesión</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.1); width: 360px; }
  h2 { text-align: center; margin-bottom: 24px; color: #1a202c; }
  label { display: block; margin-bottom: 6px; font-size: 14px; color: #4a5568; }
  input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 8px; margin-bottom: 16px; font-size: 15px; }
  button { width: 100%; padding: 12px; background: #3182ce; color: #fff; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
  button:hover { background: #2b6cb0; }
  .pass-wrap { position:relative; margin-bottom:16px; }
  .pass-wrap input { margin-bottom:0; }
  .eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:18px; user-select:none; }
  .error { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .links { text-align: center; margin-top: 16px; font-size: 14px; }
  .links a { color: #3182ce; text-decoration: none; }
</style>
<script>function togglePass(id){const i=document.getElementById(id);i.type=i.type==="password"?"text":"password";}</script>
</head>
<body>
<div class="card">
  <h2>🔐 Iniciar Sesión</h2>
  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" required placeholder="correo@ejemplo.com">
    <label>Contraseña</label>
    <div class="pass-wrap"><input type="password" name="password" id="p1" required placeholder="••••••••"><span class="eye" onclick="togglePass('p1')">👁</span></div>
    <button type="submit">Entrar</button>
  </form>
  <div class="links">
    <a href="registro.php">Registrarse</a> &nbsp;|&nbsp;
    <a href="recuperar.php">Olvidé mi contraseña</a>
  </div>
</div>
</body>
</html>

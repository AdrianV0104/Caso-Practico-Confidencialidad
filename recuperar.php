<?php
require 'config.php';

$error = ''; $exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $stmt  = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE usuarios SET token_recuperacion = ? WHERE id = ?")->execute([$token, $usuario['id']]);

        $link = "http://localhost/proyecto/cambiar_password.php?token=$token";
        $html = "
        <div style='font-family:Segoe UI,sans-serif;max-width:480px;margin:auto;padding:30px;border:1px solid #e2e8f0;border-radius:12px;'>
          <h2 style='color:#2d3748;'>🔑 Recuperación de contraseña</h2>
          <p style='color:#4a5568;'>Hola <b>" . htmlspecialchars($usuario['nombre']) . "</b>,</p>
          <p style='color:#4a5568;margin:16px 0;'>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el botón:</p>
          <a href='$link' style='display:inline-block;background:#3182ce;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:15px;'>Cambiar contraseña</a>
          <p style='color:#a0aec0;font-size:13px;margin-top:24px;'>Si no solicitaste esto, ignora este mensaje.</p>
        </div>";

        $enviado = enviarEmail($email, 'Recuperación de contraseña', $html);

        if ($enviado) {
            $exito = "Te enviamos un correo a <b>$email</b> con el enlace para cambiar tu contraseña.";
        } else {
            $error = 'No se pudo enviar el correo. Verifica la configuración SMTP en config.php.';
        }
    } else {
        $exito = "Si ese correo está registrado, recibirás un enlace en breve.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recuperar Contraseña</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.1); width: 380px; }
  h2 { text-align: center; margin-bottom: 8px; color: #1a202c; }
  p.sub { text-align:center; color:#718096; font-size:14px; margin-bottom:20px; }
  label { display: block; margin-bottom: 6px; font-size: 14px; color: #4a5568; }
  input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 8px; margin-bottom: 16px; font-size: 15px; }
  button { width: 100%; padding: 12px; background: #d69e2e; color: #fff; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; }
  .error { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .exito { background: #bee3f8; color: #2c5282; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .links { text-align: center; margin-top: 16px; font-size: 14px; }
  .links a { color: #3182ce; text-decoration: none; }
</style>
</head>
<body>
<div class="card">
  <h2>🔑 Recuperar contraseña</h2>
  <p class="sub">Ingresa tu email y te enviaremos un enlace para cambiarla.</p>
  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <?php if ($exito): ?><div class="exito"><?= $exito ?></div><?php endif; ?>
  <?php if (!$exito): ?>
  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" required placeholder="correo@ejemplo.com">
    <button type="submit">Enviar enlace</button>
  </form>
  <?php endif; ?>
  <div class="links"><a href="login.php">← Volver al login</a></div>
</div>
</body>
</html>

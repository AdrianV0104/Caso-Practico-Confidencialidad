<?php
// config.php - Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Cambia si tu usuario es diferente
define('DB_PASS', '');           // Cambia si tienes contraseña en XAMPP
define('DB_NAME', 'roles_app');

// ─── Configuración Gmail SMTP ────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USER',     'TU_CORREO@gmail.com');   // ← Cambia esto
define('MAIL_PASS',     'TU_CONTRASENA_APP');      // ← Contraseña de aplicación Gmail
define('MAIL_FROM',     'TU_CORREO@gmail.com');   // ← Igual que MAIL_USER
define('MAIL_FROM_NAME','Sistema de Roles');

session_start();

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ─── Helpers ────────────────────────────────────────────

function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}

function requiereLogin() {
    if (!estaLogueado()) {
        header('Location: login.php');
        exit;
    }
}

function tienePermiso($permiso) {
    return isset($_SESSION['permisos']) && in_array($permiso, $_SESSION['permisos']);
}

function requierePermiso($permiso) {
    requiereLogin();
    if (!tienePermiso($permiso)) {
        die('<p style="color:red;text-align:center;margin-top:50px;">⛔ No tienes permiso para acceder aquí.</p>');
    }
}

function registrarAcceso($pdo, $usuario_id, $email, $accion) {
    $stmt = $pdo->prepare("INSERT INTO historial_acceso (usuario_id, email, accion) VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, $email, $accion]);
}

function enviarEmail($destinatario, $asunto, $cuerpoHtml) {
    // PHPMailer vía SMTP sin librería externa — socket directo
    $host    = MAIL_HOST;
    $port    = MAIL_PORT;
    $usuario = MAIL_USER;
    $pass    = MAIL_PASS;
    $from    = MAIL_FROM;
    $fromName= MAIL_FROM_NAME;

    $errno = 0; $errstr = '';
    $sock = fsockopen("tcp://$host", $port, $errno, $errstr, 10);
    if (!$sock) return false;

    $recv = function() use ($sock) { return fgets($sock, 1024); };
    $send = function($cmd) use ($sock) { fwrite($sock, $cmd . "\r\n"); };

    $recv(); // 220 banner
    $send("EHLO localhost");
    while (($line = $recv()) && substr($line,3,1) === '-');

    $send("STARTTLS");
    $recv(); // 220
    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    $send("EHLO localhost");
    while (($line = $recv()) && substr($line,3,1) === '-');

    $send("AUTH LOGIN");
    $recv();
    $send(base64_encode($usuario));
    $recv();
    $send(base64_encode($pass));
    $auth = $recv();
    if (strpos($auth, '235') === false) { fclose($sock); return false; }

    $send("MAIL FROM:<$from>");
    $recv();
    $send("RCPT TO:<$destinatario>");
    $recv();
    $send("DATA");
    $recv();

    $boundary = md5(time());
    $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
    $headers .= "To: $destinatario\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($asunto) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $send($headers . "\r\n" . $cuerpoHtml . "\r\n.");
    $recv();
    $send("QUIT");
    fclose($sock);
    return true;
}

function cargarPermisosEnSesion($pdo, $rol_id) {
    $stmt = $pdo->prepare("
        SELECT p.nombre FROM permisos p
        JOIN roles_permisos rp ON rp.permiso_id = p.id
        WHERE rp.rol_id = ?
    ");
    $stmt->execute([$rol_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

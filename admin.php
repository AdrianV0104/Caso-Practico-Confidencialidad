<?php
require 'config.php';
requierePermiso('gestionar_usuarios');

$mensaje = '';
$ROLES_PROTEGIDOS = [1,2,3]; // Solo los roles predeterminados están protegidos

// Cambiar rol de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $uid = (int)$_POST['usuario_id'];
    $rid = (int)$_POST['rol_id'];
    $pdo->prepare("UPDATE usuarios SET rol_id = ? WHERE id = ?")->execute([$rid, $uid]);
    registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Cambió rol del usuario #$uid");
    $mensaje = '✅ Rol actualizado.';
}

// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre   = trim($_POST['nuevo_nombre']);
    $email    = trim($_POST['nuevo_email']);
    $password = $_POST['nuevo_password'];
    $rol_id   = (int)$_POST['nuevo_rol_id'];
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $mensaje = '⚠️ El email ya está registrado.';
    } elseif (strlen($password) < 6) {
        $mensaje = '⚠️ La contraseña debe tener al menos 6 caracteres.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol_id) VALUES (?,?,?,?)")
            ->execute([$nombre, $email, $hash, $rol_id]);
        registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Creó usuario: $email");
        $mensaje = "✅ Usuario '$nombre' creado correctamente.";
    }
}

// Crear nuevo rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_rol'])) {
    $nombre_rol   = trim($_POST['nombre_rol']);
    $desc_rol     = trim($_POST['desc_rol']);
    $permisos_sel = $_POST['permisos'] ?? [];
    if ($nombre_rol) {
        $pdo->prepare("INSERT IGNORE INTO roles (nombre, descripcion) VALUES (?,?)")->execute([$nombre_rol, $desc_rol]);
        $nuevo_rol_id = $pdo->lastInsertId();
        if ($nuevo_rol_id && !empty($permisos_sel)) {
            foreach ($permisos_sel as $pid) {
                $pdo->prepare("INSERT IGNORE INTO roles_permisos (rol_id, permiso_id) VALUES (?,?)")->execute([$nuevo_rol_id, (int)$pid]);
            }
        }
        registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Creó rol: $nombre_rol");
        $mensaje = "✅ Rol '$nombre_rol' creado.";
    }
}

// Editar permisos de un rol existente (protegido)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_rol'])) {
    $rid = (int)$_POST['editar_rol_id'];
    if (in_array($rid, $ROLES_PROTEGIDOS)) {
        $mensaje = '⚠️ No puedes editar los roles predeterminados del sistema.';
    } else {
        $permisos_sel = $_POST['editar_permisos'] ?? [];
        $pdo->prepare("DELETE FROM roles_permisos WHERE rol_id = ?")->execute([$rid]);
        foreach ($permisos_sel as $pid) {
            $pdo->prepare("INSERT IGNORE INTO roles_permisos (rol_id, permiso_id) VALUES (?,?)")->execute([$rid, (int)$pid]);
        }
        registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Editó permisos del rol #$rid");
        $mensaje = '✅ Permisos del rol actualizados.';
    }
}

// Eliminar rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_rol'])) {
    $rid = (int)$_POST['eliminar_rol_id'];
    if (in_array($rid, $ROLES_PROTEGIDOS)) {
        $mensaje = '⚠️ No puedes eliminar los roles predeterminados del sistema.';
    } else {
        // Verificar que no haya usuarios con ese rol
        $check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = ?");
        $check->execute([$rid]);
        if ($check->fetchColumn() > 0) {
            $mensaje = '⚠️ No se puede eliminar: hay usuarios asignados a este rol.';
        } else {
            $stmt = $pdo->prepare("SELECT nombre FROM roles WHERE id = ?");
            $stmt->execute([$rid]);
            $nombre_eliminado = $stmt->fetchColumn();
            $pdo->prepare("DELETE FROM roles WHERE id = ?")->execute([$rid]);
            registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Eliminó rol: $nombre_eliminado");
            $mensaje = "✅ Rol '$nombre_eliminado' eliminado.";
        }
    }
}

// Cargar datos
$usuarios  = $pdo->query("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id ORDER BY u.id")->fetchAll(PDO::FETCH_ASSOC);
$roles     = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$permisos  = $pdo->query("SELECT * FROM permisos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$historial = $pdo->query("SELECT * FROM historial_acceso ORDER BY fecha DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// Permisos actuales por rol (para pre-marcar checkboxes)
$permisos_por_rol = [];
$rows = $pdo->query("SELECT rol_id, permiso_id FROM roles_permisos")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $permisos_por_rol[$r['rol_id']][] = $r['permiso_id'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel de Administración</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f7fafc; color: #2d3748; }
  nav { background: #1a202c; color: #fff; padding: 14px 30px; display: flex; align-items: center; justify-content: space-between; }
  nav .brand { font-size: 18px; font-weight: 700; }
  nav a { color: #90cdf4; text-decoration: none; font-size: 14px; }
  main { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
  h1 { font-size: 26px; margin-bottom: 24px; }
  section { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.07); padding: 24px; margin-bottom: 30px; }
  h2 { font-size: 18px; margin-bottom: 16px; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #edf2f7; padding: 10px 14px; text-align: left; font-size: 13px; color: #4a5568; text-transform: uppercase; }
  td { padding: 10px 14px; border-top: 1px solid #e2e8f0; font-size: 14px; vertical-align: top; }
  tr:hover td { background: #f7fafc; }
  select, input[type=text], input[type=email], input[type=password] { padding: 7px 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; }
  button { padding: 7px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
  .btn-blue   { background: #3182ce; color: #fff; }
  .btn-green  { background: #38a169; color: #fff; }
  .btn-red    { background: #e53e3e; color: #fff; }
  .btn-yellow { background: #d69e2e; color: #fff; }
  .msg { padding: 10px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; background: #c6f6d5; color: #276749; }
  .msg.warn { background: #fefcbf; color: #744210; }
  .form-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 14px; }
  .form-row > div { display: flex; flex-direction: column; gap: 4px; }
  .form-row label { font-size: 13px; color: #4a5568; }
  .check-group { display: flex; gap: 12px; flex-wrap: wrap; }
  .check-group label { font-size: 14px; display: flex; align-items: center; gap: 5px; cursor: pointer; }
  .badge { background: #4299e1; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 12px; }
  .badge-gray { background: #a0aec0; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 12px; }
  .badge-perm { background: #e9d8fd; color: #553c9a; padding: 2px 8px; border-radius: 20px; font-size: 12px; margin: 2px; display:inline-block; }
  .time { color: #a0aec0; font-size: 12px; }
  .pass-wrap { position: relative; }
  .pass-wrap input { padding-right: 38px; }
  .eye { position: absolute; right: 9px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 16px; user-select: none; }
  /* Modal */
  .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:100; align-items:center; justify-content:center; }
  .modal-bg.open { display:flex; }
  .modal { background:#fff; border-radius:12px; padding:30px; width:480px; max-width:95vw; box-shadow:0 8px 40px rgba(0,0,0,.2); }
  .modal h3 { font-size:18px; margin-bottom:16px; }
  .modal .actions { display:flex; gap:10px; margin-top:20px; justify-content:flex-end; }
  .protected-tag { font-size:11px; color:#a0aec0; margin-left:6px; }
</style>
<script>
function togglePass(id){ const i=document.getElementById(id); i.type=i.type==="password"?"text":"password"; }

function cerrarEditar() { document.getElementById('modal-editar').classList.remove('open'); }

document.addEventListener('DOMContentLoaded', function() {
    // Botones editar
    document.querySelectorAll('.btn-editar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rolId      = this.dataset.id;
            var rolNombre  = this.dataset.nombre;
            var activos    = JSON.parse(this.dataset.permisos);
            document.getElementById('modal-titulo').textContent = 'Editar rol: ' + rolNombre;
            document.getElementById('editar_rol_id').value = rolId;
            document.querySelectorAll('.modal-perm-check').forEach(function(cb) {
                cb.checked = activos.includes(parseInt(cb.value));
            });
            document.getElementById('modal-editar').classList.add('open');
        });
    });

    // Botones eliminar
    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rolId     = this.dataset.id;
            var rolNombre = this.dataset.nombre;
            if (confirm('¿Eliminar el rol "' + rolNombre + '"? Esta acción no se puede deshacer.')) {
                document.getElementById('eliminar_rol_id').value = rolId;
                document.getElementById('form-eliminar').submit();
            }
        });
    });
});
</script>
</head>
<body>

<!-- Modal editar permisos -->
<div class="modal-bg" id="modal-editar" onclick="if(event.target===this)cerrarEditar()">
  <div class="modal">
    <h3 id="modal-titulo">Editar rol</h3>
    <form method="POST">
      <input type="hidden" name="editar_rol_id" id="editar_rol_id">
      <p style="font-size:13px;color:#4a5568;margin-bottom:12px;">Selecciona los permisos para este rol:</p>
      <div class="check-group" style="flex-direction:column;gap:10px;">
        <?php foreach ($permisos as $p): ?>
          <label>
            <input type="checkbox" class="modal-perm-check" name="editar_permisos[]" value="<?= $p['id'] ?>">
            <span><b><?= htmlspecialchars($p['nombre']) ?></b> — <span style="color:#718096;font-size:13px;"><?= htmlspecialchars($p['descripcion']) ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="editar_rol" value="1">
      <div class="actions">
        <button type="button" onclick="cerrarEditar()" style="background:#e2e8f0;color:#2d3748;">Cancelar</button>
        <button type="submit" class="btn-blue">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<nav>
  <div class="brand">⚙️ Panel Admin</div>
  <div style="display:flex;gap:16px;">
    <a href="index.php">← Catálogo</a>
    <a href="logout.php">Salir</a>
  </div>
</nav>

<main>
  <h1>Panel de Administración</h1>

  <!-- Form oculto para eliminar rol -->
  <form method="POST" id="form-eliminar" style="display:none;">
    <input type="hidden" name="eliminar_rol_id" id="eliminar_rol_id">
    <input type="hidden" name="eliminar_rol" value="1">
  </form>
  <?php if ($mensaje): ?>
    <div class="msg <?= strpos($mensaje,'⚠️')!==false?'warn':'' ?>"><?= $mensaje ?></div>
  <?php endif; ?>

  <!-- Usuarios -->
  <section>
    <h2>👥 Usuarios registrados</h2>
    <table>
      <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol actual</th><th>Cambiar rol</th></tr></thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['nombre']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="badge"><?= htmlspecialchars($u['rol_nombre']) ?></span></td>
        <td>
          <form method="POST" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
            <select name="rol_id">
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $r['id']==$u['rol_id']?'selected':'' ?>><?= htmlspecialchars($r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="cambiar_rol" class="btn-blue">Asignar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <!-- Crear usuario -->
  <section>
    <h2>➕ Crear nuevo usuario</h2>
    <form method="POST">
      <div class="form-row">
        <div>
          <label>Nombre</label>
          <input type="text" name="nuevo_nombre" placeholder="Nombre completo" required>
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="nuevo_email" placeholder="correo@ejemplo.com" required>
        </div>
        <div>
          <label>Contraseña</label>
          <div class="pass-wrap">
            <input type="password" name="nuevo_password" id="np1" placeholder="Mínimo 6 caracteres" required>
            <span class="eye" onclick="togglePass('np1')">👁</span>
          </div>
        </div>
        <div>
          <label>Rol</label>
          <select name="nuevo_rol_id">
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="justify-content:flex-end;">
          <button type="submit" name="crear_usuario" class="btn-green">Crear usuario</button>
        </div>
      </div>
    </form>
  </section>

  <!-- Roles y permisos -->
  <section>
    <h2>🛡️ Roles y permisos</h2>
    <table>
      <thead><tr><th>Rol</th><th>Descripción</th><th>Permisos asignados</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($roles as $rol):
        $activos = $permisos_por_rol[$rol['id']] ?? [];
        $esProtegido = in_array($rol['id'], $ROLES_PROTEGIDOS);
      ?>
      <tr>
        <td>
          <span class="badge"><?= htmlspecialchars($rol['nombre']) ?></span>
          <?php if ($esProtegido): ?><span class="protected-tag">sistema</span><?php endif; ?>
        </td>
        <td style="color:#718096;font-size:13px;"><?= htmlspecialchars($rol['descripcion'] ?? '') ?></td>
        <td>
          <?php if (empty($activos)): ?>
            <span style="color:#a0aec0;font-size:13px;">Sin permisos</span>
          <?php else: ?>
            <?php foreach ($permisos as $p): ?>
              <?php if (in_array($p['id'], $activos)): ?>
                <span class="badge-perm"><?= htmlspecialchars($p['nombre']) ?></span>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </td>
        <td>
          <div style="display:flex;gap:8px;">
            <?php if (!$esProtegido): ?>
              <button class="btn-yellow btn-editar"
                data-id="<?= $rol['id'] ?>"
                data-nombre="<?= htmlspecialchars($rol['nombre'], ENT_QUOTES) ?>"
                data-permisos="<?= htmlspecialchars(json_encode(array_map('intval', $activos)), ENT_QUOTES) ?>"
              >✏️ Editar</button>
              <button class="btn-red btn-eliminar"
                data-id="<?= $rol['id'] ?>"
                data-nombre="<?= htmlspecialchars($rol['nombre'], ENT_QUOTES) ?>"
              >🗑 Eliminar</button>
            <?php else: ?>
              <button disabled style="background:#e2e8f0;color:#a0aec0;cursor:not-allowed;">🔒 Protegido</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div style="margin-top:24px;border-top:2px solid #e2e8f0;padding-top:20px;">
      <h3 style="font-size:16px;margin-bottom:14px;color:#2d3748;">➕ Crear nuevo rol</h3>
      <form method="POST">
        <div class="form-row">
          <div>
            <label>Nombre del rol</label>
            <input type="text" name="nombre_rol" placeholder="Ej: Moderador" required>
          </div>
          <div>
            <label>Descripción</label>
            <input type="text" name="desc_rol" placeholder="Opcional" style="width:220px;">
          </div>
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;color:#4a5568;margin-bottom:8px;display:block;">Permisos iniciales</label>
          <div class="check-group">
            <?php foreach ($permisos as $p): ?>
              <label><input type="checkbox" name="permisos[]" value="<?= $p['id'] ?>"> <?= htmlspecialchars($p['nombre']) ?></label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" name="crear_rol" class="btn-green">Crear rol</button>
      </form>
    </div>
  </section>

  <!-- Historial -->
  <section>
    <h2>📋 Registro de auditoría (últimas 50 acciones)</h2>
    <table>
      <thead><tr><th>#</th><th>Usuario</th><th>Acción</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach ($historial as $h): ?>
      <tr>
        <td><?= $h['id'] ?></td>
        <td><?= htmlspecialchars($h['email'] ?? '-') ?></td>
        <td><?= htmlspecialchars($h['accion']) ?></td>
        <td class="time"><?= $h['fecha'] ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
</body>
</html>
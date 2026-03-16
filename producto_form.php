<?php
require 'config.php';
requierePermiso('escribir');

$id = $_GET['id'] ?? null;
$producto = ['nombre'=>'','descripcion'=>'','precio'=>'','stock'=>''];
$error = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC) ?: $producto;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio      = (float) $_POST['precio'];
    $stock       = (int)   $_POST['stock'];

    if (!$nombre || $precio <= 0) {
        $error = 'Nombre y precio son obligatorios.';
    } else {
        if ($id) {
            $pdo->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=? WHERE id=?")
                ->execute([$nombre, $descripcion, $precio, $stock, $id]);
            registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Editó producto #$id: $nombre");
        } else {
            $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, creado_por) VALUES (?,?,?,?,?)")
                ->execute([$nombre, $descripcion, $precio, $stock, $_SESSION['usuario_id']]);
            registrarAcceso($pdo, $_SESSION['usuario_id'], $_SESSION['usuario_email'], "Creó producto: $nombre");
        }
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $id ? 'Editar' : 'Nuevo' ?> Producto</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.1); width: 420px; }
  h2 { margin-bottom: 24px; color: #1a202c; }
  label { display: block; margin-bottom: 6px; font-size: 14px; color: #4a5568; }
  input, textarea { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 8px; margin-bottom: 16px; font-size: 15px; font-family: inherit; }
  textarea { height: 90px; resize: vertical; }
  .row { display: flex; gap: 16px; }
  .row > div { flex: 1; }
  .btns { display: flex; gap: 10px; }
  button { flex:1; padding: 12px; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; }
  .btn-save { background: #3182ce; color: #fff; }
  .btn-cancel { background: #e2e8f0; color: #2d3748; text-decoration: none; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size:15px; }
  .error { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
</style>
</head>
<body>
<div class="card">
  <h2><?= $id ? '✏️ Editar' : '➕ Nuevo' ?> Producto</h2>
  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <form method="POST">
    <label>Nombre</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
    <label>Descripción</label>
    <textarea name="descripcion"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
    <div class="row">
      <div>
        <label>Precio ($)</label>
        <input type="number" name="precio" step="0.01" min="0" value="<?= $producto['precio'] ?>" required>
      </div>
      <div>
        <label>Stock</label>
        <input type="number" name="stock" min="0" value="<?= $producto['stock'] ?>">
      </div>
    </div>
    <div class="btns">
      <button type="submit" class="btn-save">Guardar</button>
      <a href="index.php" class="btn-cancel">Cancelar</a>
    </div>
  </form>
</div>
</body>
</html>

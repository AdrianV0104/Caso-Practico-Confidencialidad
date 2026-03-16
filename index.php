<?php
require 'config.php';
requiereLogin();
requierePermiso('leer');

$productos = $pdo->query("SELECT p.*, u.nombre as autor FROM productos p LEFT JOIN usuarios u ON u.id = p.creado_por ORDER BY p.creado_en ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Catálogo de Productos</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f7fafc; color: #2d3748; }
  nav { background: #2d3748; color: #fff; padding: 14px 30px; display: flex; align-items: center; justify-content: space-between; }
  nav .brand { font-size: 18px; font-weight: 700; }
  nav .info { font-size: 14px; display: flex; gap: 16px; align-items: center; }
  nav a { color: #90cdf4; text-decoration: none; font-size: 14px; }
  nav a:hover { text-decoration: underline; }
  .badge { background: #4299e1; color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
  main { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
  .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  h1 { font-size: 24px; }
  .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
  .btn-green { background: #38a169; color: #fff; }
  .btn-blue  { background: #3182ce; color: #fff; }
  .btn-red   { background: #e53e3e; color: #fff; }
  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.07); }
  th { background: #edf2f7; padding: 12px 16px; text-align: left; font-size: 13px; color: #4a5568; text-transform: uppercase; letter-spacing: .05em; }
  td { padding: 12px 16px; border-top: 1px solid #e2e8f0; font-size: 15px; }
  tr:hover td { background: #f7fafc; }
  .acciones { display: flex; gap: 8px; }
  .empty { text-align: center; padding: 40px; color: #718096; }
</style>
</head>
<body>
<nav>
  <div class="brand">🛒 Catálogo</div>
  <div class="info">
    <span>Hola, <b><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></b></span>
    <span class="badge"><?= htmlspecialchars($_SESSION['rol_nombre']) ?></span>
    <?php if ($_SESSION['rol_nombre'] === 'Administrador'): ?>
      <a href="admin.php">Panel Admin</a>
    <?php endif; ?>
    <a href="mi_perfil.php">Mi perfil</a>
    <a href="logout.php">Salir</a>
  </div>
</nav>
<main>
  <div class="top-bar">
    <h1>Productos</h1>
    <?php if (tienePermiso('escribir')): ?>
      <a href="producto_form.php" class="btn btn-green">+ Nuevo producto</a>
    <?php endif; ?>
  </div>

  <?php if (empty($productos)): ?>
    <div class="empty">No hay productos registrados.</div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Stock</th><th>Autor</th>
        <?php if (tienePermiso('escribir') || tienePermiso('eliminar')): ?><th>Acciones</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($productos as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['nombre']) ?></td>
        <td><?= htmlspecialchars($p['descripcion']) ?></td>
        <td>$<?= number_format($p['precio'], 2) ?></td>
        <td><?= $p['stock'] ?></td>
        <td><?= htmlspecialchars($p['autor'] ?? '-') ?></td>
        <?php if (tienePermiso('escribir') || tienePermiso('eliminar')): ?>
        <td>
          <div class="acciones">
            <?php if (tienePermiso('escribir')): ?>
              <a href="producto_form.php?id=<?= $p['id'] ?>" class="btn btn-blue">Editar</a>
            <?php endif; ?>
            <?php if (tienePermiso('eliminar')): ?>
              <a href="producto_eliminar.php?id=<?= $p['id'] ?>" class="btn btn-red" onclick="return confirm('¿Eliminar producto?')">Eliminar</a>
            <?php endif; ?>
          </div>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
</body>
</html>

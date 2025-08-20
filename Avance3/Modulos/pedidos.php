<?php
session_start();
require_once('../include/layout.php');
require_once('../include/conexion.php');
require_once('../models/factura_model.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function e($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$model   = new FacturaModel($mysqli);
$logged  = !empty($_SESSION['usuario']);
$id_cli  = $logged ? $model->obtenerIdClientePorUsuario((int)$_SESSION['usuario']) : null;

$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$facturas = [];
$totalFact = 0;

if ($logged && $id_cli) {
  $totalFact = $model->contarFacturasPorCliente($id_cli);
  $facturas  = $model->listarFacturasPorCliente($id_cli, $perPage, $offset, 'DESC');
}

function pageUrl($p)
{
  $qs = $_GET;
  $qs['page'] = $p;
  return 'historial.php?' . http_build_query($qs);
}

?>
<!doctype html>
<html lang="es">

<head>
  
  <meta charset="utf-8">
  <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
  <title>Pedidos | Soluna</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="bg-light d-flex flex-column min-vh-100">

  <?php renderHeader(); ?>

  <main class="container py-4">
    <h2 class="mb-4">Historial de pedidos</h2>

    <?php if (!$logged): ?>
      <div class="alert alert-info d-flex justify-content-between align-items-center">
        <div>Para ver tu historial de facturas, inicia sesión.</div>
        <a href="../login.php?next=facturas/historial.php" class="btn btn-primary btn-sm">Iniciar sesión</a>
      </div>
    <?php elseif (!$id_cli): ?>
      <div class="alert alert-warning">
        No encontramos un perfil de cliente asociado. Completa tu <a href="Modulos/perfil_cliente.php" class="alert-link">perfil</a>.
      </div>
    <?php elseif (!$facturas): ?>
      <div class="alert alert-info">No tienes pedidos aún.</div>
    <?php else: ?>

      <div class="list-group">
        <?php foreach ($facturas as $f): ?>
          <div class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between align-items-center">
              <div>
                <h5 class="mb-1">Factura #<?= (int)$f['id_factura'] ?></h5>
                <small class="text-muted">Fecha: <?= e($f['fecha']) ?></small>
                <?php if (!empty($f['fecha_pedido'])): ?>
                  <small class="text-muted ms-2">Pedido: <?= e($f['fecha_pedido']) ?></small>
                <?php endif; ?>
                <?php if (!empty($f['metodo_pago'])): ?>
                  <span class="badge bg-secondary ms-2"><?= e($f['metodo_pago']) ?></span>
                <?php endif; ?>
              </div>
              <div class="text-end">
                <div class="fw-bold">₡<?= number_format((float)$f['total'], 2, '.', ',') ?></div>
                <a class="btn btn-sm btn-outline-primary mt-1" href="Modulos/ver.php?id=<?= (int)$f['id_factura'] ?>">Ver / Imprimir</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php
      $lastPage = (int)ceil($totalFact / $perPage);
      if ($lastPage > 1):
      ?>
        <nav class="mt-3">
          <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= e(pageUrl($page - 1)) ?>">Anterior</a>
            </li>
            <?php for ($p = 1; $p <= $lastPage; $p++): ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= e(pageUrl($p)) ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= e(pageUrl($page + 1)) ?>">Siguiente</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>

    <?php endif; ?>
  </main>

  <?php renderFooter(); ?>
</body>

</html>
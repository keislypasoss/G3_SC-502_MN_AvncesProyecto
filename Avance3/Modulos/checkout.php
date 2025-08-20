<?php
// Modulos/checkout.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/carrito_model.php';

$carrito = new CarritoModel();
$items = $carrito->items();

// Requiere login
$idUsuario = $_SESSION['usuario'] ?? null;
if (!$idUsuario) {
    header('Location: ./login.php?next= ./checkout.php');
    exit;
}

if (!$items) {
    header('Location: ./carrito.php');
    exit;
}

// Métodos de pago desde BD
$metodos = [];
$res = $mysqli->query("SELECT id_metodo_pago, nombre FROM Metodo_Pago ORDER BY id_metodo_pago");
while ($row = $res->fetch_assoc()) { $metodos[] = $row; }

// Datos de cliente/usuario (solo para mostrar)
$cliente = null;
$st = $mysqli->prepare("SELECT id_cliente, nombre, telefono, direccion FROM Cliente WHERE id_usuario=? LIMIT 1");
$st->bind_param('i', $idUsuario);
$st->execute();
$cliente = $st->get_result()->fetch_assoc();

$correo = '';
$stU = $mysqli->prepare("SELECT correo FROM Usuario WHERE id_usuario=? LIMIT 1");
$stU->bind_param('i', $idUsuario);
$stU->execute();
if ($u = $stU->get_result()->fetch_assoc()) { $correo = $u['correo']; }

$total = 0.0;
foreach ($items as $it) { $total += $it['subtotal']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
  <title>Soluna | Confirmar compra</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="styles/styles.css">
</head>
<body class="bg-light">
<main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
  <?php renderHeader(); ?>

  <section class="container py-4">
    <h1 class="h3 mb-3">Confirmar compra</h1>

    <div class="row g-4">
      <div class="col-lg-7">
        <!-- Datos del cliente detectado (solo visual) -->
        <div class="card mb-3">
          <div class="card-header">Tu perfil</div>
          <div class="card-body">
            <div><strong>Usuario:</strong> <?php echo htmlspecialchars($correo); ?></div>
            <div><strong>Nombre:</strong> <?php echo htmlspecialchars($cliente['nombre'] ?? '(sin nombre)'); ?></div>
            <div><strong>Teléfono:</strong> <?php echo htmlspecialchars($cliente['telefono'] ?? '(sin teléfono)'); ?></div>
            <div><strong>Dirección:</strong> <?php echo htmlspecialchars($cliente['direccion'] ?? '(sin dirección)'); ?></div>
            <div class="form-text">Estos datos se tomarán de tu perfil. Puedes actualizarlos más tarde en tu cuenta.</div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">Pago</div>
          <div class="card-body">
            <form id="frmPago" class="row g-3">
              <div class="col-12">
                <label class="form-label">Nota al pedido (opcional)</label>
                <textarea class="form-control" name="nota" rows="2" placeholder="Instrucciones especiales..."></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Método de pago</label>
                <select class="form-select" name="metodo_nombre" required>
                  <?php foreach ($metodos as $m): ?>
                    <option value="<?php echo htmlspecialchars($m['nombre']); ?>">
                      <?php echo htmlspecialchars($m['nombre']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Campos demo (no se envían) -->
              <div class="col-md-6">
                <label class="form-label">N° tarjeta (demo)</label>
                <input type="text" class="form-control" placeholder="4111 1111 1111 1111">
              </div>

              <div class="col-12 d-flex gap-2">
                <button type="button" id="btnConfirmar" class="btn btn-success">Confirmar compra</button>
                <a href="Modulos/carrito.php" class="btn btn-outline-secondary">Volver al carrito</a>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card">
          <div class="card-header">Resumen</div>
          <div class="card-body">
            <ul class="list-group list-group-flush mb-3">
              <?php foreach ($items as $it): ?>
                <li class="list-group-item d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($it['nombre']); ?></div>
                    <div class="small text-muted">x<?php echo (int)$it['cantidad']; ?></div>
                  </div>
                  <div>₡<?php echo number_format($it['subtotal'], 2); ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
            <div class="d-flex justify-content-between fw-bold">
              <span>Total</span><span>₡<?php echo number_format($total, 2); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php renderFooter(); ?>
</main>

<!-- Modal éxito -->
<div class="modal fade" id="modalOk" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">¡Compra confirmada!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="okBody">Procesando...</div>
      <div class="modal-footer">
        <a id="lnkFactura" href="#" class="btn btn-primary">Ver factura</a>
        <a href="Modulos/tienda.php" class="btn btn-outline-secondary">Seguir comprando</a>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('btnConfirmar').addEventListener('click', async () => {
  const form = document.getElementById('frmPago');
  if (!form.reportValidity()) return;

  const fd = new FormData(form);
  fd.append('accion', 'checkout');

  try {
    const resp = await fetch('Modulos/checkout_api.php', { method: 'POST', body: fd });
    const data = await resp.json();
    if (!resp.ok || !data.ok) throw new Error(data.msg || 'Error en el pago');

    document.getElementById('okBody').innerHTML =
      `Pago simulado <strong>${data.metodo}</strong> aprobado.<br>
       Pedido: <strong>${data.id_pedido}</strong><br>
       Factura: <strong>${data.id_factura}</strong>`;

    document.getElementById('lnkFactura').href = 'Modulos/factura_ver.php?id=' + data.id_factura;

    const modal = new bootstrap.Modal(document.getElementById('modalOk'));
    modal.show();

    const badge = document.querySelector('[data-cart-badge]');
    if (badge) { badge.textContent = '0'; badge.classList.add('d-none'); }
  } catch (err) {
    alert(err.message || 'No se pudo procesar la compra.');
  }
});
</script>
</body>
</html>

<?php
session_start();
// Modulos/carrito.php
$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/layout.php';
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/producto_model.php';
require_once $APP_ROOT . '/models/carrito_model.php';

$modelProd = new ProductoModel($mysqli);
$carrito   = new CarritoModel();

$flash = null;

/* ====== ACCIONES ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'agregar') {
            $id  = (int)($_POST['id_producto'] ?? 0);
            $qty = max(1, (int)($_POST['cantidad'] ?? 1));
            if ($id > 0) {
                $p = $modelProd->obtenerPorId($id);
                if ($p && (int)$p['disponible'] === 1) {
                    $carrito->add($id, $p['nombre'], (float)$p['precio'], $qty, $p['imagen'] ?? '');
                    $flash = ['type' => 'success', 'msg' => 'Producto agregado al carrito.'];
                } else {
                    $flash = ['type' => 'warning', 'msg' => 'Producto no disponible.'];
                }
            }
        } elseif ($accion === 'actualizar') {
            $id  = (int)($_POST['id_producto'] ?? 0);
            $qty = (int)($_POST['cantidad'] ?? 1);
            $carrito->setQuantity($id, $qty);
            $flash = ['type' => 'info', 'msg' => 'Cantidad actualizada.'];
        } elseif ($accion === 'incrementar') {
            $id = (int)($_POST['id_producto'] ?? 0);
            $carrito->increment($id, +1);
        } elseif ($accion === 'decrementar') {
            $id = (int)($_POST['id_producto'] ?? 0);
            $carrito->increment($id, -1);
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id_producto'] ?? 0);
            $carrito->remove($id);
            $flash = ['type' => 'danger', 'msg' => 'Producto eliminado del carrito.'];
        } elseif ($accion === 'vaciar') {
            $carrito->clear();
            $flash = ['type' => 'secondary', 'msg' => 'Carrito vaciado.'];
        }
    } catch (Throwable $e) {
        $flash = ['type' => 'danger', 'msg' => 'Error: ' . $e->getMessage()];
    }
}

$items = $carrito->items();
$total = $carrito->total();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Soluna | Carrito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body class="bg-light">
    <main class="container-fluid d-flex flex-column min-vh-100" style="margin:0;padding:0;">
        <?php renderHeader(); ?>

        <section class="container py-4">
            <h1 class="h3 mb-3">Tu carrito</h1>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flash['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>

            <?php if (!$items): ?>
                <div class="text-center text-muted py-5">
                    <p class="mb-3">Tu carrito está vacío.</p>
                    <a href="Modulos/menu.php" class="btn btn-primary">Ir al menú</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:72px;"></th>
                                <th>Producto</th>
                                <th class="text-end" style="width:140px;">Precio</th>
                                <th class="text-center" style="width:220px;">Cantidad</th>
                                <th class="text-end" style="width:160px;">Subtotal</th>
                                <th style="width:110px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($it['imagen'])): ?>
                                            <img src="<?php echo htmlspecialchars($it['imagen']); ?>" alt=""
                                                class="rounded" style="width:64px;height:64px;object-fit:cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded" style="width:64px;height:64px;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($it['nombre']); ?></div>
                                    </td>
                                    <td class="text-end">₡<?php echo number_format((float)$it['precio'], 2); ?></td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="accion" value="decrementar">
                                                <input type="hidden" name="id_producto" value="<?php echo (int)$it['id']; ?>">
                                                <button class="btn btn-outline-secondary btn-sm" <?php echo ($it['cantidad'] <= 1 ? 'disabled' : ''); ?>>−</button>
                                            </form>
                                            <form method="post" class="d-inline d-flex align-items-center gap-1">
                                                <input type="hidden" name="accion" value="actualizar">
                                                <input type="hidden" name="id_producto" value="<?php echo (int)$it['id']; ?>">
                                                <input type="number" name="cantidad" min="1" class="text-center form-control form-control-sm no-outline"
                                                    style="width:70px;" value="<?php echo (int)$it['cantidad']; ?>">
                                                <button class="btn btn-primary btn-sm">Actualizar</button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="accion" value="incrementar">
                                                <input type="hidden" name="id_producto" value="<?php echo (int)$it['id']; ?>">
                                                <button class="btn btn-outline-secondary btn-sm">+</button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold">₡<?php echo number_format((float)$it['subtotal'], 2); ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('¿Eliminar este producto del carrito?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_producto" value="<?php echo (int)$it['id']; ?>">
                                            <button class="btn btn-outline-danger btn-sm">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">₡<?php echo number_format($total, 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between mt-3">
                    <div class="d-flex gap-2">
                        <a href="Modulos/menu.php" class="btn btn-outline-secondary ">Seguir comprando</a>
                        <form method="post" onsubmit="return confirm('¿Vaciar todo el carrito?');">
                            <input type="hidden" name="accion" value="vaciar">
                            <button class="btn btn-outline-danger">Vaciar carrito</button>
                        </form>
                    </div>
                    <!-- Dentro de tu carrito -->
                    <div class="text-end mt-3">
                        <a href="Modulos/checkout.php" class="btn btn-primary">Finalizar compra</a>
                    </div>

                </div>
            <?php endif; ?>
        </section>

        <?php renderFooter(); ?>
    </main>
</body>

</html>
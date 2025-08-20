<?php
// Modulos/facturas/ver.php
session_start();
require_once('../include/layout.php');
require_once('../include/conexion.php');
require_once('../models/factura_model.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    exit('Solicitud inválida.');
}
$id_factura = (int)$_GET['id'];

$model  = new FacturaModel($mysqli);
$logged = !empty($_SESSION['usuario']);
if (!$logged) {
    header('Location: ../login.php?next=facturas/ver.php?id=' . $id_factura);
    exit;
}

// Seguridad: la factura debe pertenecer al cliente de esta sesión
$id_cli = $model->obtenerIdClientePorUsuario((int)$_SESSION['usuario']);
if (!$id_cli) {
    http_response_code(403);
    exit('No autorizado.');
}

$data = $model->obtenerConDetalles($id_factura);
$fac  = $data['factura'] ?? null;
$det  = $data['detalles'] ?? [];

if (!$fac || (int)$fac['id_cliente'] !== (int)$id_cli) {
    http_response_code(404);
    exit('Factura no encontrada.');
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <base href="/G3_SC-502_MN_AvncesProyecto/Avance3/">
    <title>Factura #<?= (int)$id_factura ?> | Soluna</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <a href="Modulos/pedidos.php" class="btn btn-outline-secondary btn-sm">← Volver</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">Imprimir</button>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="m-0">Factura #<?= (int)$id_factura ?></h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Cliente</h6>
                        <div><strong><?= e($fac['cliente_nombre'] ?? 'Cliente') ?></strong></div>
                        <div><?= e($fac['direccion'] ?? '') ?></div>
                        <div><?= e($fac['telefono'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted">Datos de la factura</h6>
                        <div>Fecha factura: <?= e($fac['fecha'] ?? '') ?></div>
                        <?php if (!empty($fac['fecha_creacion'])): ?>
                            <div>Fecha pedido: <?= e($fac['fecha_creacion']) ?></div>
                        <?php endif; ?>
                        <div>ID Pedido: #<?= (int)($fac['id_pedido'] ?? 0) ?></div>
                    </div>
                </div>

                <hr>

                <?php if ($det): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center" style="width:100px;">Cantidad</th>
                                    <th class="text-end" style="width:150px;">Precio unit.</th>
                                    <th class="text-end" style="width:150px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalCalc = 0.0;
                                foreach ($det as $d):
                                    $nombre = $d['nombre_producto'] ?? ('#' . $d['id_producto']);
                                    $cant   = (int)$d['cantidad'];
                                    $pu     = (float)$d['precio_unitario'];
                                    $sub    = $cant * $pu;
                                    $totalCalc += $sub;
                                ?>
                                    <tr>
                                        <td><?= e($nombre) ?></td>
                                        <td class="text-center"><?= $cant ?></td>
                                        <td class="text-end">₡<?= number_format($pu, 2, '.', ',') ?></td>
                                        <td class="text-end">₡<?= number_format($sub, 2, '.', ',') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total (calculado)</th>
                                    <th class="text-end">₡<?= number_format($totalCalc, 2, '.', ',') ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Total (registrado)</th>
                                    <th class="text-end">₡<?= number_format((float)$fac['total'], 2, '.', ',') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-muted">Esta factura no tiene líneas de detalle.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
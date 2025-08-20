<?php
header('Content-Type: application/json; charset=utf-8');

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$id = (int)($_POST['id_factura'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'id_factura inválido']); exit;
}

try {
  // Encabezado de factura
  $sql = "SELECT f.id_factura, f.fecha, f.total, f.id_pedido, f.id_cliente,
                 mp.nombre AS metodo_pago,
                 c.nombre  AS cliente_nombre, c.telefono, c.direccion,
                 p.fecha_creacion
          FROM Factura f
          LEFT JOIN Metodo_Pago mp ON mp.id_metodo_pago = f.id_metodo_pago
          LEFT JOIN Pedido      p  ON p.id_pedido       = f.id_pedido
          LEFT JOIN Cliente     c  ON c.id_cliente      = f.id_cliente
          WHERE f.id_factura = ? LIMIT 1";
  $st = $mysqli->prepare($sql);
  $st->bind_param('i', $id);
  $st->execute();
  $fac = $st->get_result()->fetch_assoc();

  if (!$fac) { throw new RuntimeException('Factura no encontrada'); }

  // Detalles de factura
  $sql2 = "SELECT id_detalle, id_producto, nombre_producto, cantidad, precio_unitario
           FROM Detalle_Factura WHERE id_factura = ? ORDER BY id_detalle ASC";
  $st2 = $mysqli->prepare($sql2);
  $st2->bind_param('i', $id);
  $st2->execute();
  $det = $st2->get_result()->fetch_all(MYSQLI_ASSOC);

  // Calcular totales por si difieren
  $total_calc = 0.0;
  foreach ($det as $d) {
    $total_calc += (float)$d['precio_unitario'] * (int)$d['cantidad'];
  }

  echo json_encode([
    'ok'       => true,
    'factura'  => $fac,
    'detalles' => $det,
    'total_calc' => round($total_calc, 2)
  ]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}

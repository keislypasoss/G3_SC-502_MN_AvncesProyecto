<?php
// Modulos/checkout_api.php
header('Content-Type: application/json; charset=utf-8');

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/carrito_model.php';
require_once $APP_ROOT . '/models/pedido_model.php';
require_once $APP_ROOT . '/models/factura_model.php';
require_once $APP_ROOT . '/models/producto_model.php';


$carrito = new CarritoModel();
$pedidoM = new PedidoModel($mysqli);
$facturaM = new FacturaModel($mysqli);
$prodM   = new ProductoModel($mysqli);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (($_POST['accion'] ?? '') !== 'checkout') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Acción inválida']);
    exit;
}

try {
    $items = $carrito->items();
    if (!$items) throw new RuntimeException('El carrito está vacío');

    $nombre    = trim($_POST['nombre'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $nota      = trim($_POST['nota'] ?? '');
    $metodoNom = trim($_POST['metodo_nombre'] ?? '');

    if ($nombre === '') throw new RuntimeException('Nombre requerido');

    // Validar productos y usar precio actual
    $total = 0.0;
    $lineas = [];
    foreach ($items as $it) {
        $p = $prodM->obtenerPorId((int)$it['id']);
        if (!$p || (int)$p['disponible'] !== 1) {
            throw new RuntimeException('Producto no disponible: ID ' . (int)$it['id']);
        }
        $precio = (float)$p['precio'];
        $cant   = (int)$it['cantidad'];
        $total += $precio * $cant;
        $lineas[] = [
            'id_producto' => (int)$it['id'],
            'nombre'      => $p['nombre'],
            'precio'      => $precio,
            'cantidad'    => $cant
        ];
    }

    // Ids auxiliares
    $id_estado = $pedidoM->obtenerEstadoPedidoId('Pagado'); // por script provisto
    if (!$id_estado) throw new RuntimeException('No existe estado "Pagado" para Pedido');

    $id_metodo = $facturaM->obtenerMetodoPagoIdPorNombre($metodoNom);
    if (!$id_metodo) throw new RuntimeException('Método de pago inválido');

    // Crear/obtener cliente básico (sin usuario)
    $id_cliente = null;
    if ($nombre !== '' || $telefono !== '' || $direccion !== '') {
        $sqlC = "INSERT INTO Cliente (id_usuario, nombre, telefono, direccion) VALUES (NULL, ?, ?, ?)";
        $stC = $mysqli->prepare($sqlC);
        $stC->bind_param('sss', $nombre, $telefono, $direccion);
        $stC->execute();
        $id_cliente = (int)$mysqli->insert_id;
    }

    // Transacción
    $mysqli->begin_transaction();
    try {
        // Pedido
        $id_pedido = $pedidoM->crearPedido($id_cliente, $nota, $id_estado);

        // Detalles del pedido
        foreach ($lineas as $ln) {
            $pedidoM->agregarDetalle($id_pedido, $ln['id_producto'], $ln['cantidad'], $ln['precio']);
        }

        // Factura
        $id_factura = $facturaM->crearFactura($id_cliente, $id_pedido, $id_metodo, $total);

        // Detalles de factura
        foreach ($lineas as $ln) {
            $facturaM->agregarDetalleFactura($id_factura, $ln['id_producto'], $ln['cantidad'], $ln['precio'], $ln['nombre']);
        }

        $mysqli->commit();
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }

    // Limpiar carrito
    $carrito->clear();

    echo json_encode([
        'ok'         => true,
        'id_pedido'  => $id_pedido,
        'id_factura' => $id_factura,
        'metodo'     => $metodoNom
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}

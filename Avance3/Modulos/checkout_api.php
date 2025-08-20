<?php

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/carrito_model.php';
require_once $APP_ROOT . '/models/producto_model.php';
require_once $APP_ROOT . '/models/pedido_model.php';
require_once $APP_ROOT . '/models/factura_model.php';

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

// === Obtener usuario de la sesión ===
$idUsuario = null;
if (isset($_SESSION['usuario'])) {
    $idUsuario = (int)$_SESSION['usuario'];
}

if (!$idUsuario) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Debe iniciar sesión para confirmar la compra.']);
    exit;
}

try {
    $items = $carrito->items();
    if (!$items) throw new RuntimeException('El carrito está vacío');

    $nota      = trim($_POST['nota'] ?? '');
    $metodoNom = trim($_POST['metodo_nombre'] ?? 'Tarjeta'); 

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


    $id_estado = $pedidoM->obtenerEstadoPedidoId('Pagado'); 
    if (!$id_estado) throw new RuntimeException('No existe estado "Pagado" para Pedido');

    $id_metodo = $facturaM->obtenerMetodoPagoIdPorNombre($metodoNom);
    if (!$id_metodo) throw new RuntimeException('Método de pago inválido');

    $id_cliente = null;

  
    $st = $mysqli->prepare("SELECT id_cliente, nombre, telefono, direccion FROM Cliente WHERE id_usuario=? LIMIT 1");
    $st->bind_param('i', $idUsuario);
    $st->execute();
    $cli = $st->get_result()->fetch_assoc();

    if ($cli) {
        $id_cliente = (int)$cli['id_cliente'];
    } else {
        $correo = '';
        $stU = $mysqli->prepare("SELECT correo FROM Usuario WHERE id_usuario=? LIMIT 1");
        $stU->bind_param('i', $idUsuario);
        $stU->execute();
        if ($u = $stU->get_result()->fetch_assoc()) {
            $correo = (string)$u['correo'];
        }
        $nombreAuto = $correo ? explode('@', $correo)[0] : ('Usuario#' . $idUsuario);

        $sqlC = "INSERT INTO Cliente (id_usuario, nombre, telefono, direccion) VALUES (?,?, '', '')";
        $stC = $mysqli->prepare($sqlC);
        $stC->bind_param('is', $idUsuario, $nombreAuto);
        $stC->execute();
        $id_cliente = (int)$mysqli->insert_id;
    }

    $mysqli->begin_transaction();
    try {
        $id_pedido = $pedidoM->crearPedido($id_cliente, $nota, $id_estado);

        foreach ($lineas as $ln) {
            $pedidoM->agregarDetalle($id_pedido, $ln['id_producto'], $ln['cantidad'], $ln['precio']);
        }

        $id_factura = $facturaM->crearFactura($id_cliente, $id_pedido, $id_metodo, $total);

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

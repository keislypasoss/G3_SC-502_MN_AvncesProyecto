<?php

header('Content-Type: application/json; charset=utf-8');

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/producto_model.php';
require_once $APP_ROOT . '/models/carrito_model.php';

$modelProd = new ProductoModel($mysqli);
$carrito   = new CarritoModel();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'agregar':
            $id  = (int)($_POST['id_producto'] ?? 0);
            $qty = max(1, (int)($_POST['cantidad'] ?? 1));

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
                exit;
            }

            $p = $modelProd->obtenerPorId($id);
            if (!$p || !(int)$p['disponible']) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'msg' => 'Producto no disponible']);
                exit;
            }

            $carrito->add($id, $p['nombre'], (float)$p['precio'], $qty, $p['imagen'] ?? '');
            echo json_encode([
                'ok'    => true,
                'msg'   => 'Agregado al carrito',
                'count' => $carrito->count(),
                'total' => $carrito->total()
            ]);
            break;

        case 'contar':
            echo json_encode([
                'ok'    => true,
                'count' => $carrito->count(),
                'total' => $carrito->total()
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Acción inválida']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}

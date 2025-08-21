<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$APP_ROOT = realpath(__DIR__ . '/..');
require_once $APP_ROOT . '/include/conexion.php';
require_once $APP_ROOT . '/models/usuario_model.php';

function esAdmin(): bool {
    $rol = null;
    if (isset($_SESSION['usuario']['rol'])) $rol = $_SESSION['usuario']['rol'];
    elseif (isset($_SESSION['rol'])) $rol = $_SESSION['rol'];
    $rol = $rol ? strtolower($rol) : '';
    return in_array($rol, ['admin','administrador'], true);
}
if (!esAdmin()) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No autorizado']); exit;
}

$usuarioM = new UsuarioModel($mysqli);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

$accion = $_POST['accion'] ?? '';
try {
    if ($accion === 'get') {
        $id = (int)($_POST['id_usuario'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException('ID inválido');

        $u = $usuarioM->getById($id);
        if (!$u) throw new RuntimeException('Usuario no encontrado');

        echo json_encode(['ok'=>true,'usuario'=>$u]); 
        exit;
    }

    if ($accion === 'toggle') {
        $id = (int)($_POST['id_usuario'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException('ID inválido');

        $ok = $usuarioM->update($id, ['activo'=>$activo]);
        echo json_encode(['ok'=>$ok]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Acción inválida']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}

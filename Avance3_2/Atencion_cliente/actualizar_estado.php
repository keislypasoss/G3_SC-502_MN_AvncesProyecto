<?php
session_start();
require_once('../include/conexion.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_usuario'])) {
    echo "Error: Debe iniciar sesión";
    exit;
}

// Obtener el id_solicitud enviado por AJAX
$id_solicitud = $_POST['id_solicitud'] ?? 0;

if ($id_solicitud == 0) {
    echo "Error: id_solicitud no definido";
    exit;
}

// Preparar la actualización
$stmt = $mysqli->prepare("UPDATE solicitudes SET estado = 'Hecho' WHERE id_solicitud = ?");
$stmt->bind_param("i", $id_solicitud);

// Ejecutar y devolver resultado
if ($stmt->execute()) {
    echo "Estado actualizado correctamente";
} else {
    echo "Error al actualizar estado: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
